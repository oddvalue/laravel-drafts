<?php

namespace Oddvalue\LaravelDrafts\Http\Middleware;

use Closure;
use Oddvalue\LaravelDrafts\Facades\LaravelDrafts;
use Illuminate\Http\Request;

class WithDraftsMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        LaravelDrafts::withDrafts();

        return $next($request);
    }
}
