<?php
/**
 * Authentication for /quiz-system/admin/ (ROP-160).
 *
 * Every file in that directory was reachable unauthenticated, carrying POST
 * forms that delete quizzes and upload files. Seven external IPs had already
 * probed it. This is the gate.
 *
 * Call require_admin() as the FIRST thing every admin page does, before any
 * output, because it may redirect or render the login form and exit.
 *
 * Deliberately different from fileupload/admin.php, which compares a plaintext
 * constant and has no CSRF:
 *   - the password is stored only as a bcrypt hash, outside the docroot
 *   - failed attempts are rate limited, keyed per IP
 *   - the login form and every admin mutation carry a CSRF token
 *   - the session cookie is HttpOnly + Secure + SameSite=Strict
 *   - no inline CSS or JS anywhere, so the strict site CSP applies unchanged
 */

const QUIZ_SECRETS   = '/etc/quiz-secrets.env';
const QUIZ_IDLE_MAX  = 3600;   // seconds of inactivity before re-login
const QUIZ_TRY_MAX   = 5;      // failed attempts per window
const QUIZ_TRY_WIN   = 900;    // window, seconds

function quiz_secret($key)
{
    static $vals = null;
    if ($vals === null) {
        $vals = [];
        foreach (@file(QUIZ_SECRETS, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
            if ($line === '' || $line[0] === '#' || strpos($line, '=') === false) {
                continue;
            }
            list($k, $v) = explode('=', $line, 2);
            $vals[trim($k)] = trim($v);
        }
    }
    return $vals[$key] ?? null;
}

function quiz_session_start()
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    session_name('quizadmin');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/quiz-system/',
        'secure'   => true,          // the site is HTTPS-only with HSTS
        'httponly' => true,
        'samesite' => 'Strict',      // an admin panel has no cross-site use
    ]);
    session_start();
}

/** Stateless-ish CSRF token, bound to the session. */
function quiz_csrf_token()
{
    quiz_session_start();
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function quiz_csrf_ok($token)
{
    return !empty($_SESSION['csrf']) && is_string($token)
        && hash_equals($_SESSION['csrf'], $token);
}

/**
 * Guard a mutating request. Call at the top of any admin page that acts on
 * POST, after require_admin(). Kept here rather than in each page so a new
 * admin page cannot forget it.
 */
function quiz_require_csrf()
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return;
    }
    if (!quiz_csrf_ok($_POST['csrf'] ?? null)) {
        header('HTTP/1.1 403 Forbidden');
        header('Content-Type: text/plain; charset=utf-8');
        echo "Request rejected: session expired or invalid. Reload the page and try again.\n";
        exit;
    }
}

const QUIZ_THROTTLE_DIR = '/var/lib/quiz-throttle';

/**
 * Failed-attempt counter, keyed by client IP and held SERVER-SIDE.
 *
 * It used to live in $_SESSION. That only rate-limits an attacker who chooses
 * to keep sending the session cookie: drop the cookie and every request gets a
 * fresh empty session, so the counter never reaches the limit. Measured before
 * the fix -- a client that kept its cookie was blocked on attempt 6, a client
 * that discarded it was never blocked at all.
 *
 * REMOTE_ADDR is the real client address here because Cloudflare is DNS-only
 * for this domain (grey cloud), so nothing proxies the connection. If that ever
 * changes to a proxied setup, every request would arrive from a handful of
 * proxy IPs and this would throttle everyone at once -- read the forwarded
 * header then, and only if it can be trusted.
 */
function quiz_throttle_file()
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    return QUIZ_THROTTLE_DIR . '/' . hash('sha256', $ip) . '.json';
}

/**
 * Count one sign-in attempt and say whether it may go ahead: 'ok', 'blocked'
 * or 'error'.
 *
 * The check and the count happen in ONE step under an exclusive lock. They
 * used to be two calls (is it blocked? then add one), so attempts arriving at
 * the same instant could all pass the check before any of them was counted.
 *
 * It fails CLOSED: if the counter cannot be opened, locked or saved, the
 * answer is 'error' and the caller refuses the attempt. The old code ignored a
 * failed write, so a broken counter meant unlimited guesses with nothing said.
 */
