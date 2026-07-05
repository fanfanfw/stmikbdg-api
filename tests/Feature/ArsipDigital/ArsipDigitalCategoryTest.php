<?php

namespace Tests\Feature\ArsipDigital;

class ArsipDigitalCategoryTest extends ArsipDigitalFeatureTestCase
{
    public function test_update_category_rejects_descendant_parent_cycle(): void
    {
        $rootId = $this->actingAsMahasiswa()
            ->postJson('/api/arsip-digital/categories', [
                'category_type' => 'personal',
                'name' => 'Root',
            ])
            ->assertCreated()
            ->json('data.category.category_id');

        $childId = $this->actingAsMahasiswa()
            ->postJson('/api/arsip-digital/categories', [
                'category_type' => 'personal',
                'name' => 'Child',
                'parent_category_id' => $rootId,
            ])
            ->assertCreated()
            ->json('data.category.category_id');

        $grandchildId = $this->actingAsMahasiswa()
            ->postJson('/api/arsip-digital/categories', [
                'category_type' => 'personal',
                'name' => 'Grandchild',
                'parent_category_id' => $childId,
            ])
            ->assertCreated()
            ->json('data.category.category_id');

        $this->actingAsMahasiswa()
            ->putJson('/api/arsip-digital/categories/'.$rootId, [
                'parent_category_id' => $grandchildId,
            ])
            ->assertUnprocessable();
    }

    public function test_update_category_rejects_self_parent(): void
    {
        $categoryId = $this->actingAsMahasiswa()
            ->postJson('/api/arsip-digital/categories', [
                'category_type' => 'personal',
                'name' => 'Root',
            ])
            ->assertCreated()
            ->json('data.category.category_id');

        $this->actingAsMahasiswa()
            ->putJson('/api/arsip-digital/categories/'.$categoryId, [
                'parent_category_id' => $categoryId,
            ])
            ->assertUnprocessable();
    }
}
