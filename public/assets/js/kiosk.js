const searchInput = document.getElementById('employeeSearch');
const matches = document.getElementById('matches');
const selected = document.getElementById('selected');
const pinInput = document.getElementById('pin');
const camera = document.getElementById('camera');
const snapshot = document.getElementById('snapshot');
const captureBtn = document.getElementById('captureBtn');
const registerBtn = document.getElementById('registerBtn');
const message = document.getElementById('message');

let selectedEmployee = null;
let selfieData = null;

navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user' }, audio: false }).then((stream) => {
  camera.srcObject = stream;
});

searchInput?.addEventListener('input', async (e) => {
  if (e.target.value.trim().length < 2) return;
  const res = await fetch(`/kiosk/search?q=${encodeURIComponent(e.target.value)}`);
  const data = await res.json();
  matches.innerHTML = data.data.map((u) => `<button data-id="${u.id}" data-name="${u.full_name}" data-photo="${u.base_photo_path || ''}">${u.full_name} (${u.short_id})</button>`).join('');
});

matches?.addEventListener('click', async (e) => {
  if (e.target.tagName !== 'BUTTON') return;
  selectedEmployee = { id: e.target.dataset.id, name: e.target.dataset.name };
  selected.innerHTML = `<p><strong>${selectedEmployee.name}</strong></p>`;
  const n = await fetch(`/kiosk/next-action?employee_id=${selectedEmployee.id}`);
  const nData = await n.json();
  registerBtn.textContent = nData.next_action === 'entry' ? 'Registrar entrada' : 'Registrar salida';
  checkReady();
});

captureBtn?.addEventListener('click', () => {
  snapshot.width = camera.videoWidth;
  snapshot.height = camera.videoHeight;
  const ctx = snapshot.getContext('2d');
  ctx.drawImage(camera, 0, 0);
  selfieData = snapshot.toDataURL('image/jpeg', 0.75);
  message.textContent = 'Selfie capturada';
  checkReady();
});

pinInput?.addEventListener('input', checkReady);

function checkReady() {
  registerBtn.disabled = !(selectedEmployee && /^\d{4}$/.test(pinInput.value) && selfieData);
}

registerBtn?.addEventListener('click', async () => {
  const res = await fetch('/kiosk/register', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ employee_id: selectedEmployee.id, pin: pinInput.value, selfie: selfieData })
  });

  const data = await res.json();
  message.textContent = data.message || 'Error';
  if (data.ok) {
    setTimeout(() => window.location.reload(), 3500);
  }
});
