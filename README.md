# Reservify Backend — Laravel 11

API REST para sistema de agendamiento multi-tenant. Gestiona autenticación, aislamiento de datos por tenant, servicios, horarios y reservas con detección automática de conflictos.

**Stack:** Laravel 11 · Sanctum · MySQL · UUIDs

---

## Features

- ✅ **Multi-tenancy row-level** — Múltiples negocios en 1 BD, datos completamente aislados
- ✅ **Autenticación Sanctum** — Tokens JWT con UUIDs como llave primaria
- ✅ **API REST completa** — 25+ endpoints documentados
- ✅ **Detección de conflictos** — Impide reservar horarios ocupados
- ✅ **Roles y permisos** — Owner, Staff, Client con acceso diferenciado
- ✅ **Global Scopes** — Filtrado automático por tenant en todas las queries
- ✅ **Seeders demo** — 2 tenants con datos reales para testing
- ✅ **Identificación dinámica de tenant** — Subdominio (prod) o header (dev)
- ✅ **Error handling** — Respuestas HTTP estándar con códigos apropiados

---

## Cómo Correr Localmente

### Opción 1: Sin Docker (XAMPP + PHP local)

#### Requisitos
- PHP 8.2+
- Composer
- MySQL 8.0
- XAMPP o similar

#### Setup

```bash
cd reservify

# 1. Instalar dependencias
composer install

# 2. Configurar .env
cp .env.example .env

# Edita .env:
DB_HOST=127.0.0.1
DB_DATABASE=reservify
DB_USERNAME=root
DB_PASSWORD=

# 3. Generar app key
php artisan key:generate

# 4. Migrar y seedear
php artisan migrate:fresh --seed

# 5. Levantar servidor
php artisan serve
```

Backend corre en `http://localhost:8000`.

---

### Opción 2: Con Docker (recomendado)

#### Requisitos
- Docker
- Docker Compose

#### Setup

```bash
cd reservify

# 1. Construir imagen
docker-compose build

# 2. Levantar contenedores
docker-compose up -d

# 3. Instalar dependencias
docker-compose exec app composer install

# 4. Configurar .env
docker-compose exec app cp .env.example .env
docker-compose exec app php artisan key:generate

# 5. Migrar y seedear
docker-compose exec app php artisan migrate:fresh --seed

# 6. Verificar que está corriendo
curl http://localhost:8000/api/auth/me
```

Backend corre en `http://localhost:8000`.

Para detener:
```bash
docker-compose down
```

---

## Docker Compose

**Archivo:** `docker-compose.yml`

```yaml
version: '3.8'

services:
  app:
    build:
      context: .
      dockerfile: Dockerfile
    container_name: reservify-app
    restart: unless-stopped
    working_dir: /app
    environment:
      - DB_HOST=mysql
      - DB_DATABASE=reservify
      - DB_USERNAME=reservify
      - DB_PASSWORD=secret
    ports:
      - "8000:8000"
    volumes:
      - ./:/app
    depends_on:
      - mysql
    command: php artisan serve --host=0.0.0.0

  mysql:
    image: mysql:8.0
    container_name: reservify-mysql
    restart: unless-stopped
    environment:
      MYSQL_DATABASE: reservify
      MYSQL_ROOT_PASSWORD: root
      MYSQL_PASSWORD: secret
      MYSQL_USER: reservify
    ports:
      - "3306:3306"
    volumes:
      - mysql_data:/var/lib/mysql
    healthcheck:
      test: ["CMD", "mysqladmin", "ping", "-h", "localhost"]
      timeout: 20s
      retries: 10

volumes:
  mysql_data:
    driver: local
```

---

## Credenciales Demo

### Barbería López (`barberia-lopez`)

| Rol | Email | Contraseña |
|-----|-------|-----------|
| Owner | `owner@barberia.com` | `password123` |
| Staff | `miguel@barberia.com` | `password123` |
| Client | `carlos@barberia.com` | `password123` |

