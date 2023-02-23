<?php

namespace Oddvalue\LaravelDrafts\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Oddvalue\LaravelDrafts\Facades\LaravelDrafts;

class WithDraftsMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        LaravelDrafts::withDrafts();

        return $next($request);
    }
}
