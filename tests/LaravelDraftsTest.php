<?php

it('gets the logged in user', function (): void {
    $this->assertEquals($this->testUser, \Oddvalue\LaravelDrafts\Facades\LaravelDrafts::getCurrentUser());
});