### Clínica Pérez (`clinica-perez`)

| Rol | Email | Contraseña |
|-----|-------|-----------|
| Owner | `owner@clinica.com` | `password123` |
| Staff | `juan@clinica.com` | `password123` |
| Client | `maria@clinica.com` | `password123` |

---

## Estructura del Proyecto

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── Auth/
│   │   │   └── AuthController.php       # Login, registro, logout
│   │   ├── BookingController.php        # CRUD reservas + conflictos
│   │   ├── ServiceController.php        # CRUD servicios
│   │   ├── StaffScheduleController.php  # CRUD horarios
│   │   └── UserController.php           # Listados de staff
│   └── Middleware/
│       ├── IdentifyTenant.php           # Resuelve tenant del request
│       └── CheckRole.php                # Valida roles
│
├── Models/
│   ├── Tenant.php                       # Negocio/empresa
│   ├── User.php                         # Usuario (owner, staff, client)
│   ├── Booking.php                      # Reserva
│   ├── Service.php                      # Servicio ofrecido
│   └── StaffSchedule.php                # Horario de disponibilidad
│
├── Scopes/
│   └── TenantScope.php                  # Filtra queries por tenant_id
│
├── Traits/
│   └── BelongsToTenant.php              # Inyecta tenant_id automáticamente
│
└── Exceptions/
    └── Handler.php

database/
├── migrations/
│   ├── 2024_01_01_create_tenants_table.php
│   ├── 2024_01_02_create_users_table.php
│   ├── 2024_01_03_create_services_table.php
│   ├── 2024_01_04_create_staff_schedules_table.php
│   ├── 2024_01_05_create_bookings_table.php
│   └── 2024_01_06_create_personal_access_tokens_table.php
│
└── seeders/
    └── TenantSeeder.php                 # 2 tenants demo con datos

routes/
└── api.php                              # Todas las rutas de la API

bootstrap/
└── app.php                              # Registro de middleware
```

---

## Endpoints Principales

### Autenticación

| Método | Ruta | Descripción | Auth |
|--------|------|-------------|------|
| POST | `/auth/register` | Crear nueva cuenta cliente | ❌ |
| POST | `/auth/login` | Iniciar sesión | ❌ |
| POST | `/auth/logout` | Cerrar sesión | ✅ |
| GET | `/auth/me` | Obtener usuario actual | ✅ |

### Servicios

| Método | Ruta | Descripción | Auth | Roles |
|--------|------|-------------|------|-------|
| GET | `/services` | Listar servicios del tenant | ✅ | todos |
| GET | `/services/{id}` | Obtener un servicio | ✅ | todos |
| POST | `/services` | Crear servicio | ✅ | owner, staff |
| PUT | `/services/{id}` | Actualizar servicio | ✅ | owner, staff |
| DELETE | `/services/{id}` | Eliminar servicio | ✅ | owner, staff |

### Reservas

| Método | Ruta | Descripción | Auth | Roles |
|--------|------|-------------|------|-------|
| GET | `/bookings` | Listar reservas | ✅ | owner, staff |
| POST | `/bookings` | Crear reserva | ✅ | client |
| GET | `/bookings/{id}` | Obtener una reserva | ✅ | owner, staff, client |
| PUT | `/bookings/{id}` | Actualizar estado | ✅ | owner, staff |
| GET | `/bookings/available-slots` | Horarios libres | ✅ | todos |
| GET | `/dashboard/stats` | KPIs del dashboard | ✅ | owner, staff |

### Horarios

| Método | Ruta | Descripción | Auth | Roles |
|--------|------|-------------|------|-------|
| GET | `/staff-schedules` | Listar horarios | ✅ | todos |
| POST | `/staff-schedules` | Crear horario | ✅ | owner, staff |
| PUT | `/staff-schedules/{id}` | Actualizar horario | ✅ | owner, staff |
| DELETE | `/staff-schedules/{id}` | Eliminar horario | ✅ | owner, staff |

### Usuarios

| Método | Ruta | Descripción | Auth | Roles |
|--------|------|-------------|------|-------|
| GET | `/users/staff` | Listar staff del tenant | ✅ | owner, staff |

---

## Flujos Importantes

### Crear una Reserva

```
POST /api/bookings
Header: Authorization: Bearer {token}
Header: X-Tenant-Slug: barberia-lopez

