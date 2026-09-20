<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckMenuPermission
{
    /**
     * Ensure the authenticated user's role grants the action on the menu slug.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string $menuSlug, string $action): Response
    {
        if (! $request->user()?->canAccess($menuSlug, $action)) {
            abort(403);
        }

        return $next($request);
    }
}
