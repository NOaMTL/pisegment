<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SegmentPreviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_preview_segment_results(): void
    {
        $user = User::factory()->create();

        Customer::factory()->create(['city' => 'Bordeaux', 'average_balance' => 15000]);
        Customer::factory()->create(['city' => 'Bordeaux', 'average_balance' => 25000]);
        Customer::factory()->create(['city' => 'Paris', 'average_balance' => 30000]);

        $response = $this->actingAs($user)->postJson('/api/segment-preview', [
            'condition_groups' => [
                [
                    'logical_operator' => 'AND',
                    'conditions' => [
                        [
                            'field' => 'city',
                            'operator' => '=',
                            'value' => 'Bordeaux',
                        ],
                        [
                            'field' => 'average_balance',
                            'operator' => '>',
                            'value' => 20000,
                        ],
                    ],
                ],
            ],
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'total',
                'preview' => [
                    '*' => ['id', 'name', 'age', 'city', 'average_balance', 'products'],
                ],
            ])
            ->assertJson([
                'total' => 1,
            ]);
    }

    public function test_requires_authentication(): void
    {
        $response = $this->postJson('/api/segment-preview', [
            'condition_groups' => [],
        ]);

        $response->assertStatus(401);
    }

    public function test_validates_request_data(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/segment-preview', [
            'condition_groups' => 'invalid',
        ]);

        $response->assertStatus(422);
    }

    public function test_can_preview_with_between_operator(): void
    {
        $user = User::factory()->create();

        Customer::factory()->create(['average_balance' => 5000]);
        Customer::factory()->create(['average_balance' => 15000]);
        Customer::factory()->create(['average_balance' => 25000]);
        Customer::factory()->create(['average_balance' => 35000]);

        $response = $this->actingAs($user)->postJson('/api/segment-preview', [
            'condition_groups' => [
                [
                    'logical_operator' => 'AND',
                    'conditions' => [
                        [
                            'field' => 'average_balance',
                            'operator' => 'between',
                            'value' => 10000,
                            'value_max' => 30000,
                        ],
                    ],
                ],
            ],
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'total' => 2,
            ]);
    }
}
