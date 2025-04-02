<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use App\Http\Responses\ApiResponse;

class ThrottleRequestsMiddleware
{
    protected int $maxAttempts = 5;
    protected int $decayMinutes = 1;

    public function handle(Request $request, Closure $next, $actionKey = 'default', $maxAttempts = null, $decayMinutes = null)
    {
        // Si se pasan valores desde la ruta, se usan en lugar de los valores predeterminados
        $this->maxAttempts = $maxAttempts ?? $this->maxAttempts;
        $this->decayMinutes = $decayMinutes ?? $this->decayMinutes;

        $key = "{$actionKey}_attempts_" . $request->ip();

        if (RateLimiter::tooManyAttempts($key, $this->maxAttempts)) {
            return ApiResponse::error(
                null,
                'Demasiados intentos. Intenta de nuevo en ' . RateLimiter::availableIn($key) . ' segundos',
                429
            );
        }

        $response = $next($request);

        if ($response->status() !== 200) {
            RateLimiter::hit($key, now()->addMinutes($this->decayMinutes));
        } else {
            RateLimiter::clear($key);
        }

        return $response;
    }
}
