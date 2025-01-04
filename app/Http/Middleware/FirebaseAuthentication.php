<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Kreait\Laravel\Firebase\Facades\FirebaseAuth;
use Firebase\Auth\Token\Exception\InvalidToken;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class FirebaseAuthentication
{
    public function handle(Request $request, Closure $next)
    {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json(['error' => 'No token provided'], 401);
        }

        if (app()->environment('local', 'testing')) {
            return $this->handleLocalAuthentication($request, $token, $next);
        }

        return $this->handleProductionAuthentication($request, $token, $next);
    }

    private function handleLocalAuthentication(Request $request, $token, Closure $next)
    {
        $role = $this->getDummyRole($token);
        if (!$role) {
            return response()->json(['error' => 'Invalid dummy token'], 401);
        }

        $user = User::where('role', $role)->first();
        if (!$user) {
            return response()->json(['error' => 'Dummy user not found. Run seeder first!'], 404);
        }

        Auth::login($user);
        return $next($request);
    }

    private function handleProductionAuthentication(Request $request, $token, Closure $next)
    {
        try {
            $verifiedIdToken = FirebaseAuth::verifyIdToken($token);
            $uid = $verifiedIdToken->claims()->get('sub');

            $user = User::where('firebase_uid', $uid)->first();
            if (!$user) {
                return response()->json(['error' => 'User not found'], 404);
            }

            Auth::login($user);
            return $next($request);
        } catch (InvalidToken $e) {
            return response()->json(['error' => 'Invalid token'], 401);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 401);
        }
    }

    private function getDummyRole($token)
    {
        return match ($token) {
            'dummy-token' => 'admin',
            default => null
        };
    }
}
