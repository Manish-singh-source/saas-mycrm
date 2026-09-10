<?php
namespace App\Http\Controllers;

use App\Http\Requests\ForgotPasswordRequest;
use App\Http\Requests\ResetPasswordRequest;
use App\Models\PasswordResetToken;
use App\Models\PlatformUser;
use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class PasswordRecoveryController extends Controller
{
    public function forgot(ForgotPasswordRequest $request): mixed
    {
        $input = $request->validated();
        $token = Str::random(64);
        $targets = [];
        foreach (PlatformUser::where('email', $input['email'])->where('status', 'active')->get() as $user) $targets[] = ['platform', $user->id];
        foreach (User::where('email', $input['email'])->where('status', 'active')->get() as $user) $targets[] = ['tenant', $user->id];
        PasswordResetToken::updateOrCreate(['email' => $input['email']], ['token' => json_encode(['hash' => Hash::make($token), 'targets' => $targets]), 'created_at' => now()]);
        Log::info('Password reset instructions queued.', ['email' => $input['email']]);
        $data = ['email' => $input['email']];
        if (app()->environment('local')) $data['reset_token'] = $token;
        return ApiResponse::success($data, 'If an account exists, password reset instructions have been sent.');
    }

    public function reset(ResetPasswordRequest $request): mixed
    {
        $input = $request->validated();
        $record = PasswordResetToken::where('email', $input['email'])->first();
        $payload = $record ? json_decode($record->token, true) : null;
        if (! $record || ! is_array($payload) || ! Hash::check($input['token'], $payload['hash'] ?? '')) return ApiResponse::error('Invalid or expired reset token.', 422, null, 'INVALID_RESET_TOKEN');
        foreach ($payload['targets'] ?? [] as [$surface, $id]) {
            $model = $surface === 'platform' ? PlatformUser::find($id) : User::find($id);
            if ($model) $model->forceFill(['password' => Hash::make($input['password'])])->save();
        }
        $record->delete();
        return ApiResponse::success(null, 'Password reset successfully.');
    }
}