function quiz_throttle_take()
{
    if (!is_dir(QUIZ_THROTTLE_DIR) && !@mkdir(QUIZ_THROTTLE_DIR, 0700, true) && !is_dir(QUIZ_THROTTLE_DIR)) {
        error_log('quiz-system: login throttle unavailable: cannot create ' . QUIZ_THROTTLE_DIR);
        return 'error';
    }
    $fh = @fopen(quiz_throttle_file(), 'c+');
    if ($fh === false) {
        error_log('quiz-system: login throttle unavailable: cannot open its counter in ' . QUIZ_THROTTLE_DIR);
        return 'error';
    }
    try {
        if (!flock($fh, LOCK_EX)) {
            error_log('quiz-system: login throttle unavailable: cannot lock its counter');
            return 'error';
        }
        $raw = stream_get_contents($fh);
        $d = $raw ? json_decode($raw, true) : null;
        $now = time();
        if (!is_array($d) || !isset($d['n'], $d['t']) || $now - (int) $d['t'] > QUIZ_TRY_WIN) {
            $d = ['n' => 0, 't' => $now];          // no record, or the window has passed
        }
        if ((int) $d['n'] >= QUIZ_TRY_MAX) {
            return 'blocked';                       // a refused attempt is not counted again
        }
        $json = json_encode(['n' => (int) $d['n'] + 1, 't' => (int) $d['t']]);
        if (!ftruncate($fh, 0) || !rewind($fh) || fwrite($fh, $json) !== strlen($json) || !fflush($fh)) {
            error_log('quiz-system: login throttle unavailable: cannot save its counter');
            return 'error';
        }
        return 'ok';
    } finally {
        flock($fh, LOCK_UN);
        fclose($fh);
    }
}

function quiz_throttle_clear()
{
    @unlink(quiz_throttle_file());
}

function quiz_login_form($error = '')
{
    $token = quiz_csrf_token();
    header('Content-Type: text/html; charset=utf-8');
    echo '<!doctype html><html lang="en"><head><meta charset="utf-8">'
       . '<meta name="viewport" content="width=device-width, initial-scale=1">'
       . '<title>Admin sign in - Quiz System</title>'
       . '<link rel="stylesheet" href="/quiz-system/admin/admin-auth.css">'
       . '</head><body class="qa-page"><main class="qa-card">'
       . '<h1 class="qa-title">Quiz System</h1>'
       . '<p class="qa-sub">Administrator sign in</p>';
    if ($error !== '') {
        echo '<p class="qa-error">' . htmlspecialchars($error, ENT_QUOTES) . '</p>';
    }
    echo '<form method="post" class="qa-form">'
       . '<input type="hidden" name="csrf" value="' . htmlspecialchars($token, ENT_QUOTES) . '">'
       . '<label class="qa-label" for="qa-pw">Password</label>'
       . '<input class="qa-input" id="qa-pw" type="password" name="admin_password" '
       . 'autocomplete="current-password" required autofocus>'
       . '<button class="qa-button" type="submit">Sign in</button>'
       . '</form></main></body></html>';
    exit;
}

function require_admin()
{
    quiz_session_start();

    // Expire an idle session rather than trusting it forever.
    if (!empty($_SESSION['admin_ok'])
        && (time() - ($_SESSION['seen'] ?? 0)) > QUIZ_IDLE_MAX) {
        $_SESSION = [];
        session_destroy();
        quiz_session_start();
    }

    if (!empty($_SESSION['admin_ok'])) {
        $_SESSION['seen'] = time();
        return;
    }

    $hash = quiz_secret('QUIZ_ADMIN_HASH');
    if (!$hash) {
        // Fail CLOSED. A missing secrets file must never mean "let everyone in".
        header('HTTP/1.1 503 Service Unavailable');
        header('Content-Type: text/plain; charset=utf-8');
        echo "Admin sign in is not configured on this server.\n";
        exit;
    }

    if (isset($_POST['admin_password'])) {
        if (!quiz_csrf_ok($_POST['csrf'] ?? null)) {
            quiz_login_form('Session expired. Try again.');
        }
        // Count the attempt BEFORE verifying. Incrementing only on failure means
        // an attacker who aborts the connection mid-verify is never counted.
        $gate = quiz_throttle_take();
        if ($gate === 'blocked') {
            header('HTTP/1.1 429 Too Many Requests');
            header('Retry-After: ' . QUIZ_TRY_WIN);
            quiz_login_form('Too many attempts. Wait 15 minutes.');
        }
        if ($gate !== 'ok') {
            // Fail CLOSED: without a working counter there is no limit on guesses.
            header('HTTP/1.1 503 Service Unavailable');
            header('Retry-After: 300');
            quiz_login_form('Sign in is temporarily unavailable. Try again in a few minutes.');
        }
        if (password_verify($_POST['admin_password'], $hash)) {
            session_regenerate_id(true);       // no session fixation
            $_SESSION['admin_ok'] = true;
            $_SESSION['seen'] = time();
            $_SESSION['csrf'] = bin2hex(random_bytes(32));
            quiz_throttle_clear();
            // Redirect so a refresh does not re-post the password.
            header('Location: ' . $_SERVER['REQUEST_URI'], true, 303);
            exit;
        }
        error_log('quiz-system: failed admin login from ' . ($_SERVER['REMOTE_ADDR'] ?? '?'));
        quiz_login_form('Incorrect password.');
    }

    quiz_login_form();
}
