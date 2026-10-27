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

function quiz_throttle_read()
{
    $f = quiz_throttle_file();
    $raw = @file_get_contents($f);
    $d = $raw ? json_decode($raw, true) : null;
    if (!is_array($d) || !isset($d['n'], $d['t'])) {
        return ['n' => 0, 't' => time()];
    }
    if (time() - (int) $d['t'] > QUIZ_TRY_WIN) {
        return ['n' => 0, 't' => time()];
    }
    return ['n' => (int) $d['n'], 't' => (int) $d['t']];
}

function quiz_throttle_write($d)
{
    @mkdir(QUIZ_THROTTLE_DIR, 0700, true);
    $f = quiz_throttle_file();
    // write-then-rename so a concurrent read never sees a half-written file
    $tmp = $f . '.' . getmypid() . '.tmp';
    if (@file_put_contents($tmp, json_encode($d)) !== false) {
        @rename($tmp, $f);
    }
}

function quiz_throttle_blocked()
{
    $d = quiz_throttle_read();
    return $d['n'] >= QUIZ_TRY_MAX;
}

function quiz_throttle_bump()
{
    $d = quiz_throttle_read();
    quiz_throttle_write(['n' => $d['n'] + 1, 't' => $d['t']]);
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
        if (quiz_throttle_blocked()) {
            header('HTTP/1.1 429 Too Many Requests');
            header('Retry-After: ' . QUIZ_TRY_WIN);
            quiz_login_form('Too many attempts. Wait 15 minutes.');
        }
        // Count the attempt BEFORE verifying. Incrementing only on failure means
        // an attacker who aborts the connection mid-verify is never counted.
        quiz_throttle_bump();
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
