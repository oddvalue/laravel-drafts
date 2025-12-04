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
     * @inheritDoc
     */
    public function definition()
    {
        return [
            'content' => $this->faker->paragraph,
        ];
    }
}
