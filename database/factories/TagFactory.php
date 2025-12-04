<?php

namespace Oddvalue\LaravelDrafts\Database\Factories;

use Oddvalue\LaravelDrafts\Tests\app\Models\Tag;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<Tag>
 */
class TagFactory extends \Illuminate\Database\Eloquent\Factories\Factory
{
    protected $model = Tag::class;

    /**
     * @return array<string, mixed>
     * @phpstan-ignore method.childReturnType
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->word(),
        ];
    }
}
