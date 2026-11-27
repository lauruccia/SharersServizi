// Effepi Master — poco JavaScript, senza librerie.
(function () {
  'use strict';

  // Numeri della home: crescono da zero quando entrano nello schermo.
  var counters = document.querySelectorAll('[data-count]');
  if (counters.length) {
    // Punto delle migliaia anche sui numeri di quattro cifre (2.000, non 2000).
    var fmt = { format: function (n) { return String(n).replace(/\B(?=(\d{3})+(?!\d))/g, '.'); } };
    var still = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var run = function (el) {
      var target = parseInt(el.dataset.count, 10) || 0;
      if (still) { el.textContent = fmt.format(target); return; }
      var start = null;
      var step = function (ts) {
        if (start === null) start = ts;
        var p = Math.min(1, (ts - start) / 1400);
        el.textContent = fmt.format(Math.round(target * (1 - Math.pow(1 - p, 3))));
        if (p < 1) requestAnimationFrame(step);
      };
      requestAnimationFrame(step);
    };
    if ('IntersectionObserver' in window) {
      var io = new IntersectionObserver(function (entries) {
        entries.forEach(function (e) { if (e.isIntersecting) { run(e.target); io.unobserve(e.target); } });
      }, { threshold: 0.4 });
      counters.forEach(function (el) { io.observe(el); });
    } else {
      counters.forEach(run);
    }
  }

  // Animazioni della home: partono quando il blocco entra nello schermo (una sola volta).
  var reveals = document.querySelectorAll('[data-reveal]');
  if (reveals.length) {
    var calm = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    if (calm || !('IntersectionObserver' in window)) {
      reveals.forEach(function (el) { el.classList.add('is-in'); });
    } else {
      var seen = new IntersectionObserver(function (entries) {
        entries.forEach(function (e) { if (e.isIntersecting) { e.target.classList.add('is-in'); seen.unobserve(e.target); } });
      }, { threshold: 0.25 });
      reveals.forEach(function (el) { seen.observe(el); });
    }
  }

  // Mappa dell'hero: le regioni si accendono a turno (con il mouse sopra, resta accesa quella indicata).
  var pins = document.querySelectorAll('[data-pin]');
  if (pins.length && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
    var cur = -1, hover = false;
    var show = function (n) { pins.forEach(function (p, i) { p.classList.toggle('is-on', i === n); }); };
    pins.forEach(function (p, i) {
      p.parentNode.addEventListener('mouseenter', function () { hover = true; });
      p.parentNode.addEventListener('mouseleave', function () { hover = false; });
      p.addEventListener('mouseenter', function () { cur = i; show(i); });
    });
    setInterval(function () { if (hover) return; cur = (cur + 1) % pins.length; show(cur); }, 1600);
  }

  // Menu a pulsante (mobile).
  var burger = document.querySelector('.ef-burger');
  var menu = document.getElementById('ef-menu');
  if (burger && menu) {
    var setMenu = function (open) {
      menu.classList.toggle('is-open', open);
      burger.setAttribute('aria-expanded', open ? 'true' : 'false');
      burger.setAttribute('aria-label', open ? 'Chiudi il menu' : 'Apri il menu');
    };
    burger.addEventListener('click', function () { setMenu(!menu.classList.contains('is-open')); });
    menu.addEventListener('click', function (e) { if (e.target.closest('a')) setMenu(false); });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') { setMenu(false); burger.focus(); } });
    document.addEventListener('click', function (e) { if (!e.target.closest('.ef-header')) setMenu(false); });
  }

  // Moduli di registrazione: su schermi piccoli si vede un modulo alla volta (dealer o agente).
  var regs = document.querySelector('[data-regs]');
  if (regs) {
    var tabs = document.querySelectorAll('[data-regtab]');
    var activate = function (who) {
      regs.setAttribute('data-active', who);
      tabs.forEach(function (t) { t.setAttribute('aria-pressed', t.dataset.regtab === who ? 'true' : 'false'); });
    };
    tabs.forEach(function (t) { t.addEventListener('click', function () { activate(t.dataset.regtab); }); });
    var fromHash = function (h) {
      if (h === '#registrati-dealer') return 'dealer';
      if (h === '#registrati-agente') return 'agent';
      return null;
    };
    var go = function (h) {
      var who = fromHash(h);
      if (!who) return false;
      activate(who);
      var target = document.getElementById('registrati');
      if (target) target.scrollIntoView({ behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth' });
      return true;
    };
    document.addEventListener('click', function (e) {
      var a = e.target.closest('a[href^="#registrati-"]');
      if (a && go(a.getAttribute('href'))) e.preventDefault();
    });
    if (fromHash(location.hash)) { activate(fromHash(location.hash)); }
  }

  // Barra fissa "Registrati / Chiama" (mobile): compare dopo l'hero, sparisce sopra ai moduli.
  var bar = document.querySelector('[data-sticky-cta]');
  if (bar && 'IntersectionObserver' in window) {
    var hero = document.querySelector('.ef-hero-band');
    var form = document.getElementById('registrati');
    var past = false, onForm = false;
    var update = function () { bar.classList.toggle('is-on', past && !onForm); };
    if (hero) new IntersectionObserver(function (en) { past = !en[0].isIntersecting; update(); }).observe(hero);
    if (form) new IntersectionObserver(function (en) { onForm = en[0].isIntersecting; update(); }, { threshold: 0.15 }).observe(form);
  }

  // Conto alla rovescia delle Promo Dedicate.
  // L'orologio del visitatore può essere sbagliato: si parte dall'ora del server.
  var promos = document.querySelectorAll('[data-ends-at]');
  if (promos.length) {
    var holder = document.querySelector('[data-server-now]');
    var offset = holder ? new Date(holder.dataset.serverNow).getTime() - Date.now() : 0;
    var pad = function (n) { return String(n).padStart(2, '0'); };
    var tick = function () {
      var now = Date.now() + offset;
      var alive = 0;
      promos.forEach(function (card) {
        if (!card.isConnected) return;
        var ms = new Date(card.dataset.endsAt).getTime() - now;
        if (ms <= 0) { card.remove(); return; } // scaduta: sparisce da sola
        alive++;
        var sec = Math.floor(ms / 1000);
        var clock = card.querySelector('[data-clock]');
        if (clock) clock.textContent = pad(Math.floor(sec / 3600)) + ':' + pad(Math.floor((sec % 3600) / 60)) + ':' + pad(sec % 60);
      });
      var empty = document.querySelector('[data-promo-empty]');
      if (empty) empty.hidden = alive > 0;
    };
    tick();
    setInterval(tick, 1000);
  }

  // Moduli d'ordine: anteprima di prezzi e totale. Il totale valido lo ricalcola il server.
  var order = document.querySelector('[data-order]');
  if (order) {
    var P = JSON.parse(order.dataset.pricing);
    var euro = function (n) { return n.toFixed(2).replace('.', ',').replace(/\B(?=(\d{3})+(?!\d))/g, '.') + ' €'; };
    var out = function (key, text) { var el = order.querySelector('[data-out="' + key + '"]'); if (el) el.textContent = text; };
    var num = function (el) { return el ? parseFloat(String(el.value).replace(',', '.')) || 0 : 0; };
    var recalc = function () {
      var total = 0, shipping = 0, type = order.dataset.order;
      if (type === 'optima') {
        var plafond = num(order.querySelector('[name="plafond"]'));
        if (plafond > 0) {
          var pct = plafond <= P.plafond.threshold ? P.plafond.discount_low : P.plafond.discount_high;
          var cost = plafond - plafond * pct / 100;
          out('plafond-discount', pct + '%'); out('plafond-cost', euro(cost)); total += cost;
        } else { out('plafond-discount', '–'); out('plafond-cost', euro(0)); }
        var sims = num(order.querySelector('[name="sim_quantity"]'));
        if (sims > 0 && sims % P.sim_optima.multiple === 0) {
          var unit = sims <= P.sim_optima.threshold ? P.sim_optima.price_low_qty : P.sim_optima.price_high_qty;
          out('sim-unit', euro(unit)); out('sim-cost', euro(sims * unit)); total += sims * unit; shipping = P.sim_optima.shipping;
        } else { out('sim-unit', sims > 0 ? 'solo multipli di ' + P.sim_optima.multiple : '–'); out('sim-cost', euro(0)); }
      } else if (type === 'altri_operatori') {
        var any = false;
        order.querySelectorAll('[data-package]').forEach(function (sel) {
          var pieces = parseInt(sel.value, 10) || 0, i = sel.dataset.package, price = P.sim_altri.packages[pieces];
          if (pieces && price) { any = true; out('package-unit-' + i, euro(price)); out('package-cost-' + i, euro(pieces * price)); total += pieces * price; }
          else { out('package-unit-' + i, '–'); out('package-cost-' + i, euro(0)); }
        });
        if (any) shipping = P.sim_altri.shipping;
      } else if (type === 'promo') {
        order.querySelectorAll('[data-promo-price]').forEach(function (inp) { total += Math.max(0, Math.floor(num(inp))) * parseFloat(inp.dataset.promoPrice); });
      } else {
        order.querySelectorAll('[data-row-qty]').forEach(function (inp) {
          total += Math.max(0, Math.floor(num(inp))) * num(order.querySelector('[data-row-price="' + inp.dataset.rowQty + '"]'));
        });
      }
      out('shipping', euro(shipping));
      out('total', euro(total + shipping));
    };
    order.querySelectorAll('[data-calc]').forEach(function (el) { el.addEventListener('input', recalc); el.addEventListener('change', recalc); });
    recalc();
    // L'agente cambia dealer: ragione sociale, indirizzo ed email seguono la scelta.
    var pick = order.querySelector('[data-dealer-select]');
    if (pick) pick.addEventListener('change', function () {
      var o = pick.options[pick.selectedIndex];
      order.querySelector('[name="business_name"]').value = o.dataset.name || '';
      order.querySelector('[name="delivery_address"]').value = o.dataset.address || '';
      order.querySelector('[name="customer_email"]').value = o.dataset.email || '';
    });
  }

  // Prenotazione videocall: gli orari liberi dipendono da data e regione.
  document.querySelectorAll('[data-booking]').forEach(function (form) {
    var date = form.querySelector('[name="meeting_date"]');
    var time = form.querySelector('[name="meeting_time"]');
    var region = form.querySelector('[name="region"]');
    var hint = form.querySelector('[data-booking-hint]');
    if (!date || !time || !region) return;
    var wanted = time.dataset.old || '';
    var load = function () {
      time.innerHTML = '<option value="">Seleziona</option>';
      if (!date.value || !region.value) {
        hint.textContent = 'Scegli prima la regione e la data.';
        return;
      }
      hint.textContent = 'Cerco gli orari liberi…';
      fetch(form.dataset.booking + '?data=' + encodeURIComponent(date.value) + '&regione=' + encodeURIComponent(region.value), { headers: { Accept: 'application/json' } })
        .then(function (r) { return r.ok ? r.json() : Promise.reject(); })
        .then(function (res) {
          if (!res.bookable) { hint.textContent = 'In questa data non facciamo videocall: scegli un lunedì, mercoledì o venerdì.'; return; }
          if (!res.slots.length) { hint.textContent = 'Nessun orario libero in questa data: provane un\'altra.'; return; }
          res.slots.forEach(function (s) {
            var o = document.createElement('option');
            o.value = s; o.textContent = s; if (s === wanted) o.selected = true;
            time.appendChild(o);
          });
          hint.textContent = res.slots.length + ' orari liberi.';
        })
        .catch(function () { hint.textContent = 'Non riesco a caricare gli orari: riprova tra poco.'; });
    };
    date.addEventListener('change', load);
    region.addEventListener('change', load);
    if (date.value && region.value) load();
  });
})();
