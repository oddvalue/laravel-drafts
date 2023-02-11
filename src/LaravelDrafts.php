<?php

namespace Oddvalue\LaravelDrafts;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class LaravelDrafts
{
    public function getCurrentUser(): Model | Authenticatable
    {
        return Auth::guard(config('drafts.auth.guard'))->user();
    }
}
