<?php

namespace Oddvalue\LaravelDrafts\Database\Factories;

use Oddvalue\LaravelDrafts\Tests\Post;

class PostFactory extends \Illuminate\Database\Eloquent\Factories\Factory
{
    protected $model = Post::class;

    /**
     * @inheritDoc
     */
    public function definition(): array
    {
        return [
            'title' => $this->faker->sentence,
            'is_current' => true,
        ];
    }

    public function draft(): PostFactory
    {
        return $this->state(fn (): array => [
            'published_at' => null,
            'is_published' => false,
        ]);
    }

    public function published(): PostFactory
    {
        return $this->state(fn (): array => [
            'published_at' => now()->toDateTimeString(),
            'is_published' => true,
        ]);
    }
}
