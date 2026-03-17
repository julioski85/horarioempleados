(function () {
  const base = window.APP_BASE_PATH || '';
  const toUrl = (path) => base + path;
  window.toUrl = toUrl;

  const pinGrid = document.getElementById('pin-grid');
  if (pinGrid) {
    const pinValue = document.getElementById('pin-value');
    [...Array(10).keys(), '←'].forEach((n) => {
      const b = document.createElement('button');
      b.className = 'pin-key';
      b.textContent = n;
      b.onclick = () => {
        if (n === '←') {
          pinValue.value = pinValue.value.slice(0, -1);
        } else if (pinValue.value.length < 6) {
          pinValue.value += String(n);
        }
      };
      pinGrid.appendChild(b);
    });
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
}

async function kioskRegister() {
  const payload = new URLSearchParams();
  payload.set('employee_id', document.getElementById('employee-id').value);
  payload.set('pin', document.getElementById('pin-value').value);
  const res = await fetch(window.toUrl ? window.toUrl('/kiosk/register') : '/kiosk/register', { method: 'POST', body: payload });
  const data = await res.json();
  alert(data.message || 'Listo');
}
