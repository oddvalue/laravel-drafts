<?php

declare(strict_types=1);

namespace Oddvalue\LaravelDrafts\Tests\app\Models;

use Illuminate\Support\Arr;

/**
 * A section model exposing getDraftableAttributes(), exercising the branch
 * in replicateAndAssociateDraftableRelations that prefers it over
 * getAttributes() when replicating related models.
 */
class DraftableAttributesSection extends PostSection
{
    protected $table = 'post_sections';

    /**
     * @return array<string, mixed>
     */
    public function getDraftableAttributes(): array
    {
        /** @var array<string, mixed> */
        return Arr::except($this->getAttributes(), ['created_at', 'updated_at']);
    }
}
