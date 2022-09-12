<?php

it('gets the logged in user', function () {
    $this->assertEquals($this->testUser, \TechnologyAdvice\LaravelDrafts\Facades\LaravelDrafts::getCurrentUser());
});
