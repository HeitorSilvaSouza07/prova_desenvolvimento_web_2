(function () {
  'use strict';

  /* ------------------------ Menu lateral (mobile) ----------------------- */
  var sidebar = document.querySelector('[data-sidebar]');
  var overlay = document.querySelector('.app-overlay');
  var burger  = document.querySelector('[data-sidebar-toggle]');

  function closeSidebar() {
    if (sidebar) sidebar.classList.remove('is-open');
    if (overlay) overlay.classList.remove('is-visible');
  }

  if (burger && sidebar) {
    burger.addEventListener('click', function () {
      sidebar.classList.toggle('is-open');
      if (overlay) overlay.classList.toggle('is-visible', sidebar.classList.contains('is-open'));
    });
  }
  if (overlay) overlay.addEventListener('click', closeSidebar);
  document.querySelectorAll('[data-sidebar-close]').forEach(function (el) {
    el.addEventListener('click', closeSidebar);
  });

  /* ------------------------- Mostrar/ocultar senha ---------------------- */
  document.querySelectorAll('[data-toggle-password]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var input = document.getElementById(btn.getAttribute('data-toggle-password'));
      if (!input) return;
      var showing = input.type === 'text';
      input.type = showing ? 'password' : 'text';
      btn.setAttribute('aria-label', showing ? 'Mostrar senha' : 'Ocultar senha');
    });
  });

  /* ----------------------- Rolagem para um elemento -------------------- */
  document.querySelectorAll('[data-scroll-to]').forEach(function (el) {
    el.addEventListener('click', function () {
      var target = document.getElementById(el.getAttribute('data-scroll-to'));
      if (!target) return;
      target.scrollIntoView({ behavior: 'smooth', block: 'start' });
      var first = target.querySelector('input, select, textarea');
      if (first) window.setTimeout(function () { first.focus(); }, 400);
    });
  });

  /* --------------------------- Confirmações ---------------------------- */
  document.querySelectorAll('[data-confirm]').forEach(function (el) {
    el.addEventListener('click', function (event) {
      var message = el.getAttribute('data-confirm') || 'Tem certeza?';
      if (!window.confirm(message)) {
        event.preventDefault();
      }
    });
  });

  /* ------------- Busca e filtros em tabelas (data-table) --------------- */
  document.querySelectorAll('[data-table]').forEach(function (wrapper) {
    var search  = wrapper.querySelector('[data-table-search]');
    var select  = wrapper.querySelector('[data-table-filter]');
    var rows    = wrapper.querySelectorAll('tbody tr[data-row]');
    var empty   = wrapper.querySelector('[data-table-empty]');
    var counter = wrapper.querySelector('[data-table-count]');

    if (!rows.length) return;

    function apply() {
      var term = search ? search.value.trim().toLowerCase() : '';
      var value = select ? select.value : '';
      var visible = 0;

      rows.forEach(function (row) {
        var matchesText = !term || row.textContent.toLowerCase().indexOf(term) !== -1;
        var matchesFilter = !value || row.getAttribute('data-group') === value;
        var show = matchesText && matchesFilter;
        row.hidden = !show;
        if (show) visible++;
      });

      if (empty) empty.hidden = visible !== 0;
      if (counter) counter.textContent = visible + (visible === 1 ? ' registro' : ' registros');
    }

    if (search) search.addEventListener('input', apply);
    if (select) select.addEventListener('change', apply);
    apply();
  });

  /* ------------------------- Abas de navegação ------------------------- */
  document.querySelectorAll('[data-tabs]').forEach(function (tabs) {
    var buttons = tabs.querySelectorAll('[data-tab]');
    buttons.forEach(function (btn) {
      btn.addEventListener('click', function () {
        buttons.forEach(function (b) { b.classList.remove('is-active'); });
        btn.classList.add('is-active');
        var target = btn.getAttribute('data-tab');
        document.querySelectorAll('[data-panel]').forEach(function (panel) {
          panel.hidden = panel.getAttribute('data-panel') !== target;
        });
      });
    });
  });

  /* ------------- Autoesconde mensagens flash após 6s ------------------- */
  document.querySelectorAll('.flash').forEach(function (flash) {
    window.setTimeout(function () {
      flash.style.transition = 'opacity .4s';
      flash.style.opacity = '0';
      window.setTimeout(function () { flash.remove(); }, 400);
    }, 6000);
  });
})();
