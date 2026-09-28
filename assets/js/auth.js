(function () {
  'use strict';

  document.querySelectorAll('[data-toggle-password]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var input = document.getElementById(btn.getAttribute('data-toggle-password'));
      if (!input) return;
      var showing = input.type === 'text';
      input.type = showing ? 'password' : 'text';
      btn.setAttribute('aria-label', showing ? 'Mostrar senha' : 'Ocultar senha');
      btn.setAttribute('title', showing ? 'Mostrar senha' : 'Ocultar senha');
    });
  });

  document.querySelectorAll('[data-auth-form]').forEach(function (form) {
    form.addEventListener('submit', function (event) {
      var required = form.querySelectorAll('[required]');
      var invalid = null;

      required.forEach(function (field) {
        if (!invalid && !String(field.value).trim()) {
          invalid = field;
        }
      });

      if (invalid) {
        event.preventDefault();
        invalid.focus();
        invalid.style.borderColor = '#ef4444';
        window.setTimeout(function () { invalid.style.borderColor = ''; }, 2000);
      }
    });
  });

  var senha = document.getElementById('signup-senha');
  var conf  = document.getElementById('signup-senha-conf');
  if (senha && conf) {
    conf.addEventListener('input', function () {
      conf.setCustomValidity(senha.value === conf.value ? '' : 'As senhas precisam ser iguais.');
    });
  }
})();
