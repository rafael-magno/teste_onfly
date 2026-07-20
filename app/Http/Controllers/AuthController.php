<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Resources\AuthTokenResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;
use PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException;
use PHPOpenSourceSaver\JWTAuth\JWTGuard;
use Symfony\Component\HttpFoundation\Response;

class AuthController extends Controller
{
    /**
     * @unauthenticated
     * @responseFile docs/responses/login_200.json
     * @response 401 {"message": "Credenciais inválidas."}
     */
    public function login(LoginRequest $request): JsonResponse
    {
        if (! $token = Auth::guard('api')->attempt($request->validated())) {
            return $this->respondError('Credenciais inválidas.', Response::HTTP_UNAUTHORIZED);
        }

        return $this->respondSuccess(AuthTokenResource::make([
            ...$this->tokenPayload($token),
            'user' => Auth::guard('api')->user(),
        ]));
    }

    /**
     * @responseFile docs/responses/refresh_200.json
     * @response 401 {"message": "Não foi possível renovar o token."}
     */
    public function refresh(): JsonResponse
    {
        try {
            /** @var JWTGuard */
            $guard = Auth::guard('api');
            $token = $guard->refresh();
        } catch (JWTException $e) {
            return $this->respondError('Não foi possível renovar o token.', Response::HTTP_UNAUTHORIZED);
        }

        return $this->respondSuccess(AuthTokenResource::make($this->tokenPayload($token)));
    }

    /**
     * @response 200 {"message": "Logout realizado com sucesso."}
     */
    public function logout(): JsonResponse
    {
        Auth::guard('api')->logout();

        return $this->respondSuccess(JsonResource::make([
            'message' => 'Logout realizado com sucesso.',
        ]));
    }

    /**
     * @return array<string, mixed>
     */
    private function tokenPayload(string $token): array
    {
        /** @var JWTGuard */
        $guard = Auth::guard('api');

        return [
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => $guard->factory()->getTTL() * 60,
        ];
    }
}
