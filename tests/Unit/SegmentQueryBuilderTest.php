<?php

namespace Tests\Unit;

use App\Models\Customer;
use App\Services\SegmentBuilder\Condition;
use App\Services\SegmentBuilder\ConditionGroup;
use App\Services\SegmentBuilder\OperatorType;
use App\Services\SegmentBuilder\SegmentQueryBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SegmentQueryBuilderTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_build_query_with_equals_condition(): void
    {
        Customer::factory()->create(['city' => 'Bordeaux']);
        Customer::factory()->create(['city' => 'Paris']);

        $condition = new Condition(
            field: 'city',
            operator: OperatorType::Equals,
            value: 'Bordeaux'
        );

        $group = new ConditionGroup('AND', [$condition]);
        $builder = new SegmentQueryBuilder([$group]);

        $results = $builder->build()->get();

        $this->assertCount(1, $results);
        $this->assertEquals('Bordeaux', $results->first()->city);
    }

    public function test_can_build_query_with_greater_than_condition(): void
    {
        Customer::factory()->create(['average_balance' => 5000]);
        Customer::factory()->create(['average_balance' => 15000]);
        Customer::factory()->create(['average_balance' => 25000]);

        $condition = new Condition(
            field: 'average_balance',
            operator: OperatorType::GreaterThan,
            value: 10000
        );

        $group = new ConditionGroup('AND', [$condition]);
        $builder = new SegmentQueryBuilder([$group]);

        $results = $builder->build()->get();

        $this->assertCount(2, $results);
        $this->assertTrue($results->every(fn ($c) => $c->average_balance > 10000));
    }

    public function test_can_build_query_with_in_condition(): void
    {
        Customer::factory()->create(['city' => 'Bordeaux']);
        Customer::factory()->create(['city' => 'Mérignac']);
        Customer::factory()->create(['city' => 'Paris']);

        $condition = new Condition(
            field: 'city',
            operator: OperatorType::In,
            value: ['Bordeaux', 'Mérignac']
        );

        $group = new ConditionGroup('AND', [$condition]);
        $builder = new SegmentQueryBuilder([$group]);

        $results = $builder->build()->get();

        $this->assertCount(2, $results);
    }

    public function test_can_convert_to_and_from_array(): void
    {
        $condition = new Condition(
            field: 'city',
            operator: OperatorType::Equals,
            value: 'Bordeaux'
        );

        $group = new ConditionGroup('AND', [$condition]);
        $builder = new SegmentQueryBuilder([$group]);

        $array = $builder->toArray();
        $recreated = SegmentQueryBuilder::fromArray($array);

        $this->assertEquals($builder->toArray(), $recreated->toArray());
    }
}
