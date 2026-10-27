/* Applies per-request style declarations PHP renders into data-style="".
   cssText is a direct CSSOM write, which style-src does not govern. */
document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('[data-style]').forEach(function (el) {
    el.style.cssText = el.getAttribute('data-style');
  });
});
