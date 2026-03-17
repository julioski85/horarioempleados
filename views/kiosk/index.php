<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Modo kiosco</title><link rel="stylesheet" href="/assets/css/app.css"></head><body class="kiosk"><main class="kiosk-wrap">
<h1>Control de asistencia</h1>
<p>Modo kiosco fijo</p>
<div class="card">
<label>Buscar por nombre o ID</label>
<input id="employeeSearch" placeholder="Ej. ANA o E001">
<div id="matches"></div>
<div id="selected"></div>
<label>PIN de 4 dígitos</label><input id="pin" maxlength="4" pattern="\\d{4}" inputmode="numeric">
<video id="camera" autoplay playsinline></video>
<canvas id="snapshot" style="display:none"></canvas>
<button id="captureBtn">Tomar selfie</button>
<button id="registerBtn" disabled>Registrar</button>
<p id="message"></p>
</div>
<a href="/login">Volver</a>
</main><script src="/assets/js/kiosk.js"></script></body></html>
