<?php
// app/Http/Middleware/IdentifyTenant.php

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;

class IdentifyTenant
{
    public function handle(Request $request, Closure $next): Response
    {
        // Estrategia 1: por subdominio (acme.tuapp.com)
        $host = $request->getHost();
        $subdomain = explode('.', $host)[0];

        $tenant = Tenant::where('slug', $subdomain)
                        ->where('is_active', true)
                        ->first();

        // Fallback para desarrollo local: header X-Tenant-Slug
        if (!$tenant && app()->environment('local')) {
            $slug = $request->header('X-Tenant-Slug');
            if ($slug) {
                $tenant = Tenant::where('slug', $slug)->first();
            }
        }

        if (!$tenant) {
            return response()->json([
                'error' => 'Tenant not found or inactive.',
                'code'  => 'TENANT_NOT_RESOLVED',
            ], 404);
        }
        Log::info('Tenant resuelto: ' . $tenant->id . ' para slug: ' . $tenant->slug);

        // Registra el tenant en el contenedor para el resto del request
        app()->instance('current_tenant', $tenant);

        return $next($request);
    }
}
