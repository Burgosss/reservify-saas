<?php

namespace App\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\App;

class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        if (App::runningInConsole()) {
            return;
        }

        // Si no hay tenant resuelto todavía, no aplicar el scope
        // Esto evita el error cuando Sanctum carga el User internamente
        try {
            $tenant = App::make('current_tenant');
        } catch (\Exception $e) {
            return;
        }

        if (! $tenant) {
            return;
        }

        $builder->where(
            $model->getTable() . '.tenant_id',
            $tenant->id
        );
    }
}
