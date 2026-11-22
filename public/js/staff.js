/* staff.js: tiny helpers for the SB member pages (menu, pop-ups, tabs, clickable rows). */
(function () {
  "use strict";
  var $ = function (s, r) { return (r || document).querySelector(s); };
  var $$ = function (s, r) { return Array.prototype.slice.call((r || document).querySelectorAll(s)); };

  // Phone menu: the button shows or hides the sidebar
  var menuBtn = $("#menuBtn"), sidebar = $("#sidebar");
  if (menuBtn && sidebar) {
    menuBtn.addEventListener("click", function (e) { e.stopPropagation(); sidebar.classList.toggle("open"); });
    document.addEventListener("click", function (e) { if (!e.target.closest("#sidebar")) sidebar.classList.remove("open"); });
  }

  // Pop-ups: a button with data-open="id" opens the .modal-back with that id; data-close or Esc closes it
  $$("[data-open]").forEach(function (b) {
    b.addEventListener("click", function () {
      var m = document.getElementById(b.dataset.open);
      if (!m) return;
      m.classList.add("open");
      var f = $("input:not([type=hidden]),select,textarea", m);
      if (f) f.focus();
    });
  });
  $$(".modal-back").forEach(function (m) {
    m.addEventListener("click", function (e) { if (e.target === m || e.target.closest("[data-close]")) m.classList.remove("open"); });
  });
  document.addEventListener("keydown", function (e) {
    if (e.key === "Escape") $$(".modal-back.open").forEach(function (m) { m.classList.remove("open"); });
  });

  // Tabs: buttons with data-tab show the panel with the same data-panel. The wrapper's data-initial picks the first tab.
  $$("[data-tabs]").forEach(function (root) {
    var btns = $$("[data-tab]", root), panels = $$("[data-panel]", root);
    function show(n) {
      btns.forEach(function (b) { b.classList.toggle("active", b.dataset.tab === n); });
      panels.forEach(function (p) { p.hidden = p.dataset.panel !== n; });
    }
    btns.forEach(function (b) { b.addEventListener("click", function () { show(b.dataset.tab); }); });
    show(root.dataset.initial || (btns[0] && btns[0].dataset.tab));
  });

  // Table rows with data-href open that link when clicked (except on links and buttons inside the row)
  $$("tr[data-href]").forEach(function (tr) {
    tr.addEventListener("click", function (e) { if (!e.target.closest("a,button")) location.href = tr.dataset.href; });
  });
})();
