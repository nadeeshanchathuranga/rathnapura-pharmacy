<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Sale;
use App\Models\SalesProduct;
use App\Models\Product;
use App\Models\User;
use App\Models\SalesReturn;
use App\Models\SalesReturnProduct;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

class DiscountPartialReturnTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $product1;
    protected $product2;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test user
        $this->user = User::factory()->create();
        $this->actingAs($this->user);

        // Create test products
        $this->product1 = Product::factory()->create([
            'name' => 'Test Product 1',
            'retail_price' => 100.00,
            'shop_quantity_in_sales_unit' => 100,
            'return_product' => true,
        ]);

        $this->product2 = Product::factory()->create([
            'name' => 'Test Product 2',
            'retail_price' => 150.00,
            'shop_quantity_in_sales_unit' => 100,
            'return_product' => true,
        ]);
    }

    /**
     * Test Case 1: Single product with discount - partial return
     * Validates that after return, remaining items reflect correct values
     */
    public function test_single_product_partial_return_with_discount()
    {
        // Create a sale with discount
        $sale = Sale::create([
            'invoice_no' => 'TEST-001',
            'type' => 1,
            'user_id' => $this->user->id,
            'total_amount' => 200.00,  // 100 × 2
            'discount' => 70.00,
            'net_amount' => 130.00,
            'balance' => 0,
            'sale_date' => now(),
        ]);

        // Create sale product
        $salesProduct = SalesProduct::create([
            'sale_id' => $sale->id,
            'product_id' => $this->product1->id,
            'quantity' => 2,
            'price' => 100.00,
            'total' => 200.00,
            'discount_amount' => 70.00,      // All discount on this line
            'net_amount' => 130.00,          // 200 - 70
            'is_return' => false,
        ]);

        // Calculate expected values
        $expectedUnitPrice = 130.00 / 2; // = 65.00
        $expectedRefund = $expectedUnitPrice * 1; // = 65.00

        // Create return
        $return = SalesReturn::create([
            'sale_id' => $sale->id,
            'user_id' => $this->user->id,
            'return_date' => now(),
            'return_type' => SalesReturn::TYPE_PRODUCT_RETURN,
            'refund_amount' => $expectedRefund,
            'status' => SalesReturn::STATUS_APPROVED,
        ]);

        SalesReturnProduct::create([
            'sales_return_id' => $return->id,
            'product_id' => $this->product1->id,
            'quantity' => 1,
            'price' => $expectedUnitPrice,
            'total' => $expectedRefund,
        ]);

        // Assertions for return calculation
        $this->assertEquals(65.00, round($expectedUnitPrice, 2));
        $this->assertEquals(65.00, round($expectedRefund, 2));
        $this->assertEquals(65.00, $return->refund_amount);

        // Verify sale net amount after return
        $netAfterReturn = $sale->getNetAmountAfterReturnsAttribute();
        $this->assertEquals(65.00, round($netAfterReturn, 2));
        
        // NEW: Verify the sales_product record is updated correctly
        $salesProduct->refresh();
        $this->assertEquals(1, $salesProduct->quantity, 'Quantity should be reduced to 1');
        $this->assertEquals(100.00, $salesProduct->total, 'Total should be 100 (1 × 100)');
        $this->assertEquals(35.00, $salesProduct->discount_amount, 'Discount should be 35 (half of 70)');
        $this->assertEquals(65.00, $salesProduct->net_amount, 'Net amount should be 65 (100 - 35)');
        
        // NEW: Verify the sale record is updated correctly
        $sale->refresh();
        $this->assertEquals(100.00, $sale->total_amount, 'Sale total should be 100');
        $this->assertEquals(35.00, $sale->discount, 'Sale discount should be 35');
        $this->assertEquals(65.00, $sale->net_amount, 'Sale net should be 65');
    }

    /**
     * Test Case 2: Multiple products with discount - partial return
     */
    public function test_multiple_products_partial_return_with_proportional_discount()
    {
        // Create a sale with 2 products and discount
        $sale = Sale::create([
            'invoice_no' => 'TEST-002',
            'type' => 1,
            'user_id' => $this->user->id,
            'total_amount' => 350.00,  // 200 + 150
            'discount' => 70.00,
            'net_amount' => 280.00,
            'balance' => 0,
            'sale_date' => now(),
        ]);

        // Product 1: 100 × 2 = 200 (gets 40 discount)
        $discount1 = (200 / 350) * 70; // = 40.00
        $salesProduct1 = SalesProduct::create([
            'sale_id' => $sale->id,
            'product_id' => $this->product1->id,
            'quantity' => 2,
            'price' => 100.00,
            'total' => 200.00,
            'discount_amount' => round($discount1, 2),
            'net_amount' => round(200 - $discount1, 2),
            'is_return' => false,
        ]);

        // Product 2: 150 × 1 = 150 (gets 30 discount)
        $discount2 = (150 / 350) * 70; // = 30.00
        $salesProduct2 = SalesProduct::create([
            'sale_id' => $sale->id,
            'product_id' => $this->product2->id,
            'quantity' => 1,
            'price' => 150.00,
            'total' => 150.00,
            'discount_amount' => round($discount2, 2),
            'net_amount' => round(150 - $discount2, 2),
            'is_return' => false,
        ]);

        // Verify discount allocation
        $this->assertEquals(40.00, $salesProduct1->discount_amount);
        $this->assertEquals(30.00, $salesProduct2->discount_amount);
        $this->assertEquals(160.00, $salesProduct1->net_amount);
        $this->assertEquals(120.00, $salesProduct2->net_amount);

        // Return 1 unit of Product 1
        $unitPrice1 = 160.00 / 2; // = 80.00
        $return = SalesReturn::create([
            'sale_id' => $sale->id,
            'user_id' => $this->user->id,
            'return_date' => now(),
            'return_type' => SalesReturn::TYPE_PRODUCT_RETURN,
            'refund_amount' => $unitPrice1,
            'status' => SalesReturn::STATUS_APPROVED,
        ]);

        SalesReturnProduct::create([
            'sales_return_id' => $return->id,
            'product_id' => $this->product1->id,
            'quantity' => 1,
            'price' => $unitPrice1,
            'total' => $unitPrice1,
        ]);

        // Verify refund amount
        $this->assertEquals(80.00, round($unitPrice1, 2));
        
        // Remaining value should be: 80 (1 unit of product 1) + 120 (product 2) = 200
        $netAfterReturn = $sale->getNetAmountAfterReturnsAttribute();
        $this->assertEquals(200.00, round($netAfterReturn, 2));
    }

    /**
     * Test Case 3: Full return with discount
     */
    public function test_full_return_with_discount()
    {
        // Create a sale with discount
        $sale = Sale::create([
            'invoice_no' => 'TEST-003',
            'type' => 1,
            'user_id' => $this->user->id,
            'total_amount' => 200.00,
            'discount' => 70.00,
            'net_amount' => 130.00,
            'balance' => 0,
            'sale_date' => now(),
        ]);

        $salesProduct = SalesProduct::create([
            'sale_id' => $sale->id,
            'product_id' => $this->product1->id,
            'quantity' => 2,
            'price' => 100.00,
            'total' => 200.00,
            'discount_amount' => 70.00,
            'net_amount' => 130.00,
            'is_return' => false,
        ]);

        // Return all 2 units
        $return = SalesReturn::create([
            'sale_id' => $sale->id,
            'user_id' => $this->user->id,
            'return_date' => now(),
            'return_type' => SalesReturn::TYPE_PRODUCT_RETURN,
            'refund_amount' => 130.00,  // Full net amount
            'status' => SalesReturn::STATUS_APPROVED,
        ]);

        SalesReturnProduct::create([
            'sales_return_id' => $return->id,
            'product_id' => $this->product1->id,
            'quantity' => 2,
            'price' => 65.00,  // 130 / 2
            'total' => 130.00,
        ]);

        // Verify full refund
        $this->assertEquals(130.00, $return->refund_amount);
        
        // Net after return should be 0
        $netAfterReturn = $sale->getNetAmountAfterReturnsAttribute();
        $this->assertEquals(0.00, round($netAfterReturn, 2));
    }

    /**
     * Test Case 4: No discount scenario (baseline)
     */
    public function test_partial_return_without_discount()
    {
        // Create a sale without discount
        $sale = Sale::create([
            'invoice_no' => 'TEST-004',
            'type' => 1,
            'user_id' => $this->user->id,
            'total_amount' => 200.00,
            'discount' => 0,
            'net_amount' => 200.00,
            'balance' => 0,
            'sale_date' => now(),
        ]);

        $salesProduct = SalesProduct::create([
            'sale_id' => $sale->id,
            'product_id' => $this->product1->id,
            'quantity' => 2,
            'price' => 100.00,
            'total' => 200.00,
            'discount_amount' => 0,
            'net_amount' => 200.00,
            'is_return' => false,
        ]);

        // Return 1 unit
        $return = SalesReturn::create([
            'sale_id' => $sale->id,
            'user_id' => $this->user->id,
            'return_date' => now(),
            'return_type' => SalesReturn::TYPE_PRODUCT_RETURN,
            'refund_amount' => 100.00,
            'status' => SalesReturn::STATUS_APPROVED,
        ]);

        SalesReturnProduct::create([
            'sales_return_id' => $return->id,
            'product_id' => $this->product1->id,
            'quantity' => 1,
            'price' => 100.00,
            'total' => 100.00,
        ]);

        // Verify refund (should be full price when no discount)
        $this->assertEquals(100.00, $return->refund_amount);
        
        // Remaining should be 100
        $netAfterReturn = $sale->getNetAmountAfterReturnsAttribute();
        $this->assertEquals(100.00, round($netAfterReturn, 2));
    }

    /**
     * Test Case 5: Sale model helper methods
     */
    public function test_sale_model_return_summary()
    {
        $sale = Sale::create([
            'invoice_no' => 'TEST-005',
            'type' => 1,
            'user_id' => $this->user->id,
            'total_amount' => 200.00,
            'discount' => 70.00,
            'net_amount' => 130.00,
            'balance' => 0,
            'sale_date' => now(),
        ]);

        $salesProduct = SalesProduct::create([
            'sale_id' => $sale->id,
            'product_id' => $this->product1->id,
            'quantity' => 2,
            'price' => 100.00,
            'total' => 200.00,
            'discount_amount' => 70.00,
            'net_amount' => 130.00,
            'is_return' => false,
        ]);

        // Create return
        $return = SalesReturn::create([
            'sale_id' => $sale->id,
            'user_id' => $this->user->id,
            'return_date' => now(),
            'return_type' => SalesReturn::TYPE_PRODUCT_RETURN,
            'refund_amount' => 65.00,
            'status' => SalesReturn::STATUS_APPROVED,
        ]);

        SalesReturnProduct::create([
            'sales_return_id' => $return->id,
            'product_id' => $this->product1->id,
            'quantity' => 1,
            'price' => 65.00,
            'total' => 65.00,
        ]);

        // Test return summary
        $summary = $sale->getReturnSummary();
        
        $this->assertEquals(200.00, $summary['original_total']);
        $this->assertEquals(70.00, $summary['original_discount']);
        $this->assertEquals(130.00, $summary['original_net']);
        $this->assertEquals(65.00, $summary['returned_amount']);
        $this->assertEquals(65.00, $summary['current_net']);
        $this->assertEquals(1, $summary['returns_count']);
    }

    /**
     * Test Case 6: Your exact example scenario
     * Product: Test Product, Unit price: 100, Qty: 3, Subtotal: 300
     * Discount: 60, Final: 240, Effective unit price: 80
     * Return 1 unit should refund 80, leaving 2 units with net 160
     */
    public function test_exact_example_scenario()
    {
        // Create sale matching your example
        $sale = Sale::create([
            'invoice_no' => 'TEST-EXAMPLE',
            'type' => 1,
            'user_id' => $this->user->id,
            'total_amount' => 300.00,  // 100 × 3
            'discount' => 60.00,
            'net_amount' => 240.00,
            'balance' => 0,
            'sale_date' => now(),
        ]);

        $salesProduct = SalesProduct::create([
            'sale_id' => $sale->id,
            'product_id' => $this->product1->id,
            'quantity' => 3,
            'price' => 100.00,
            'total' => 300.00,
            'discount_amount' => 60.00,
            'net_amount' => 240.00,
            'is_return' => false,
        ]);

        // Verify effective unit price
        $effectiveUnitPrice = $salesProduct->net_amount / $salesProduct->quantity;
        $this->assertEquals(80.00, $effectiveUnitPrice);

        // Return 1 unit
        $return = SalesReturn::create([
            'sale_id' => $sale->id,
            'user_id' => $this->user->id,
            'return_date' => now(),
            'return_type' => SalesReturn::TYPE_PRODUCT_RETURN,
            'refund_amount' => 80.00,
            'status' => SalesReturn::STATUS_APPROVED,
        ]);

        SalesReturnProduct::create([
            'sales_return_id' => $return->id,
            'product_id' => $this->product1->id,
            'quantity' => 1,
            'price' => 80.00,
            'total' => 80.00,
        ]);

        // CRITICAL VALIDATIONS: After returning 1 unit
        $salesProduct->refresh();
        $sale->refresh();

        // 1. Remaining quantity should be 2
        $this->assertEquals(2, $salesProduct->quantity);

        // 2. Remaining net_amount should be 160 (not 240!)
        $this->assertEquals(160.00, $salesProduct->net_amount);

        // 3. Remaining discount should be 40 (not 60!)
        $this->assertEquals(40.00, $salesProduct->discount_amount);

        // 4. Remaining total should be 200 (100 × 2)
        $this->assertEquals(200.00, $salesProduct->total);

        // 5. Sale totals should be updated
        $this->assertEquals(200.00, $sale->total_amount);
        $this->assertEquals(40.00, $sale->discount);
        $this->assertEquals(160.00, $sale->net_amount);

        // 6. If another unit is returned, it should use 80 again (not 120!)
        $newEffectiveUnitPrice = $salesProduct->net_amount / $salesProduct->quantity;
        $this->assertEquals(80.00, $newEffectiveUnitPrice);
    }
}
