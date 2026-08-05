<?php

use Oddvalue\LaravelDrafts\Facades\LaravelDrafts;

it('gets the logged in user', function (): void {
    $this->assertEquals($this->testUser, LaravelDrafts::getCurrentUser());
});
