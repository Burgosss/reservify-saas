# Laravel Sanctum con UUIDs

## Contexto

Laravel Sanctum guarda los tokens de acceso personal en la tabla `personal_access_tokens`.

Esta tabla utiliza una relación polimórfica llamada `tokenable`, la cual permite asociar un token con un modelo autenticable, normalmente `App\Models\User`.

## Configuración usada

```php
$table->uuidMorphs('tokenable');
```

Esta instrucción crea dos columnas:

```txt
tokenable_id
tokenable_type
```

## ¿Qué guarda cada columna?

`tokenable_id` guarda el ID del modelo autenticado.

Ejemplo:

```txt
019e75a1-a303-72a8-aadf-23b5698edca4
```

`tokenable_type` guarda el nombre del modelo relacionado.

Ejemplo:

```txt
App\Models\User
```

## ¿Por qué se usa uuidMorphs?

Por defecto, Sanctum puede usar:

```php
$table->morphs('tokenable');
```

Pero esa opción crea `tokenable_id` como un campo numérico, pensado para IDs incrementales como `1`, `2` o `3`.

En este proyecto, el modelo `User` utiliza UUID como llave primaria. Por eso se usa:

```php
$table->uuidMorphs('tokenable');
```

De esta forma, `tokenable_id` puede almacenar correctamente IDs en formato UUID.

## Resumen

Sanctum no usa `uuidMorphs` por los tenants directamente. Lo usa porque el modelo autenticable, en este caso `User`, tiene un ID tipo UUID.

Flujo:

```txt
User usa UUID
↓
Sanctum genera un token para ese User
↓
personal_access_tokens necesita guardar el UUID del User
↓
se usa uuidMorphs('tokenable')
```
