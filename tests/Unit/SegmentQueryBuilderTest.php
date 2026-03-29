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

    public function test_can_build_query_with_between_condition(): void
    {
        Customer::factory()->create(['average_balance' => 5000]);
        Customer::factory()->create(['average_balance' => 15000]);
        Customer::factory()->create(['average_balance' => 25000]);
        Customer::factory()->create(['average_balance' => 35000]);

        $condition = new Condition(
            field: 'average_balance',
            operator: OperatorType::Between,
            value: 10000,
            valueMax: 30000
        );

        $group = new ConditionGroup('AND', [$condition]);
        $builder = new SegmentQueryBuilder([$group]);

        $results = $builder->build()->get();

        $this->assertCount(2, $results);
        $this->assertTrue($results->every(fn ($c) => $c->average_balance >= 10000 && $c->average_balance <= 30000));
    }

    public function test_can_convert_between_condition_to_and_from_array(): void
    {
        $condition = new Condition(
            field: 'average_balance',
            operator: OperatorType::Between,
            value: 10000,
            valueMax: 30000
        );

        $group = new ConditionGroup('AND', [$condition]);
        $builder = new SegmentQueryBuilder([$group]);

        $array = $builder->toArray();
        $recreated = SegmentQueryBuilder::fromArray($array);

        $this->assertEquals($builder->toArray(), $recreated->toArray());
        $this->assertEquals(10000, $recreated->conditionGroups[0]->conditions[0]->value);
        $this->assertEquals(30000, $recreated->conditionGroups[0]->conditions[0]->valueMax);
    }

    public function test_can_build_query_with_or_between_groups(): void
    {
        Customer::factory()->create(['city' => 'Bordeaux', 'average_balance' => 5000]);
        Customer::factory()->create(['city' => 'Paris', 'average_balance' => 15000]);
        Customer::factory()->create(['city' => 'Lyon', 'average_balance' => 25000]);

        // Group 1: city = Bordeaux (nextOperator = OR)
        $group1 = new ConditionGroup(
            logicalOperator: 'AND',
            conditions: [
                new Condition(
                    field: 'city',
                    operator: OperatorType::Equals,
                    value: 'Bordeaux'
                ),
            ],
            nextOperator: 'OR'
        );

        // Group 2: average_balance > 20000
        $group2 = new ConditionGroup(
            logicalOperator: 'AND',
            conditions: [
                new Condition(
                    field: 'average_balance',
                    operator: OperatorType::GreaterThan,
                    value: 20000
                ),
            ]
        );

        $builder = new SegmentQueryBuilder([$group1, $group2]);
        $results = $builder->build()->get();

        // Should return Bordeaux OR balance > 20000 = 2 customers (Bordeaux and Lyon)
        $this->assertCount(2, $results);
    }

    public function test_can_build_query_with_and_between_groups(): void
    {
        Customer::factory()->create(['city' => 'Bordeaux', 'average_balance' => 5000]);
        Customer::factory()->create(['city' => 'Bordeaux', 'average_balance' => 15000]);
        Customer::factory()->create(['city' => 'Paris', 'average_balance' => 25000]);

        // Group 1: city = Bordeaux (nextOperator = AND or null)
        $group1 = new ConditionGroup(
            logicalOperator: 'AND',
            conditions: [
                new Condition(
                    field: 'city',
                    operator: OperatorType::Equals,
                    value: 'Bordeaux'
                ),
            ],
            nextOperator: 'AND'
        );

        // Group 2: average_balance > 10000
        $group2 = new ConditionGroup(
            logicalOperator: 'AND',
            conditions: [
                new Condition(
                    field: 'average_balance',
                    operator: OperatorType::GreaterThan,
                    value: 10000
                ),
            ]
        );

        $builder = new SegmentQueryBuilder([$group1, $group2]);
        $results = $builder->build()->get();

        // Should return Bordeaux AND balance > 10000 = 1 customer
        $this->assertCount(1, $results);
        $this->assertEquals('Bordeaux', $results->first()->city);
        $this->assertTrue($results->first()->average_balance > 10000);
    }

    public function test_can_convert_next_operator_to_and_from_array(): void
    {
        $group1 = new ConditionGroup(
            logicalOperator: 'AND',
            conditions: [
                new Condition(
                    field: 'city',
                    operator: OperatorType::Equals,
                    value: 'Bordeaux'
                ),
            ],
            nextOperator: 'OR'
        );

        $group2 = new ConditionGroup(
            logicalOperator: 'AND',
            conditions: [
                new Condition(
                    field: 'age',
                    operator: OperatorType::GreaterThan,
                    value: 30
                ),
            ]
        );

        $builder = new SegmentQueryBuilder([$group1, $group2]);
        $array = $builder->toArray();
        $recreated = SegmentQueryBuilder::fromArray($array);

        $this->assertEquals($builder->toArray(), $recreated->toArray());
        $this->assertEquals('OR', $recreated->conditionGroups[0]->nextOperator);
        $this->assertNull($recreated->conditionGroups[1]->nextOperator);
    }
}
