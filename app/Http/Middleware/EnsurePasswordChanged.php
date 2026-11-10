<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePasswordChanged
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null || ! $user->mustChangePassword()) {
            return $next($request);
        }

        if ($this->isExempt($request)) {
            return $next($request);
        }

        return redirect()->route('password.forced.edit');
    }

    private function isExempt(Request $request): bool
    {
        return $request->routeIs([
            'password.forced.edit',
            'password.forced.update',
            'logout',
        ]);
    }
}
