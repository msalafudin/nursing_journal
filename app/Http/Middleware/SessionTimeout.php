<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

class SessionTimeout
{
    /**
     * Session timeout in minutes (60 minutes as per requirement 1.4)
     */
    private const SESSION_TIMEOUT_MINUTES = 60;

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Only check timeout for authenticated users
        if (Auth::check()) {
            $lastActivity = Session::get('last_activity');
            $now = now();

            // If last_activity is set, check if session has timed out
            if ($lastActivity) {
                $lastActivityTime = \Carbon\Carbon::createFromTimestamp($lastActivity);
                $minutesElapsed = $now->diffInMinutes($lastActivityTime);

                // If session has been inactive for 60 or more minutes, logout
                if ($minutesElapsed >= self::SESSION_TIMEOUT_MINUTES) {
                    Auth::logout();
                    Session::flush();

                    return redirect()->route('login')
                        ->with('message', 'Your session has expired due to inactivity. Please login again.')
                        ->with('message_type', 'info');
                }
            }

            // Update last_activity timestamp for this request
            Session::put('last_activity', $now->timestamp);
        }

        return $next($request);
    }
}
