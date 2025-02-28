<?php

namespace Oddvalue\LaravelDrafts\Database\Factories;

use Oddvalue\LaravelDrafts\Tests\app\Models\SoftDeletingPost;

class SoftDeletingPostFactory extends PostFactory
{
    protected $model = SoftDeletingPost::class;

    /**
     * @inheritDoc
     */
    public function definition()
    {
        return [
            'title' => $this->faker->sentence,
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
