/* staff.js: tiny helpers for the SB member pages (menu, pop-ups, tabs, clickable rows). */
(function () {
  "use strict";
  var $ = function (s, r) { return (r || document).querySelector(s); };
  var $$ = function (s, r) { return Array.prototype.slice.call((r || document).querySelectorAll(s)); };

  // Phone menu: the button shows or hides the sidebar
  var menuBtn = $("#menuBtn"), sidebar = $("#sidebar");
  if (menuBtn && sidebar) {
    var previousMenuFocus=null;
    var closeMenu=function(){sidebar.classList.remove("open");menuBtn.setAttribute("aria-expanded","false");if(previousMenuFocus)previousMenuFocus.focus();};
    menuBtn.addEventListener("click", function (e) {
      e.stopPropagation();
      if(sidebar.classList.contains("open")){closeMenu();return;}
      previousMenuFocus=document.activeElement;
      sidebar.classList.add("open");
      menuBtn.setAttribute("aria-expanded","true");
      var first=$("a,button",sidebar);if(first)first.focus();
    });
    document.addEventListener("click", function (e) { if (!e.target.closest("#sidebar")&&!e.target.closest("#menuBtn")) closeMenu(); });
    document.addEventListener("keydown",function(e){
      if(e.key==="Escape"&&sidebar.classList.contains("open"))closeMenu();
      if(e.key==="Tab"&&sidebar.classList.contains("open")){
        var items=$$("a,button,input",sidebar).filter(function(el){return !el.disabled;});
        if(!items.length)return;
        var first=items[0],last=items[items.length-1];
        if(e.shiftKey&&document.activeElement===first){e.preventDefault();last.focus();}
        else if(!e.shiftKey&&document.activeElement===last){e.preventDefault();first.focus();}
      }
    });
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
    tr.setAttribute("tabindex","0");
    tr.setAttribute("role","link");
    tr.addEventListener("keydown",function(e){if(e.key==="Enter"||e.key===" "){e.preventDefault();location.href=tr.dataset.href;}});
    tr.addEventListener("click", function (e) { if (!e.target.closest("a,button")) location.href = tr.dataset.href; });
  });

  // Fetches the dashboard marker data and builds an interactive Leaflet map.
  window.initOverviewMap = async function (url, elId) {
    elId = elId || "overview-map";
    var el = document.getElementById(elId);
    if (!el) return;
    var status = document.getElementById("mapStatus");
    var retry = document.getElementById("mapRetry");
    var setStatus = function (message, showRetry) {
      if (status) status.textContent = message;
      if (retry) retry.hidden = !showRetry;
    };

    if (!window.L) {
      setStatus("The map library could not load. Check your connection and retry.", true);
      return;
    }

    var map = L.map(el).setView([13.177, 121.278], 11);
    var tileLayer = L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
      maxZoom: 19,
      attribution: "&copy; OpenStreetMap contributors"
    }).addTo(map);
    setStatus("Loading map markers...", false);
    tileLayer.on("tileerror", function () {
      setStatus("Map markers are available, but OpenStreetMap tiles could not load. Check your connection and retry.", true);
    });

    var bounds = [];
    var pin = function (item, color, title, detail, link) {
      var lat = Number(item.latitude);
      var lng = Number(item.longitude);
      if (!Number.isFinite(lat) || !Number.isFinite(lng)) return;

      var marker = L.circleMarker([lat, lng], {
        radius: 8,
        color: "#ffffff",
        weight: 2,
        fillColor: color,
        fillOpacity: 0.92
      }).addTo(map);

      var popup = "<strong>" + title.replace(/[&<>"']/g, "") + "</strong>";
      if (detail) popup += "<br>" + detail.replace(/[&<>"']/g, "");
      if (link) popup += '<br><a href="' + link + '">View concern</a>';
      marker.bindPopup(popup);
      marker.on("keypress", function (event) {
        if (event.originalEvent && (event.originalEvent.key === "Enter" || event.originalEvent.key === " ")) marker.openPopup();
      });
      bounds.push([lat, lng]);
    };

    try {
      var response = await fetch(url, { headers: { Accept: "application/json" } });
      if (!response.ok) throw new Error("Map data unavailable");
      var data = await response.json();
      map.invalidateSize();

      (data.sites || []).forEach(function (site) {
        var count = (data.reports || []).filter(function (report) {
          return report.barangay === site.name;
        }).length;
        pin(site, "#0B2A5B", site.name, count + " concern pin(s)", null);
      });
      (data.reports || []).forEach(function (report) {
        var countText = report.reports_count > 1 ? report.reports_count + " reports" : "1 report";
        pin(report, "#e0a02e", report.title || "Concern", report.detail + " • " + countText, report.url);
      });
      (data.cleanups || []).forEach(function (cleanup) {
        pin(cleanup, "#1f7a3d", cleanup.title || "Cleanup", cleanup.detail || "", cleanup.url || null);
      });

      if (bounds.length) {
        map.fitBounds(bounds, { padding: [25, 25], maxZoom: 13 });
        setStatus((data.reports || []).length
          ? "Map loaded with " + (data.reports || []).length + " concern marker(s)."
          : "Map loaded. No concern reports with saved coordinates match these filters.", false);
      } else {
        map.setView([13.177, 121.278], 11);
        el.setAttribute("aria-label", "Interactive map of concern locations. No markers match the selected filters.");
        setStatus("No barangay locations or concern markers are available for this filter.", false);
      }
      setTimeout(function () { map.invalidateSize(); }, 100);
    } catch (error) {
      setStatus("Map data could not be loaded. Check your connection and retry.", true);
    }
  };

  var mapRetry = document.getElementById("mapRetry");
  if (mapRetry) {
    mapRetry.addEventListener("click", function () {
      window.location.reload();
    });
  }
})();
