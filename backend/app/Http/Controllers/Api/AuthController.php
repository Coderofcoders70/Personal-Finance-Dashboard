<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Requests\LoginRequest;
use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterRequest;
use App\Services\AuthService;

class AuthController extends Controller
{
    private AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    public function register(RegisterRequest $request)
    {
        try {

            $result = $this->authService->register($request);
            
            return response()->json([
                'success' => true,
                'message' => 'User registered successfully.',
                'token' => $result['token'],
                'user' => $result['user'],
            ], 201);

        } catch (\Exception $e) {

            return response()->json([

                'success' => false,
                'message' => 'Registration failed. Please try again.',
                // Uncomment the below while debugging:
                // 'error' => $e->getMessage()
            ], 500);
        }
    }

    public function login(LoginRequest $request)
    {
        $result = $this->authService->login($request);

        return response()->json([
            'success' => true,
            'message' => 'Login successful.',
            'token' => $result['token'],
            'user' => $result['user'],
        ]);
    }

    public function logout(Request $request)
    {
        $this->authService->logout($request->user());

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully.',
        ]);
    }

    public function me(Request $request)
    {
        return response()->json([
            'success' => true,
            'user' => $this->authService->me($request->user()),
        ]);
    }
}
