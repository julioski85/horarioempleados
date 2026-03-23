<div class="kiosk-shell">
  <div class="kiosk-card">
    <div class="kiosk-head">
      <div>
        <h1>Registrar asistencia</h1>
        <p>Busca por nombre o ID, luego confirma con PIN.</p>
      </div>
      <button type="button" class="theme-toggle" data-theme-toggle aria-label="Cambiar tema">🌙 Tema</button>
    </div>
    <div class="kiosk-search">
      <input id="kiosk-search" placeholder="Buscar empleado por nombre o ID" autocomplete="off">
      <button class="btn btn-primary" type="button" onclick="kioskSearch()">Buscar</button>
    </div>
    <div id="kiosk-suggestions" class="kiosk-suggestions"></div>
    <div id="kiosk-employee" class="kiosk-employee">
      <img src="<?= htmlspecialchars(($base_path ?? '') . '/assets/uploads/base/avatar-base.svg') ?>" alt="avatar">
      <div><strong>Empleado</strong><span>Sin seleccionar</span><small id="kiosk-shift">Turno: sin turno</small></div>
    </div>
    <p id="kiosk-feedback" class="kiosk-feedback">Busca y selecciona un empleado para continuar.</p>
    <div class="pin-grid" id="pin-grid"></div>
    <p id="pin-preview" class="pin-preview">PIN ingresado: ••••••</p>
    <div class="selfie-box">
      <div class="selfie-head">
        <strong>Selfie obligatoria</strong>
        <small>Se toma en este momento con cámara frontal.</small>
      </div>
      <div class="selfie-stage">
        <video id="selfie-video" class="selfie-video" autoplay playsinline muted></video>
        <img id="selfie-preview" class="selfie-preview" alt="Vista previa selfie">
      </div>
      <div class="selfie-actions">
        <button class="btn" type="button" onclick="kioskStartCamera()">Activar cámara</button>
        <button class="btn btn-primary" type="button" onclick="kioskTakeSelfie()">Tomar selfie</button>
      </div>
      <small class="selfie-note">Nota iPad/Safari: se usa la cámara en vivo (getUserMedia + cámara frontal). Safari no permite bloquear la galería al 100% en todos los escenarios.</small>
    </div>
    <button type="button" class="btn btn-cta" onclick="kioskRegister()">Confirmar asistencia</button>
    <input type="hidden" id="employee-id">
    <input type="hidden" id="pin-value">
    <input type="hidden" id="selfie-data">
  </div>
</div>
