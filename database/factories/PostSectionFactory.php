<?php

namespace Oddvalue\LaravelDrafts\Database\Factories;

use Oddvalue\LaravelDrafts\Tests\app\Models\PostSection;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<PostSection>
 */
class PostSectionFactory extends \Illuminate\Database\Eloquent\Factories\Factory
{
    protected $model = PostSection::class;

    /**
     * @return array<string, mixed>
     * @phpstan-ignore method.childReturnType
     */
    public function definition(): array
    {
        return [
            'content' => $this->faker->paragraph,
        ];
    }
}
