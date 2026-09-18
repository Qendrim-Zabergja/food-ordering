<?php

namespace App\Http\Controllers\Auth;

use App\Enums\RoleSlug;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * Bearer token authentication via Sanctum.
 *
 * register and login are the only endpoints in the application that are not
 * behind auth:sanctum - they cannot be, since their purpose is to issue the
 * token everything else requires. Both are rate limited in routes/api.php.
 */
class AuthController extends Controller
{
    public function register(RegisterRequest $request): Response
    {
        $validated = $request->validated();

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
        ]);

        // Registration always produces a customer. Admin accounts are seeded or
        // promoted deliberately; there is no path from this endpoint to one.
        $user->assignRole(RoleSlug::CUSTOMER);

        return response([
            'user' => new UserResource($user),
            'token' => $this->issueToken($user, $validated['device_name'] ?? null),
        ], 201);
    }

    public function login(LoginRequest $request): Response
    {
        $validated = $request->validated();

        $user = User::where('email', $validated['email'])->first();

        // One message for both a wrong email and a wrong password, so the
        // response cannot be used to discover which addresses have accounts.
        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => 'The provided credentials are incorrect.',
            ]);
        }

        return response([
            'user' => new UserResource($user),
            'token' => $this->issueToken($user, $validated['device_name'] ?? null),
        ], 200);
    }

    /**
     * Revokes only the token that made this request, so logging out on one
     * device leaves the others signed in.
     */
    public function logout(Request $request): Response
    {
        $request->user()->currentAccessToken()->delete();

        return response(null, 204);
    }

    public function me(Request $request): Response
    {
        return response(new UserResource($request->user()), 200);
    }

    protected function issueToken(User $user, ?string $deviceName): string
    {
        return $user->createToken($deviceName ?: 'api')->plainTextToken;
    }
}
