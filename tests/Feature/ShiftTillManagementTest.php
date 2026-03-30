<?php

namespace Tests\Feature;

use App\Models\Sale;
use App\Models\Shift;
use App\Models\TillTransaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShiftTillManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_start_shift_with_zero_opening_amount(): void
    {
        $user = User::factory()->create(['role' => 2]);
        $this->actingAs($user);

        $response = $this->post(route('shift-management.start'), [
            'opening_till_amount' => 0,
        ]);

        $response->assertRedirect(route('shift-management.index'));

        $this->assertDatabaseHas('shifts', [
            'user_id' => $user->id,
            'status' => Shift::STATUS_OPEN,
            'opening_till_amount' => '0.00',
        ]);
    }

    public function test_user_cannot_start_second_shift_while_open_shift_exists(): void
    {
        $user = User::factory()->create(['role' => 2]);
        $this->actingAs($user);

        Shift::create([
            'user_id' => $user->id,
            'division_id' => $user->division_id,
            'start_time' => now()->subHour(),
            'status' => Shift::STATUS_OPEN,
            'opening_till_amount' => 50,
        ]);

        $response = $this->post(route('shift-management.start'), [
            'opening_till_amount' => 25,
        ]);

        $response->assertSessionHas('error');
        $this->assertEquals(1, Shift::query()->where('user_id', $user->id)->where('status', Shift::STATUS_OPEN)->count());
    }

    public function test_till_cash_out_is_blocked_when_amount_exceeds_available_cash(): void
    {
        $user = User::factory()->create(['role' => 2]);
        $this->actingAs($user);

        $shift = Shift::create([
            'user_id' => $user->id,
            'division_id' => $user->division_id,
            'start_time' => now()->subHour(),
            'status' => Shift::STATUS_OPEN,
            'opening_till_amount' => 100,
        ]);

        $response = $this->from(route('till-management.index'))->post(route('till-management.store'), [
            'type' => TillTransaction::TYPE_CASH_OUT,
            'amount' => 150,
            'note' => 'Petty cash withdrawal',
        ]);

        $response->assertRedirect(route('till-management.index'));
        $response->assertSessionHasErrors('amount');

        $this->assertDatabaseMissing('till_transactions', [
            'shift_id' => $shift->id,
            'type' => TillTransaction::TYPE_CASH_OUT,
            'amount' => '150.00',
        ]);
    }

    public function test_shift_end_calculates_expected_and_variance(): void
    {
        $user = User::factory()->create(['role' => 2]);
        $this->actingAs($user);

        $shift = Shift::create([
            'user_id' => $user->id,
            'division_id' => $user->division_id,
            'start_time' => now()->subHours(2),
            'status' => Shift::STATUS_OPEN,
            'opening_till_amount' => 100,
        ]);

        TillTransaction::create([
            'shift_id' => $shift->id,
            'user_id' => $user->id,
            'type' => TillTransaction::TYPE_CASH_IN,
            'amount' => 20,
            'note' => 'Float top-up',
            'transaction_time' => now()->subHour(),
        ]);

        TillTransaction::create([
            'shift_id' => $shift->id,
            'user_id' => $user->id,
            'type' => TillTransaction::TYPE_CASH_OUT,
            'amount' => 10,
            'note' => 'Courier payment',
            'transaction_time' => now()->subMinutes(50),
        ]);

        Sale::create([
            'invoice_no' => 'SHIFT-TEST-001',
            'type' => 1,
            'customer_id' => null,
            'user_id' => $user->id,
            'division_id' => $user->division_id,
            'shift_id' => $shift->id,
            'total_amount' => 50,
            'discount' => 0,
            'net_amount' => 50,
            'paid_amount' => 50,
            'balance' => 0,
            'paid_status' => 1,
            'payment1_type' => 0,
            'payment1_amount' => 50,
            'sale_date' => now()->toDateString(),
        ]);

        $response = $this->post(route('shift-management.end'), [
            'closing_cash_amount' => 170,
        ]);

        $response->assertRedirect(route('shift-management.index'));

        $shift->refresh();

        $this->assertSame(Shift::STATUS_CLOSED, $shift->status);
        $this->assertEquals(170.00, (float) $shift->closing_cash_amount);
        $this->assertEquals(160.00, (float) $shift->expected_closing_amount);
        $this->assertEquals(10.00, (float) $shift->variance_amount);
        $this->assertNotNull($shift->end_time);
    }
}
