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
  setTheme(root.getAttribute('data-theme') || 'light');
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
      pinPreview.textContent = 'PIN ingresado: ' + ('*'.repeat(pinValue.value.length) || '••••••');
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

let kioskDebounceTimer = null;
let kioskCameraStream = null;
let selectedEmployee = null;

function kioskSetFeedback(message, tone) {
  const feedback = document.getElementById('kiosk-feedback');
  if (!feedback) return;
  feedback.className = 'kiosk-feedback ' + (tone || '');
  feedback.textContent = message;
}

async function kioskSearch() {
  const input = document.getElementById('kiosk-search');
  const q = (input?.value || '').trim();
  if (!q) {
    renderKioskSuggestions([]);
    kioskSetFeedback('Escribe al menos un nombre o ID.', 'warn');
    return;
  }
  const res = await fetch((window.toUrl ? window.toUrl('/kiosk/search') : '/kiosk/search') + '?q=' + encodeURIComponent(q));
  const data = await res.json();
  renderKioskSuggestions(data.employees || []);
  if (!data.employees || data.employees.length === 0) {
    kioskSetFeedback('No se encontraron empleados con ese texto.', 'warn');
  }
}

function renderKioskSuggestions(items) {
  const box = document.getElementById('kiosk-suggestions');
  if (!box) return;
  box.innerHTML = '';
  if (!items.length) return;
  items.forEach((employee) => {
    const button = document.createElement('button');
    button.type = 'button';
    button.className = 'kiosk-suggestion';
    button.innerHTML = '<strong>' + escapeHtml(employee.full_name) + '</strong><span>' + escapeHtml(employee.short_id || ('ID ' + employee.id)) + ' · ' + escapeHtml(employee.status) + '</span>';
    button.addEventListener('click', () => kioskSelectEmployee(employee));
    box.appendChild(button);
  });
}

async function kioskSelectEmployee(employee) {
  selectedEmployee = employee;
  document.getElementById('employee-id').value = employee.id;
  document.querySelector('#kiosk-employee strong').textContent = employee.full_name;
  document.querySelector('#kiosk-employee span').textContent = employee.status;
  document.querySelector('#kiosk-employee img').src = (window.toUrl ? window.toUrl('') : '') + (employee.photo_path || '/assets/uploads/base/avatar-base.svg');
  renderKioskSuggestions([]);

  const res = await fetch((window.toUrl ? window.toUrl('/kiosk/next-action') : '/kiosk/next-action') + '?employee_id=' + encodeURIComponent(employee.id));
  const data = await res.json();
  const shiftNode = document.getElementById('kiosk-shift');
  if (data.shift) {
    shiftNode.textContent = 'Turno esperado: ' + data.shift.start_time.slice(0, 5) + ' - ' + data.shift.end_time.slice(0, 5) + ' · Próxima marca: ' + data.action;
  } else {
    shiftNode.textContent = 'Turno: no disponible';
  }
  kioskSetFeedback(data.validation?.ui_message || 'Empleado seleccionado.', data.validation?.allowed ? 'ok' : 'warn');
}

function kioskBindSearchInput() {
  const input = document.getElementById('kiosk-search');
  if (!input) return;
  input.addEventListener('input', () => {
    clearTimeout(kioskDebounceTimer);
    kioskDebounceTimer = setTimeout(() => {
      kioskSearch().catch(() => kioskSetFeedback('No se pudo buscar empleados.', 'error'));
    }, 220);
  });
}

async function kioskStartCamera() {
  const video = document.getElementById('selfie-video');
  if (!video) return;
  try {
    if (kioskCameraStream) {
      video.srcObject = kioskCameraStream;
      return;
    }
    kioskCameraStream = await navigator.mediaDevices.getUserMedia({
      video: { facingMode: { ideal: 'user' }, width: { ideal: 720 }, height: { ideal: 1280 } },
      audio: false,
    });
    video.srcObject = kioskCameraStream;
    kioskSetFeedback('Cámara activa. Toma la selfie para continuar.', 'ok');
  } catch (e) {
    kioskSetFeedback('No se pudo activar la cámara. Verifica permisos en Safari.', 'error');
  }
}

function kioskTakeSelfie() {
  const video = document.getElementById('selfie-video');
  const preview = document.getElementById('selfie-preview');
  const hidden = document.getElementById('selfie-data');
  if (!video || !preview || !hidden || !video.videoWidth) {
    kioskSetFeedback('Activa la cámara antes de tomar la selfie.', 'warn');
    return;
  }
  const canvas = document.createElement('canvas');
  canvas.width = video.videoWidth;
  canvas.height = video.videoHeight;
  const ctx = canvas.getContext('2d');
  ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
  const dataUrl = canvas.toDataURL('image/jpeg', 0.9);
  hidden.value = dataUrl;
  preview.src = dataUrl;
  preview.style.display = 'block';
  kioskSetFeedback('Selfie capturada correctamente.', 'ok');
}

async function kioskRegister() {
  const employeeId = document.getElementById('employee-id').value;
  const pin = document.getElementById('pin-value').value;
  const selfieData = document.getElementById('selfie-data').value;
  if (!employeeId) return kioskSetFeedback('Selecciona un empleado.', 'warn');
  if (!pin) return kioskSetFeedback('Ingresa el PIN.', 'warn');
  if (!selfieData) return kioskSetFeedback('La selfie es obligatoria antes de confirmar.', 'warn');

  const payload = new URLSearchParams();
  payload.set('employee_id', employeeId);
  payload.set('pin', pin);
  payload.set('selfie_data', selfieData);
  const res = await fetch(window.toUrl ? window.toUrl('/kiosk/register') : '/kiosk/register', { method: 'POST', body: payload });
  const data = await res.json();
  kioskSetFeedback(data.message || 'Listo', data.ok ? 'ok' : 'error');
  if (data.ok) {
    document.getElementById('pin-value').value = '';
    document.getElementById('selfie-data').value = '';
    document.getElementById('selfie-preview').style.display = 'none';
    document.getElementById('pin-preview').textContent = 'PIN ingresado: ••••••';
  }
}

function escapeHtml(value) {
  return String(value)
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&#39;');
}

document.addEventListener('DOMContentLoaded', () => {
  kioskBindSearchInput();
});

window.kioskSearch = kioskSearch;
window.kioskRegister = kioskRegister;
window.kioskStartCamera = kioskStartCamera;
window.kioskTakeSelfie = kioskTakeSelfie;
