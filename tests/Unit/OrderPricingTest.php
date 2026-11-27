<?php

namespace Tests\Unit;

use App\Services\OrderPricing;
use InvalidArgumentException;
use Tests\TestCase;

class OrderPricingTest extends TestCase
{
    private OrderPricing $pricing;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pricing = new OrderPricing;
    }

    public function test_sim_optima_cost_2_50_on_all_sims_above_forty(): void
    {
        $this->assertSame(70.0, $this->pricing->simOptima(20)['cost']);
        $this->assertSame(140.0, $this->pricing->simOptima(40)['cost']);
        $this->assertSame(150.0, $this->pricing->simOptima(60)['cost']);
        $this->assertSame(200.0, $this->pricing->simOptima(80)['cost']);
    }

    public function test_sim_optima_only_in_multiples_of_twenty(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->pricing->simOptima(30);
    }

    public function test_plafond_discount_is_4_percent_up_to_300_and_6_percent_above(): void
    {
        $this->assertSame(96.0, $this->pricing->plafond(100)['cost']);
        $this->assertSame(288.0, $this->pricing->plafond(300)['cost']);
        $this->assertSame(376.0, $this->pricing->plafond(400)['cost']);
        $this->assertSame(1410.0, $this->pricing->plafond(1500)['cost']);
    }

    public function test_plafond_outside_limits_is_refused(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->pricing->plafond(2000);
    }

    public function test_optima_order_ships_only_when_there_are_sims(): void
    {
        $this->assertSame(96.0, $this->pricing->optimaOrder(100, null)['total']);
        $this->assertSame(76.0, $this->pricing->optimaOrder(null, 20)['total']);
        $this->assertSame(96.0 + 150.0 + 6.0, $this->pricing->optimaOrder(100, 60)['total']);
    }

    public function test_other_operators_packages_and_fixed_shipping(): void
    {
        $order = $this->pricing->otherOperatorsOrder(['Daily Telecom' => 30, 'Italia Power' => 100, 'E-mobile' => 0]);
        $this->assertSame(270.0 + 850.0 + 8.0, $order['total']);
        $this->assertCount(2, $order['lines']);
    }

    public function test_other_operators_refuse_unknown_packages(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->pricing->otherOperatorsOrder(['Daily Telecom' => 40]);
    }
}
