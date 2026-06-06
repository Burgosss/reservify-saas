## Registro de middleware en Laravel 11

Archivo:
bootstrap/app.php

Uso:
Aquí se registran alias de middleware.

Ejemplo:
'identify.tenant' => IdentifyTenant::class

Sirve para:
Poder usar ->middleware('identify.tenant') en rutas.

Nota:
