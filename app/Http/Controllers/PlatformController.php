<?php

namespace App\Http\Controllers;

use App\Support\ActivityLogger;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

final class PlatformController extends Controller
{
    public function health(): mixed { return ApiResponse::success(['scope' => 'platform', 'version' => 'v1'], 'Platform API is ready.'); }

    public function profile(Request $request): mixed { return ApiResponse::success(['user' => $request->user()->load(['department', 'designation', 'manager', 'subordinates', 'roles', 'teams', 'permissions', 'profilePhotoFile'])], 'Platform profile fetched.'); }

    public function updateProfile(Request $request): mixed
    {
        $data = $request->validate(['first_name' => ['sometimes', 'string', 'max:100'], 'last_name' => ['sometimes', 'nullable', 'string', 'max:100'], 'display_name' => ['sometimes', 'string', 'max:200'], 'mobile' => ['sometimes', 'nullable', 'string', 'max:20'], 'timezone' => ['sometimes', 'string', 'max:100'], 'locale' => ['sometimes', 'string', 'max:20']]);
        $request->user()->fill($data)->save(); ActivityLogger::record($request, 'profile.updated', $request->user(), 'Platform profile updated.', $data);
        return ApiResponse::success(['user' => $request->user()->fresh()->load(['department', 'designation', 'manager', 'subordinates', 'roles', 'teams', 'permissions', 'profilePhotoFile'])], 'Platform profile updated.');
    }

    public function changePassword(Request $request): mixed
    {
        $data = $request->validate(['current_password' => ['required', 'string'], 'password' => ['required', 'string', 'min:8', 'confirmed']]);
        if (! Hash::check($data['current_password'], $request->user()->password)) return ApiResponse::error('Invalid password.', 422, null, 'INVALID_PASSWORD');
        $request->user()->update(['password' => Hash::make($data['password'])]); ActivityLogger::record($request, 'password.changed', $request->user(), 'Platform password changed.');
        return ApiResponse::success(null, 'Password changed successfully.');
    }

    public function sessions(Request $request): mixed
    {
        $sessions = $request->user()->tokens()->latest()->get(['id', 'name', 'abilities', 'last_used_at', 'expires_at', 'created_at']);
        return ApiResponse::success($sessions, 'Platform sessions fetched.');
    }

    public function revokeSession(Request $request, int $sessionId): mixed
    {
        $deleted = $request->user()->tokens()->whereKey($sessionId)->delete();
        if (! $deleted) return ApiResponse::error('Session not found.', 404);
        ActivityLogger::record($request, 'session.revoked', $request->user(), 'Platform session revoked.');
        return ApiResponse::success(null, 'Session revoked successfully.');
    }
}
