<?php

namespace App\Services;

use App\Models\User;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;

class AuthService
{
    public function __construct(
        private NotificationService $notificationService
    ) {}

    public function register(RegisterRequest $request): array
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

            return [
                'user' => $user,
                'token' => $token,
            ];
        } catch (\Exception $e) {

            DB::rollBack();

            throw $e;
        }
    }

    public function login(LoginRequest $request): array
    {
        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw new \Exception('Invalid credentials');
        }

        $user->tokens()->delete();

        $token = $user->createToken('auth_token')->plainTextToken;

        return [
            'user' => $user,
            'token' => $token,
        ];
    }

    public function logout(User $user): void
    {
        $token = $user->currentAccessToken();

        if($token instanceof PersonalAccessToken){
            $token->delete();
        }
    }

    public function me(User $user): User
    {
        return $user;
    }
}
