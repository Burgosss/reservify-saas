<?php

namespace App\Traits;

use App\Scopes\TenantScope;
use Illuminate\Support\Facades\App;
use App\Models\Tenant;
use Illuminate\Support\Facades\Log;

/** @mixin \Illuminate\Database\Eloquent\Model */
trait BelongsToTenant
{
    /**
     * Laravel llama bootNombreDelTrait() automáticamente cuando
     * inicializa un modelo que usa este trait.
     * El prefijo "boot" + nombre del trait es una convención de Laravel.
     */
    public static function bootBelongsToTenant(): void
    {
        // Registra el TenantScope como scope global del modelo.
        // A partir de aquí, cada query de este modelo pasa por TenantScope::apply().
        static::addGlobalScope(new TenantScope());

        // Hook que se ejecuta ANTES de guardar un registro nuevo en la DB.
        // Su trabajo: inyectar tenant_id automáticamente.
        // Así el controller nunca necesita escribir $booking->tenant_id = ...
        static::creating(function ($model) {
            if (! App::runningInConsole() && empty($model->tenant_id)) {
                // Intentar desde el contenedor
                try {
                    $tenant = App::make('current_tenant');
                    if ($tenant) {
                        $model->tenant_id = $tenant->id;
                        return;
                    }
                } catch (\Exception $e) {
                    // No está en el contenedor
                }

                // Fallback: leer el slug del header y buscar el tenant directo
                $slug = request()->header('X-Tenant-Slug');
                if ($slug) {
                    $tenant = Tenant::where('slug', $slug)
                                ->where('is_active', true)
                                ->first();
                    if ($tenant) {
                        app()->instance('current_tenant', $tenant);
                        $model->tenant_id = $tenant->id;
                    }
                }
            }
        });
    }

    /**
     * Relación Eloquent: cualquier modelo con este trait
     * puede hacer $booking->tenant para obtener el objeto Tenant.
     */
    public function tenant()
    {
        return $this->belongsTo(\App\Models\Tenant::class);
    }
}
