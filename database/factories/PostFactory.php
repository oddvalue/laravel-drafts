<?php

namespace Oddvalue\LaravelDrafts\Database\Factories;

use Oddvalue\LaravelDrafts\Tests\Post;

class PostFactory extends \Illuminate\Database\Eloquent\Factories\Factory
{
    protected $model = Post::class;

    /**
     * @inheritDoc
     */
    public function definition()
    {
        return [
            'title' => $this->faker->sentence,
            'is_current' => true,
        ];
    }

    public function draft()
    {
        return $this->state(function () {
            return [
                'published_at' => null,
                'is_published' => false,
            ];
        });
    }

    public function published()
    {
        return $this->state(function () {
            return [
                'published_at' => now()->toDateTimeString(),
                'is_published' => true,
            ];
        });
    }
}