{
  "service_id": "uuid-del-servicio",
  "staff_user_id": "uuid-del-staff",
  "starts_at": "2026-06-23 14:00:00",
  "notes": "Primera visita"
}

Response 201:
{
  "id": "uuid-nueva-reserva",
  "status": "pending",
  "service_id": "...",
  "starts_at": "2026-06-23 14:00:00",
  "ends_at": "2026-06-23 14:30:00"
}
```

**Validaciones:**
- Usuario autenticado
- `service_id` existe y pertenece al tenant
- `staff_user_id` es staff del tenant
- Horario no está ocupado (no hay conflicto)
- Horario está dentro de disponibilidad del staff

### Confirmar una Reserva (Owner/Staff)

```
PUT /api/bookings/{id}
Header: Authorization: Bearer {token}

{
  "status": "confirmed"
}

Response 200:
{
  "id": "...",
  "status": "confirmed"
}
```

Estados válidos: `pending`, `confirmed`, `completed`, `cancelled`

---

## Arquitectura Multi-Tenancy

### Cómo Funciona

```
Request: POST /api/bookings
         Header: X-Tenant-Slug: barberia-lopez

         ↓

Middleware IdentifyTenant:
- Lee header X-Tenant-Slug
- Busca tenant en BD: WHERE slug = 'barberia-lopez'
- Guarda en contenedor: app('current_tenant')

         ↓

Controller BookingController:
- Llama: Booking::create([...])

         ↓

Trait BelongsToTenant (hook 'creating'):
- Lee app('current_tenant')
- Asigna: $model->tenant_id = current_tenant.id

         ↓

Scope TenantScope (aplicado automáticamente):
- Si fuera un SELECT, agregaría: WHERE tenant_id = current_tenant.id

         ↓

Result:
INSERT INTO bookings (service_id, staff_user_id, ..., tenant_id)
VALUES (?, ?, ..., uuid-de-barberia-lopez)
```

### Garantías de Seguridad

 **No hay forma de ver datos de otro tenant** — TenantScope lo impide a nivel BD

 **No hay forma de asignar a otro tenant manualmente** — El hook lo sobrescribe

 **Aislamiento completo** — Un cliente de Barbería nunca ve datos de Clínica

---

## Testing Manual

### Con Postman

**1. Login**
```
POST http://localhost:8000/api/auth/login
Header: X-Tenant-Slug: barberia-lopez

{
  "email": "miguel@barberia.com",
  "password": "password123"
}

Response:
{
  "user": {...},
  "token": "2|abc123..."
}
```

**2. Guardar token en variable**
- En Postman: Collections → Variables → `token`
- Pega el token recibido

**3. Usar token en siguiente request**
```
GET http://localhost:8000/api/services
Header: Authorization: Bearer {{token}}
Header: X-Tenant-Slug: barberia-lopez
```

**4. Ver aislamiento: cambiar tenant**
```
GET http://localhost:8000/api/services
Header: Authorization: Bearer {{token}}
Header: X-Tenant-Slug: clinica-perez

Response: Servicios de la clínica (diferentes)
```

---

## Comandos Útiles

```bash
# Migrar
php artisan migrate

# Rollback migraciones
php artisan migrate:rollback

# Fresh (borra todo y recrea)
php artisan migrate:fresh --seed

# Ver rutas
php artisan route:list

# Tinker (consola interactiva)
php artisan tinker

