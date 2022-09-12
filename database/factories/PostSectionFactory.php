<?php

namespace TechnologyAdvice\LaravelDrafts\Database\Factories;

use TechnologyAdvice\LaravelDrafts\Tests\PostSection;

class PostSectionFactory extends \Illuminate\Database\Eloquent\Factories\Factory
{
    protected $model = PostSection::class;

    /**
     * @inheritDoc
     */
    public function definition()
    {
        return [
            'content' => $this->faker->paragraph,
        ];
    }
}
