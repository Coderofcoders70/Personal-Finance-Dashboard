<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Requests\LoginRequest;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use App\Http\Requests\RegisterRequest;
use App\Services\NotificationService;
use Illuminate\Support\Facades\DB;

class AuthController extends Controller
{
    private NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    public function register(RegisterRequest $request)
    {
        DB::beginTransaction();

        try {

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
            ]);

            // throw new \Exception('Testing rollback');

            $this->notificationService->create(
                $user,
                'user_registered',
                "Welcome, {$user->name}!",
                'Welcome to Personal Finance Dashboard. Your account has been created successfully. We are excited to help you build better financial habits.',
                'success'
            );

            $token = $user->createToken('auth_token')->plainTextToken;
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'User registered successfully.',
                'token' => $token,
                'user' => $user,
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

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
        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials.',
            ], 401);
        }

        $user->tokens()->delete();
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login successful.',
            'token' => $token,
            'user' => $user,
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully.',
        ]);
    }

    public function me(Request $request)
    {
        return response()->json([
            'success' => true,
            'user' => $request->user(),
        ]);
    }
}
