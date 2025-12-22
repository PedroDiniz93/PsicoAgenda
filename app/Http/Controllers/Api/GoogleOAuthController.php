<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class GoogleOAuthController extends Controller
{
    public function generateUrl(Request $request)
    {
        $user = $request->user()->loadMissing('psychologist');
        abort_if(!$user->psychologist, 403, 'Perfil de psicólogo não encontrado.');

        $config = config('services.google');
        $clientId = $config['client_id'] ?? null;
        $redirectUri = $config['redirect'] ?? null;
        $scopes = $config['scopes'] ?? [];

        abort_if(!$clientId || !$redirectUri, 500, 'Integração com Google não configurada.');

        $state = Str::uuid()->toString();
        Cache::put($this->cacheKey($state), $user->id, now()->addMinutes(5));

        $params = [
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => implode(' ', $scopes),
            'access_type' => 'offline',
            'include_granted_scopes' => 'true',
            'prompt' => 'consent',
            'state' => $state,
        ];

        $url = 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params);

        return response()->json(['url' => $url]);
    }

    public function disconnect(Request $request)
    {
        $user = $request->user()->loadMissing('psychologist');
        abort_if(!$user->psychologist, 403, 'Perfil de psicólogo não encontrado.');

        $user->psychologist->google_calendar_token = null;
        $user->psychologist->save();

        return response()->json(['status' => 'disconnected']);
    }

    public function callback(Request $request)
    {
        $state = $request->query('state');
        $error = $request->query('error');
        $code = $request->query('code');
        $frontend = rtrim(config('app.frontend_url', config('app.url')), '/');
        $redirectUrl = $frontend ?: url('/');

        $userId = $state ? Cache::pull($this->cacheKey($state)) : null;

        if ($error || !$state || !$userId || !$code) {
            return redirect()->to($redirectUrl . '?google=denied');
        }

        $user = User::with('psychologist')->find($userId);
        if (!$user || !$user->psychologist) {
            return redirect()->to($redirectUrl . '?google=denied');
        }

        $config = config('services.google');
        $clientId = $config['client_id'] ?? null;
        $clientSecret = $config['client_secret'] ?? null;
        $redirectUri = $config['redirect'] ?? null;

        if (!$clientId || !$clientSecret || !$redirectUri) {
            return redirect()->to($redirectUrl . '?google=error');
        }

        try {
            $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
                'code' => $code,
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'redirect_uri' => $redirectUri,
                'grant_type' => 'authorization_code',
            ]);
        } catch (\Throwable) {
            return redirect()->to($redirectUrl . '?google=error');
        }

        if (!$response->successful()) {
            return redirect()->to($redirectUrl . '?google=error');
        }

        $tokenPayload = $response->json();

        if (!isset($tokenPayload['access_token'])) {
            return redirect()->to($redirectUrl . '?google=error');
        }

        $storedToken = [
            'access_token' => $tokenPayload['access_token'],
            'refresh_token' => $tokenPayload['refresh_token'] ?? null,
            'expires_at' => isset($tokenPayload['expires_in'])
                ? now()->addSeconds((int) $tokenPayload['expires_in'])->toIso8601String()
                : null,
            'scope' => $tokenPayload['scope'] ?? null,
            'token_type' => $tokenPayload['token_type'] ?? null,
        ];

        $user->psychologist->google_calendar_token = Crypt::encryptString(json_encode($storedToken));
        $user->psychologist->save();

        return redirect()->to($redirectUrl . '?google=connected');
    }

    private function cacheKey(string $state): string
    {
        return "google_oauth_state:{$state}";
    }
}
