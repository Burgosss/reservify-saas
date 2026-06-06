# Multi-tenancy

Este proyecto implementa multi-tenancy usando una base de datos compartida. Cada registro relacionado con un negocio se asocia a un tenant mediante la columna `tenant_id`.

La lógica principal se divide en tres piezas:

* `IdentifyTenant`
* `TenantScope`
* `BelongsToTenant`

## 1. IdentifyTenant

Archivo:

```txt
app/Http/Middleware/IdentifyTenant.php
```

Este middleware se encarga de identificar a qué tenant pertenece cada request.

Primero intenta resolver el tenant usando el subdominio.

Ejemplo:

```txt
barberia.tuapp.com
```

De ese host se obtiene el primer segmento:

```txt
barberia
```

Ese valor se compara contra la columna `slug` de la tabla `tenants`.

```php
$host = $request->getHost();
$subdomain = explode('.', $host)[0];

$tenant = Tenant::where('slug', $subdomain)
    ->where('is_active', true)
    ->first();
```

En desarrollo local, normalmente no se trabaja con subdominios reales. Por eso existe un fallback usando el header `X-Tenant-Slug`.

```php
if (!$tenant && app()->environment('local')) {
    $slug = $request->header('X-Tenant-Slug');

    if ($slug) {
        $tenant = Tenant::where('slug', $slug)->first();
    }
}
```

Esto permite probar la aplicación desde Postman o desde el frontend local enviando un header como:

```txt
X-Tenant-Slug: barberia
```

Cuando el tenant se encuentra, se guarda temporalmente en el contenedor de Laravel:

```php
app()->instance('current_tenant', $tenant);
```

Esto permite que otras partes del sistema puedan recuperar el tenant actual durante el mismo request usando:

```php
app('current_tenant')
```

El tenant guardado solo vive durante el request actual. En el siguiente request, el proceso comienza de nuevo.

## 2. TenantScope

Archivo:

```txt
app/Scopes/TenantScope.php
```

`TenantScope` es un scope global de Eloquent. Su función es agregar automáticamente un filtro `tenant_id` a las consultas de los modelos que pertenezcan a un tenant.

Ejemplo:

```php
User::where('email', $email)->first();
```

Gracias al scope, esa consulta se convierte en algo equivalente a:

```sql
SELECT * FROM users
WHERE email = ?
AND tenant_id = ?;
```

El tenant se obtiene desde el contenedor de Laravel:

```php
$tenant = App::make('current_tenant');
```

Después se agrega el filtro:

```php
$builder->where(
    $model->getTable() . '.tenant_id',
    $tenant->id
);
```

### Validación para consola

El scope contiene esta validación:

```php
if (App::runningInConsole()) {
    return;
}
```

Esto significa que si Laravel se está ejecutando desde consola, por ejemplo con comandos como:

```bash
php artisan migrate
php artisan db:seed
php artisan tinker
```

el scope no se aplica.

Esto es necesario porque en consola normalmente no existe un request HTTP ni un tenant actual resuelto por `IdentifyTenant`.

El `return` solo sale del método `apply()` del scope. No detiene Laravel ni cancela el comando Artisan.

### Validación cuando no existe current_tenant

El scope también usa un `try/catch` para evitar errores cuando todavía no existe `current_tenant`.

```php
try {
    $tenant = App::make('current_tenant');
} catch (\Exception $e) {
    return;
}
```

Si no hay tenant resuelto, el scope no agrega el filtro y permite que la consulta continúe.

Esto evita errores en procesos internos donde Laravel o Sanctum necesitan consultar modelos antes de que exista un tenant disponible.

## 3. BelongsToTenant

Archivo:

```txt
app/Traits/BelongsToTenant.php
```

`BelongsToTenant` es un trait reutilizable para modelos que pertenecen a un tenant.

Ejemplo:

```php
class User extends Authenticatable
{
    use BelongsToTenant;
}
```

Este trait hace dos cosas principales.

### 1. Registra el scope global

```php
static::addGlobalScope(new TenantScope());
```

Esto hace que todas las consultas del modelo se filtren automáticamente por `tenant_id`.

### 2. Inyecta tenant_id al crear registros

El trait registra un hook `creating`:

```php
static::creating(function ($model) {
    if (! App::runningInConsole() && empty($model->tenant_id)) {
        $model->tenant_id = app('current_tenant')->id;
    }
});
```

Este hook se ejecuta antes de guardar un registro nuevo en la base de datos.

Ejemplo:

```php
User::create([
    'name' => 'Carlos',
    'email' => 'carlos@test.com',
    'password' => 'password',
]);
```

Aunque el controller no envía manualmente `tenant_id`, el trait lo asigna automáticamente antes del `INSERT`.

Resultado lógico:

```txt
name: Carlos
email: carlos@test.com
tenant_id: uuid-del-tenant-actual
```

## Flujo general

```txt
Request HTTP
↓
IdentifyTenant resuelve el tenant
↓
El tenant se guarda como current_tenant
↓
El controller ejecuta consultas o crea registros
↓
TenantScope filtra consultas por tenant_id
↓
BelongsToTenant asigna tenant_id al crear registros
↓
Response
```

## Ejemplo en autenticación

Durante el registro:

```txt
POST /api/auth/register
↓
IdentifyTenant identifica el tenant
↓
User::create()
↓
BelongsToTenant asigna tenant_id
↓
Sanctum genera el token
↓
El backend responde con user y token
```

Durante el login:

```txt
POST /api/auth/login
↓
IdentifyTenant identifica el tenant
↓
User::where('email', ...)
↓
TenantScope agrega WHERE tenant_id = current_tenant
↓
Se valida la contraseña
↓
Sanctum genera el token
```

## Cuándo usar BelongsToTenant

Este trait debe usarse en modelos que pertenecen a un tenant, por ejemplo:

* `User`
* `Booking`
* `Service`
* `Product`
* `Appointment`

No debe usarse en modelos globales o del sistema, por ejemplo:

* `Tenant`
* `Plan`
* `personal_access_tokens`
* `PasswordResetToken`

## Resumen

`IdentifyTenant` identifica el tenant actual.

`TenantScope` filtra consultas automáticamente por `tenant_id`.

`BelongsToTenant` agrega el scope global y asigna `tenant_id` al crear registros.

Esta estructura permite mantener separado el acceso a datos entre tenants sin repetir manualmente filtros en cada controller.
