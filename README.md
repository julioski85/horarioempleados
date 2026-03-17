# Horario Empleados - Sistema de Asistencia (PHP + MySQL)

Aplicación web profesional para control de asistencia con:
- **Modo kiosco** (iPad fija con selfie obligatoria, ID/nombre + PIN)
- **Panel de administración** (empleados, solicitudes, reportes, auditoría)
- **Panel del empleado** (historial y solicitudes)

## 1) Dónde colocar la contraseña de la base de datos (IMPORTANTE)

No dejes la contraseña en el código fuente.

1. Copia `.env.example` a `.env`.
2. Edita `.env` y coloca el valor real en `DB_PASS`.
3. En Hostinger, sube `.env` **fuera de `public_html`** (ideal) o en la raíz del proyecto no pública.

Ejemplo:

```env
DB_HOST=localhost
DB_NAME=u801126150_equipo
DB_USER=u801126150_equipo
DB_PASS=TU_PASSWORD_REAL_AQUI
```

La app carga ese valor desde `config/bootstrap.php` + `src/Core/Env.php`.

## 2) Estructura del proyecto

- `public/` entrada web (`index.php`, assets, uploads)
- `src/` lógica (Core + Controllers)
- `views/` plantillas
- `database/schema.sql` esquema + datos semilla
- `.env.example` variables requeridas

## 3) Instalación en Hostinger (paso a paso)

1. Crear base de datos MySQL:
   - Host: `localhost`
   - DB: `u801126150_equipo`
   - User: `u801126150_equipo`
   - Password: la que asignes
2. Importar `database/schema.sql` desde phpMyAdmin.
3. Subir archivos al hosting.
4. Apuntar dominio/subdominio a carpeta `public/`.
   - Si no es posible, dejar en `public_html` y mover contenido de `public` ahí, ajustando rutas.
5. Crear `.env` con credenciales reales.
6. Permisos:
   - `public/assets/uploads/base` -> 775
   - `public/assets/uploads/selfies` -> 775
7. Probar:
   - `/login`
   - `/kiosk`

## 4) Credenciales iniciales de prueba

- Admin:
  - Email: `admin@gym.local`
  - Password: `Admin123!`
- Empleado demo:
  - Email: `ana@gym.local`
  - Password: `Empleado123!`
  - PIN kiosco: `1234`

## 5) Módulos incluidos

- Login seguro admin/empleado con hash
- CSRF en formularios
- Kiosco con identificación + PIN + selfie + secuencia entrada/salida automática
- Dashboard admin con KPIs
- Gestión de empleados
- Solicitudes vacaciones/permisos y autorización admin
- Reporte con exportación CSV
- Bitácora de auditoría
- Modelo de datos preparado para tolerancias, turnos, incidencias y escalabilidad

## 6) Seguridad aplicada

- Password hash con `password_hash()`
- Validación CSRF
- PDO con prepared statements
- Validación de PIN y tamaño selfie
- Control de acceso por rol
- Auditoría de eventos
- Anulación lógica en asistencias (`is_void`)

## 7) Siguientes mejoras recomendadas

- Exportación Excel (XLSX con PhpSpreadsheet)
- PDF
- Motor de reglas más avanzado para turnos múltiples y tolerancias por área
- Comparación facial asistida (servicio externo)
- Jobs programados para marcar faltas automáticas
