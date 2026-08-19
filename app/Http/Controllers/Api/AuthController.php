<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Inicia sesión y emite un token de acceso personal (Sanctum).
     *
     * POST /api/v1/auth/login
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->validated();

        $user = \App\Models\User::where('email', $credentials['email'])->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Las credenciales entregadas son incorrectas.'],
            ]);
        }

        if (!$user->is_active) {
            throw ValidationException::withMessages([
                'email' => ['Tu cuenta se encuentra desactivada. Contacta a tu administrador.'],
            ]);
        }

        $token = $user->createToken($credentials['device_name'] ?? 'api');

        return response()->json([
            'token' => $token->plainTextToken,
            'user' => $user->load(['tenant:id,name,slug', 'company:id,name', 'branch:id,name']),
        ]);
    }

    /**
     * Cierra la sesión actual revocando el token en uso.
     *
     * POST /api/v1/auth/logout
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Sesión cerrada correctamente.']);
    }

    /**
     * Retorna el perfil del usuario autenticado con su contexto (tenant, empresa y sucursal).
     *
     * GET /api/v1/auth/me
     */
    public function me(Request $request): JsonResponse
    {
        return response()->json(
            $request->user()->load(['tenant:id,name,slug', 'company:id,name', 'branch:id,name'])
        );
    }
}
