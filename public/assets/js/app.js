(() => {
  'use strict';
  const $ = (s, el = document) => el.querySelector(s);
  const $$ = (s, el = document) => [...el.querySelectorAll(s)];
  const root = document.documentElement;

  // ---------- Theme toggle ----------
  const isDark = () => root.dataset.theme ? root.dataset.theme === 'dark' : matchMedia('(prefers-color-scheme: dark)').matches;
  $$('[data-theme-toggle]').forEach(btn => btn.addEventListener('click', () => {
    const next = isDark() ? 'light' : 'dark';
    root.dataset.theme = next;
    try { localStorage.setItem('theme', next); } catch (e) {}
  }));

  // ---------- Sidebar (mobile) ----------
  $$('[data-sidebar-open]').forEach(b => b.addEventListener('click', () => document.body.classList.add('sidebar-open')));
  $$('[data-sidebar-close]').forEach(b => b.addEventListener('click', () => document.body.classList.remove('sidebar-open')));

  // ---------- User menu: close on outside click ----------
  document.addEventListener('click', e => {
    $$('details.usermenu[open]').forEach(d => { if (!d.contains(e.target)) d.removeAttribute('open'); });
  });

  // ---------- Toasts ----------
  const dismiss = t => { t.classList.add('leaving'); setTimeout(() => t.remove(), 300); };
  $$('.toast').forEach(t => {
    $('.toast-close', t)?.addEventListener('click', () => dismiss(t));
    if (!t.classList.contains('toast-error')) setTimeout(() => dismiss(t), 5000);
  });

  // ---------- Confirm before submit ----------
  document.addEventListener('submit', e => {
    const msg = e.target.dataset.confirm;
    if (msg && !confirm(msg)) e.preventDefault();
  });

  // ---------- Tabs ----------
  $$('[data-tabs]').forEach(tabs => {
    const buttons = $$('[data-tab]', tabs);
    buttons.forEach(b => b.addEventListener('click', () => {
      buttons.forEach(x => {
        x.classList.toggle('active', x === b);
        const p = document.getElementById(x.dataset.tab);
        if (p) p.hidden = x !== b;
      });
    }));
  });

  // ---------- Password reveal ----------
  $$('[data-reveal]').forEach(btn => btn.addEventListener('click', () => {
    const input = document.getElementById(btn.dataset.reveal);
    input.type = input.type === 'password' ? 'text' : 'password';
  }));

  // ---------- Demo login filler ----------
  $$('[data-demo]').forEach(btn => btn.addEventListener('click', () => {
    const [email, pw] = btn.dataset.demo.split('|');
    $('#email').value = email;
    $('#password').value = pw;
    $('#email').form.requestSubmit();
  }));

  // ---------- Auto-submit filters ----------
  $$('[data-autosubmit]').forEach(el => el.addEventListener('change', () => el.form.submit()));

  // ---------- Booking wizard ----------
  const form = $('[data-booking]');
  if (form) booking(form);

  function booking(form) {
    const slotsUrl = form.dataset.slotsUrl;
    const closed = new Set(JSON.parse(form.dataset.closed || '[]'));
    const maxDays = parseInt(form.dataset.maxDays || '60', 10);
    const cal = $('[data-calendar]', form);
    const slotsBox = $('[data-slots]', form);
    const dateInput = $('input[name=date]', form);
    const timeInput = $('input[name=time]', form);
    const submit = $('[data-submit]', form);
    const sum = k => $(`[data-sum="${k}"]`, form);

    const today = new Date(); today.setHours(0, 0, 0, 0);
    const maxDate = new Date(today); maxDate.setDate(maxDate.getDate() + maxDays);
    let view = new Date(today.getFullYear(), today.getMonth(), 1);
    if (dateInput.value) { const d = new Date(dateInput.value + 'T00:00'); view = new Date(d.getFullYear(), d.getMonth(), 1); }

    const ymd = d => `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
    const service = () => $('input[name=service_id]:checked', form);
    const dentist = () => $('input[name=dentist_id]:checked', form);

    function updateSummary() {
      const s = service(), d = dentist();
      sum('service').textContent = s ? s.dataset.name : '—';
      sum('price').textContent = s ? s.dataset.price : '—';
      sum('duration').textContent = s ? s.dataset.duration + ' min' : '—';
      sum('dentist').textContent = d ? d.dataset.name : '—';
      sum('date').textContent = dateInput.value ? new Date(dateInput.value + 'T00:00').toLocaleDateString(undefined, { weekday: 'short', day: 'numeric', month: 'short', year: 'numeric' }) : '—';
      sum('time').textContent = timeInput.dataset.label || '—';
      submit.disabled = !(s && d && dateInput.value && timeInput.value);
    }

    function renderCalendar() {
      const d = dentist();
      if (!service() || !d) {
        cal.innerHTML = '<p class="muted">Choose a treatment and a dentist first.</p>';
        return;
      }
      const workDays = (d.dataset.days || '1,2,3,4,5,6').split(',').map(Number);
      const monthName = view.toLocaleDateString(undefined, { month: 'long', year: 'numeric' });
      const first = (view.getDay() + 6) % 7; // Monday-first
      const daysIn = new Date(view.getFullYear(), view.getMonth() + 1, 0).getDate();
      const canPrev = view > new Date(today.getFullYear(), today.getMonth(), 1);
      const canNext = new Date(view.getFullYear(), view.getMonth() + 1, 1) <= maxDate;

      let html = `<div class="cal-head"><button type="button" class="icon-btn" data-nav="-1" ${canPrev ? '' : 'disabled'} aria-label="Previous month">‹</button><b>${monthName}</b><button type="button" class="icon-btn" data-nav="1" ${canNext ? '' : 'disabled'} aria-label="Next month">›</button></div><div class="cal-grid">`;
      ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'].forEach(w => html += `<div class="cal-dow">${w}</div>`);
      for (let i = 0; i < first; i++) html += '<button type="button" class="cal-day empty-day" disabled></button>';
      for (let day = 1; day <= daysIn; day++) {
        const date = new Date(view.getFullYear(), view.getMonth(), day);
        const key = ymd(date);
        const iso = ((date.getDay() + 6) % 7) + 1;
        const off = date < today || date > maxDate || !workDays.includes(iso) || closed.has(key);
        const cls = ['cal-day', key === ymd(today) ? 'today' : '', key === dateInput.value ? 'selected' : ''].join(' ');
        html += `<button type="button" class="${cls}" data-date="${key}" ${off ? 'disabled' : ''}>${day}</button>`;
      }
      cal.innerHTML = html + '</div>';
    }

    async function loadSlots() {
      timeInput.value = ''; timeInput.dataset.label = '';
      updateSummary();
      if (!dateInput.value || !service() || !dentist()) {
        slotsBox.innerHTML = '<p class="muted">Pick a date to see available times.</p>';
        return;
      }
      slotsBox.innerHTML = '<div class="loading"><span class="spinner"></span>Checking availability…</div>';
      try {
        const params = new URLSearchParams({ dentist: dentist().value, service: service().value, date: dateInput.value });
        const res = await fetch(`${slotsUrl}?${params}`, { headers: { Accept: 'application/json' } });
        const data = await res.json();
        if (data.reason) { slotsBox.innerHTML = `<div class="alert alert-info">${escapeHtml(data.reason)}</div>`; return; }
        const free = data.slots.filter(s => s.available);
        if (!free.length) { slotsBox.innerHTML = '<div class="alert alert-info">Fully booked on this day. Please try another date.</div>'; return; }
        slotsBox.innerHTML = '<div class="slots">' + data.slots.map(s =>
          `<button type="button" class="slot" data-time="${s.time}" data-label="${s.label}" ${s.available ? '' : 'disabled'}>${s.label}</button>`).join('') + '</div>';
        const preset = form.dataset.time;
        if (preset) { $(`.slot[data-time="${preset}"]:not(:disabled)`, slotsBox)?.click(); form.dataset.time = ''; }
      } catch (e) {
        slotsBox.innerHTML = '<div class="alert alert-error">Could not load times. Please refresh and try again.</div>';
      }
    }

    function escapeHtml(s) { return String(s).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c])); }

    cal.addEventListener('click', e => {
      const nav = e.target.closest('[data-nav]');
      if (nav) { view = new Date(view.getFullYear(), view.getMonth() + Number(nav.dataset.nav), 1); renderCalendar(); return; }
      const day = e.target.closest('[data-date]');
      if (day && !day.disabled) { dateInput.value = day.dataset.date; renderCalendar(); loadSlots(); }
    });
    slotsBox.addEventListener('click', e => {
      const s = e.target.closest('.slot');
      if (!s || s.disabled) return;
      $$('.slot', slotsBox).forEach(x => x.classList.toggle('selected', x === s));
      timeInput.value = s.dataset.time;
      timeInput.dataset.label = s.dataset.label;
      updateSummary();
    });
    $$('input[name=service_id], input[name=dentist_id]', form).forEach(r => r.addEventListener('change', () => {
      // Selected day may not be a working day for the new dentist
      const d = dentist();
      if (dateInput.value && d) {
        const iso = ((new Date(dateInput.value + 'T00:00').getDay() + 6) % 7) + 1;
        if (!(d.dataset.days || '').split(',').map(Number).includes(iso)) dateInput.value = '';
      }
      renderCalendar(); loadSlots();
    }));

    renderCalendar();
    if (dateInput.value) loadSlots(); else updateSummary();
  }
})();
