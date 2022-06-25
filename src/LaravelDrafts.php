<?php

namespace Oddvalue\LaravelDrafts;

use Illuminate\Support\Facades\Auth;

class LaravelDrafts
{
    public function getCurrentUser()
    {
        return Auth::guard(config('drafts.auth.guard'))->user();
    }
}
