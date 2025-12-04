<?php

namespace Oddvalue\LaravelDrafts\Database\Factories;

use Oddvalue\LaravelDrafts\Tests\app\Models\Post;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<Post>
 */
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

    public function draft(): static
    {
        return $this->state(function () {
            return [
                'published_at' => null,
                'is_published' => false,
            ];
        });
    }

    public function published(): static
    {
        return $this->state(function () {
            return [
                'published_at' => now()->toDateTimeString(),
                'is_published' => true,
            ];
        });
    }
}
