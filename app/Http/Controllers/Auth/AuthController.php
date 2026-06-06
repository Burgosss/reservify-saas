<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * Registro de nuevo cliente dentro de un tenant.
     * El tenant ya fue resuelto por IdentifyTenant antes de llegar aquí.
     */
    public function register(Request $request)
    {
        $data = $request->validate([
            'name'     => 'required|string|max:150',
            'email'    => 'required|email|max:200',
            'password' => 'required|string|min:8|confirmed',
        ]);

        // Verificar que el email no exista ya en este tenant
        $exists = User::where('email', $data['email'])->exists();

        if ($exists) {
            return response()->json([
                'error' => 'Este email ya está registrado en este negocio.',
                'code'  => 'EMAIL_TAKEN',
            ], 422);
        }

        // BelongsToTenant inyecta tenant_id automáticamente al crear
        $user = User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'password' => $data['password'], // el cast 'hashed' lo encripta solo
            'role'     => 'client',
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'user'  => $user,
            'token' => $token,
        ], 201);
    }

    /**
     * Login — verifica email y password dentro del tenant actual.
     */
    public function login(Request $request)
    {
        $data = $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        // User::where() ya filtra por tenant_id gracias al TenantScope
        $user = User::where('email', $data['email'])->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            return response()->json([
                'error' => 'Credenciales incorrectas.',
                'code'  => 'INVALID_CREDENTIALS',
            ], 401);
        }

        if (! $user->is_active) {
            return response()->json([
                'error' => 'Usuario inactivo.',
                'code'  => 'USER_INACTIVE',
            ], 403);
        }

        // Revocar tokens anteriores — un usuario, un token activo
        $user->tokens()->delete();

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'user'  => $user,
            'token' => $token,
        ]);
    }

    /**
     * Logout — invalida el token actual.
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Sesión cerrada correctamente.',
        ]);
    }

    /**
     * Devuelve el usuario autenticado actual.
     */
    public function me(Request $request)
    {
        return response()->json($request->user());
    }
}
