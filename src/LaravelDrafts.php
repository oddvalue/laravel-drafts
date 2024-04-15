<?php

namespace Oddvalue\LaravelDrafts;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class LaravelDrafts
{
    protected bool $withDrafts = false;

    public function getCurrentUser(): ?Authenticatable
    {
        return Auth::guard(config('drafts.auth.guard'))->user();
    }

    public function previewMode(bool $previewMode = true): void
    {
        Session::put('drafts.preview', $previewMode);
    }

    public function disablePreviewMode(): void
    {
        Session::forget('drafts.preview');
    }

    public function isPreviewModeEnabled(): bool
    {
        return Session::get('drafts.preview', false);
    }

    public function withDrafts(bool $withDrafts = true): void
    {
        $this->withDrafts = $withDrafts;
    }

    public function isWithDraftsEnabled(): bool
    {
        return $this->withDrafts;
    }
}
