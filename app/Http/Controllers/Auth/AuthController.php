<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6|confirmed',
        ]);

        // Obtener el tenant del request
        $tenant = app('current_tenant');
        if (!$tenant) {
            return response()->json([
                'message' => 'Tenant no identificado',
            ], 400);
        }

        // Crear usuario con role cliente
        $user = User::create([
            'tenant_id' => $tenant->id,
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => 'client',
        ]);

        // Generar token
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token,
        ], 201);
    }

    public function login(Request $request)
{
    $validated = $request->validate([
        'email' => 'required|email',
        'password' => 'required|string',
    ]);

    \Log::info('Login intento', [
        'email' => $validated['email'],
        'tenant' => app('current_tenant')?->slug ?? 'null'
    ]);

    $user = User::where('email', $validated['email'])->first();

    \Log::info('Usuario encontrado?', [
        'user' => $user ? $user->id : 'null',
        'email' => $validated['email']
    ]);

    if (!$user) {
        \Log::warning('Usuario no encontrado para email', ['email' => $validated['email']]);
        return response()->json([
            'message' => 'Credenciales inválidas',
        ], 401);
    }

    if (!Hash::check($validated['password'], $user->password)) {
        \Log::warning('Contraseña incorrecta para', ['email' => $validated['email']]);
        return response()->json([
            'message' => 'Credenciales inválidas',
        ], 401);
    }

    $token = $user->createToken('auth_token')->plainTextToken;

    return response()->json([
        'user' => $user,
        'token' => $token,
    ]);
}

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out']);
    }

    public function me(Request $request)
    {
        return response()->json($request->user());
    }
}
