<?php

namespace TechnologyAdvice\LaravelDrafts\Database\Factories;

use TechnologyAdvice\LaravelDrafts\Tests\Tag;

class TagFactory extends \Illuminate\Database\Eloquent\Factories\Factory
{
    protected $model = Tag::class;

    /**
     * @inheritDoc
     */
    public function definition()
    {
        return [
            'name' => $this->faker->word(),
        ];
    }
}