# Con Docker
docker-compose exec app php artisan migrate:fresh --seed
docker-compose exec app php artisan tinker
```

---

## Documentación Técnica

Para profundizar en multi-tenancy, ver [`docs/multi-tenancy.md`](docs/multi-tenancy.md).

Temas cubiertos:
- Global Scopes en detalle
- Hooks de Eloquent
- Identificación de tenant
- Edge cases y fallbacks
- Testing de aislamiento

---

## Deploy a Producción

### Requisitos
- Servidor con PHP 8.2+
- MySQL 8.0+
- Composer
- Redis (opcional, para cache)
- SSL/TLS (HTTPS)

### Pasos

```bash
# 1. Clonar repo
git clone <repo> reservify
cd reservify

# 2. Instalar dependencias
composer install --no-dev --optimize-autoloader

# 3. Configurar .env
cp .env.example .env
# Editar: DB credentials, APP_KEY, subdominio, etc.

# 4. Generar key
php artisan key:generate

# 5. Migrar BD
php artisan migrate --force

# 6. Cache configuración
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 7. Servir con Nginx/Apache
# Apuntar document root a /public
```

### Con Docker en producción

```bash
docker build -t reservify:latest .
docker run -d \
  -e DB_HOST=mysql.prod.com \
  -e DB_DATABASE=reservify \
  -e DB_USERNAME=user \
  -e DB_PASSWORD=pass \
  -e APP_ENV=production \
  -p 8000:8000 \
  reservify:latest
```

---

## Variables de Entorno

```env
APP_NAME=Reservify
APP_ENV=local
APP_DEBUG=true
APP_KEY=                          # php artisan key:generate

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=reservify
DB_USERNAME=root
DB_PASSWORD=

SANCTUM_STATEFUL_DOMAINS=localhost:3000,localhost:9000

MAIL_MAILER=log
MAIL_FROM_ADDRESS=no-reply@reservify.local

SESSION_DRIVER=file
```

---

## Base de Datos

### Diagrama ER simplificado

```
tenants
  ├─ id (UUID)
  ├─ slug
  └─ name

users
  ├─ id (UUID)
  ├─ tenant_id (FK)
  ├─ name
  ├─ email
  ├─ role (owner|staff|client)
  └─ password

services
  ├─ id (UUID)
  ├─ tenant_id (FK)
  ├─ name
  ├─ duration_min
  └─ price

staff_schedules
  ├─ id (UUID)
  ├─ tenant_id (FK)
  ├─ user_id (FK → users)
  ├─ day_of_week (0-6)
  ├─ start_time
  └─ end_time

bookings
  ├─ id (UUID)
  ├─ tenant_id (FK)
  ├─ service_id (FK)
  ├─ staff_user_id (FK → users)
  ├─ client_user_id (FK → users)
  ├─ starts_at
  ├─ ends_at
  ├─ status (pending|confirmed|completed|cancelled)
  └─ notes
```

---

## Troubleshooting

### Error: "Target class [current_tenant] does not exist"

→ El tenant no se identificó correctamente. Verifica:
1. Header `X-Tenant-Slug` está siendo enviado
2. El slug existe en tabla `tenants`
3. El tenant tiene `is_active = true`

### Error: "Field 'tenant_id' doesn't have a default value"

→ El trait BelongsToTenant no inyectó el tenant_id. Verifica:
1. El modelo usa el trait
2. El modelo tiene `tenant_id` en `$fillable` (o no está usando `$fillable`)
3. `app('current_tenant')` existe

### Error: 403 Forbidden

→ Usuario no tiene permiso. Verifica:
1. El endpoint requiere rol específico
2. El usuario tiene ese rol
3. El usuario pertenece al tenant correcto

---

## Licencia

MIT

---

## Autor

Proyecto portfolio personal construido para demostrar:
- Arquitectura multi-tenant production-ready
- Aislamiento de datos en Laravel
- API REST profesional
- Buenas prácticas de seguridad

**Tiempo total:** ~3 semanas de desarrollo part-time.
