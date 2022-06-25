<?php

it('gets the logged in user', function () {
    $this->assertEquals($this->testUser, \Oddvalue\LaravelDrafts\Facades\LaravelDrafts::getCurrentUser());
});
