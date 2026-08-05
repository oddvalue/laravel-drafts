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
        /** @var string|null $guard */
        $guard = config('drafts.auth.guard');

        return Auth::guard($guard)->user();
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
        /** @var bool $preview */
        $preview = Session::get('drafts.preview', false);

        return $preview;
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
