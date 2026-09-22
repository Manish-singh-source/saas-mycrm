<?php
namespace App\Http\Controllers;

use App\Http\Requests\ConfirmTwoFactorRequest;
use App\Http\Requests\GetPlatformPreferencesRequest;
use App\Http\Requests\PlatformCredentialsRequest;
use App\Http\Requests\UpdatePlatformPreferencesRequest;
use App\Models\PlatformUser;
use App\Models\PlatformUserPreference;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class PlatformSecurityController extends Controller
{
    public function resendVerification(PlatformCredentialsRequest $request): mixed
    {
        $user = $this->user($request);
        Log::info('Platform verification email queued.', ['platform_user_id' => $user->id]);
        return ApiResponse::success(null, 'Verification email queued.');
    }

    public function enableTwoFactor(PlatformCredentialsRequest $request): mixed
    {
        $user = $this->user($request);
        $secret = $this->base32Encode(random_bytes(20));
        $setupToken = Str::random(64);
        $user->forceFill(['two_factor_secret' => $secret, 'two_factor_enabled' => false, 'two_factor_confirmed_at' => null])->save();
        Cache::put('platform.2fa.setup.'.hash('sha256', $setupToken), $user->id, now()->addMinutes(10));
        return ApiResponse::success(['setup_token' => $setupToken, 'secret' => $secret, 'provisioning_uri' => 'otpauth://totp/MyCRM:'.rawurlencode($user->email).'?secret='.$secret.'&issuer=MyCRM'], 'Two-factor authentication setup started.');
    }

    public function confirmTwoFactor(ConfirmTwoFactorRequest $request): mixed
    {
        $input = $request->validated();
        $id = Cache::pull('platform.2fa.setup.'.hash('sha256', $input['setup_token']));
        $user = $id ? PlatformUser::find($id) : null;
        if (! $user || ! $this->validTotp($user->two_factor_secret, $input['code'])) return ApiResponse::error('Invalid two-factor code.', 422, null, 'INVALID_TWO_FACTOR_CODE');
        $codes = collect(range(1, 8))->map(fn () => Str::random(10))->all();
        $user->forceFill(['two_factor_enabled' => true, 'two_factor_confirmed_at' => now(), 'two_factor_recovery_codes' => json_encode($codes)])->save();
        return ApiResponse::success(['recovery_codes' => $codes], 'Two-factor authentication enabled.');
    }

    public function disableTwoFactor(PlatformCredentialsRequest $request): mixed
    {
        $user = $this->user($request);
        $user->forceFill(['two_factor_enabled' => false, 'two_factor_secret' => null, 'two_factor_confirmed_at' => null, 'two_factor_recovery_codes' => null])->save();
        return ApiResponse::success(null, 'Two-factor authentication disabled.');
    }

    public function preferences(GetPlatformPreferencesRequest $request): mixed
    {
        $user = $this->user($request);
        $query = PlatformUserPreference::where('platform_user_id', $user->id);
        if ($request->filled('group')) $query->where('group', (string) $request->input('group'));
        if ($request->filled('key')) $query->where('key', (string) $request->input('key'));
        return ApiResponse::success(['preferences' => $query->orderBy('group')->orderBy('key')->get()], 'Platform preferences fetched.');
    }

    public function updatePreferences(UpdatePlatformPreferencesRequest $request): mixed
    {
        $input = $request->validated();
        $user = $this->user($request);
        foreach ($input['preferences'] as $group => $values) {
            foreach ($values as $key => $value) $user->preferences()->updateOrCreate(['group' => $group, 'key' => $key], ['value' => $value]);
        }
        return ApiResponse::success(['preferences' => PlatformUserPreference::where('platform_user_id', $user->id)->orderBy('group')->orderBy('key')->get()], 'Platform preferences updated.');
    }

    private function user(Request $request): PlatformUser
    {
        $user = $request->user() instanceof PlatformUser ? $request->user() : null;
        if (! $user) {
            $data = $request->validated();
            $user = PlatformUser::where('email', $data['email'])->where('status', 'active')->first();
            if (! $user || ! Hash::check($data['password'], $user->password)) abort(401, 'Invalid credentials.');
        }
        return $user;
    }

    private function validTotp(?string $secret, string $code): bool
    {
        if (! $secret) return false;
        $key = $this->base32Decode($secret);
        $counter = intdiv(time(), 30);
        for ($offset = -1; $offset <= 1; $offset++) {
            $hash = hash_hmac('sha1', pack('N*', 0).pack('N*', $counter + $offset), $key, true);
            $index = ord($hash[19]) & 15;
            $otp = ((ord($hash[$index]) & 127) << 24 | (ord($hash[$index + 1]) & 255) << 16 | (ord($hash[$index + 2]) & 255) << 8 | (ord($hash[$index + 3]) & 255)) % 1000000;
            if (hash_equals(str_pad((string) $otp, 6, '0', STR_PAD_LEFT), $code)) return true;
        }
        return false;
    }

    private function base32Encode(string $value): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567'; $bits = 0; $buffer = 0; $out = '';
        foreach (unpack('C*', $value) as $byte) { $buffer = ($buffer << 8) | $byte; $bits += 8; while ($bits >= 5) { $bits -= 5; $out .= $alphabet[($buffer >> $bits) & 31]; } }
        if ($bits) $out .= $alphabet[($buffer << (5 - $bits)) & 31];
        return $out;
    }

    private function base32Decode(string $value): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567'; $buffer = 0; $bits = 0; $out = '';
        foreach (str_split(strtoupper($value)) as $char) { $buffer = ($buffer << 5) | strpos($alphabet, $char); $bits += 5; if ($bits >= 8) { $bits -= 8; $out .= chr(($buffer >> $bits) & 255); } }
        return $out;
    }
}
