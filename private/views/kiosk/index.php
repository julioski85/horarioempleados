<div class="kiosk-shell">
  <div class="kiosk-card">
    <h1>Registrar asistencia</h1>
    <p>Busca por nombre o ID, luego confirma con PIN.</p>
    <div class="kiosk-search">
      <input id="kiosk-search" placeholder="Buscar empleado">
      <button class="btn" onclick="kioskSearch()">Buscar</button>
    </div>
    <div id="kiosk-employee" class="kiosk-employee">
      <img src="/assets/uploads/base/avatar-base.svg" alt="avatar">
      <div><strong>Empleado</strong><span>Sin seleccionar</span></div>
    </div>
    <div class="pin-grid" id="pin-grid"></div>
    <button class="btn btn-cta" onclick="kioskRegister()">Confirmar asistencia</button>
    <input type="hidden" id="employee-id">
    <input type="hidden" id="pin-value">
  </div>
</div>
