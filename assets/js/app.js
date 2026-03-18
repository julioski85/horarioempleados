(function () {
  const base = window.APP_BASE_PATH || '';
  const toUrl = (path) => base + path;
  window.toUrl = toUrl;

  const root = document.documentElement;
  const themeButtons = document.querySelectorAll('[data-theme-toggle]');
  const setTheme = (theme) => {
    root.setAttribute('data-theme', theme);
    try {
      localStorage.setItem('app-theme', theme);
    } catch (e) {
      // no-op
    }
    themeButtons.forEach((button) => {
      button.textContent = theme === 'dark' ? '☀️ Modo claro' : '🌙 Modo oscuro';
    });
  };
  const currentTheme = root.getAttribute('data-theme') || 'light';
  setTheme(currentTheme);
  themeButtons.forEach((button) => {
    button.addEventListener('click', () => {
      const nextTheme = (root.getAttribute('data-theme') || 'light') === 'dark' ? 'light' : 'dark';
      setTheme(nextTheme);
    });
  });

  const pinGrid = document.getElementById('pin-grid');
  if (pinGrid) {
    const pinValue = document.getElementById('pin-value');
    const pinPreview = document.getElementById('pin-preview');
    const updatePinPreview = () => {
      const visiblePin = '*'.repeat(pinValue.value.length);
      pinPreview.textContent = 'PIN ingresado: ' + (visiblePin || '••••••');
    };

    [...Array(10).keys(), '←'].forEach((n) => {
      const b = document.createElement('button');
      b.type = 'button';
      b.className = 'pin-key';
      b.textContent = n;
      b.onclick = () => {
        if (n === '←') {
          pinValue.value = pinValue.value.slice(0, -1);
        } else if (pinValue.value.length < 6) {
          pinValue.value += String(n);
        }
        updatePinPreview();
      };
      pinGrid.appendChild(b);
    });

    updatePinPreview();
  }
})();

async function kioskSearch() {
  const q = document.getElementById('kiosk-search').value;
  const res = await fetch((window.toUrl ? window.toUrl('/kiosk/search') : '/kiosk/search') + '?q=' + encodeURIComponent(q));
  const data = await res.json();
  if (!data.employee) return alert('No encontrado');
  document.getElementById('employee-id').value = data.employee.id;
  document.querySelector('#kiosk-employee strong').textContent = data.employee.full_name;
  document.querySelector('#kiosk-employee span').textContent = data.employee.status;
  if (data.employee.photo_path) {
    document.querySelector('#kiosk-employee img').src = (window.toUrl ? window.toUrl('') : '') + data.employee.photo_path;
  }
}

async function kioskRegister() {
  const payload = new URLSearchParams();
  payload.set('employee_id', document.getElementById('employee-id').value);
  payload.set('pin', document.getElementById('pin-value').value);
  const res = await fetch(window.toUrl ? window.toUrl('/kiosk/register') : '/kiosk/register', { method: 'POST', body: payload });
  const data = await res.json();
  alert(data.message || 'Listo');
}
