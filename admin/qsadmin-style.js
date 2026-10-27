/* Applies per-request style declarations that PHP renders into data-style="".
   Those values (participant colours, progress-bar widths) differ per request,
   so they cannot become static CSS classes. Assigning through cssText is a
   direct CSSOM write, which style-src does NOT govern -- only the parsed
   style="" attribute and setAttribute('style') are blocked. */
document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('[data-style]').forEach(function (el) {
    el.style.cssText = el.getAttribute('data-style');
  });
});
