(function () {
  'use strict';

  var buttons = document.querySelectorAll('[data-faq]');

  function closeAll() {
    buttons.forEach(function (btn) {
      btn.setAttribute('aria-expanded', 'false');
      var icon = btn.querySelector('.pro-siga__faq-icon');
      if (icon) icon.textContent = '+';
      var panel = document.getElementById(btn.getAttribute('aria-controls'));
      if (panel) panel.hidden = true;
    });
  }

  buttons.forEach(function (btn) {
    btn.addEventListener('click', function () {
      var isOpen = btn.getAttribute('aria-expanded') === 'true';
      closeAll();
      if (!isOpen) {
        btn.setAttribute('aria-expanded', 'true');
        var icon = btn.querySelector('.pro-siga__faq-icon');
        if (icon) icon.textContent = '\u2212';
        var panel = document.getElementById(btn.getAttribute('aria-controls'));
        if (panel) panel.hidden = false;
      }
    });
  });
})();
