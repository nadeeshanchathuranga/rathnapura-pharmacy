<?php

namespace App\Http\Controllers;
use App\Models\Product;
use App\Models\Quotation;
use App\Models\QuotationProduct;
use App\Models\Customer;
use App\Models\CompanyInformation;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Type;
use App\Models\BillSetting;
use App\Models\Discount;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;



class QuotationController extends Controller
{
   public function index()
{
    // Generate next quotation number
    $lastQuotation = Quotation::latest('id')->first();
    $billSetting   = BillSetting::latest('id')->first();

    // Example: QTN-000001, QTN-000002, ...
    $nextQuotationNo = $lastQuotation
        ? 'QTN-' . str_pad($lastQuotation->id + 1, 6, '0', STR_PAD_LEFT)
        : 'QTN-000001';



    $products = Product::select(
            'id',
            'name',
            'barcode',
            'retail_price',
            'wholesale_price',
            'shop_quantity_in_sales_unit',
            'shop_low_stock_margin',
            'image',
            'brand_id',
            'category_id',
            'type_id',
            'discount_id'
        )

        ->with(['brand:id,name', 'category:id,name', 'type:id,name', 'discount:id,name'])
        ->orderByRaw('CASE WHEN shop_quantity_in_sales_unit <= shop_low_stock_margin THEN 1 ELSE 0 END')
        ->orderBy('name')
        ->get();



  $customers = Customer::select('id', 'name', 'status')
    ->orderBy('id', 'desc')
    ->get();

$brands = Brand::select('id', 'name')
    ->orderBy('id', 'desc')
    ->get();

$categories = Category::with('parent')
    ->select('id', 'name', 'parent_id')
    ->orderBy('id', 'desc')
    ->get();




$types = Type::select('id', 'name')
    ->orderBy('id', 'desc')
    ->get();

$discounts = Discount::select('id', 'name')
    ->orderBy('id', 'desc')
    ->get();

    $currencySymbol  = CompanyInformation::first();

    return Inertia::render('Quotations/Index', [   // <- change to your Vue/React page name
        'quotation_no'  => $nextQuotationNo,
        'customers'     => $customers,
        'products'      => $products,
        'brands'        => $brands,
        'categories'    => $categories,
        'types'         => $types,
        'billSetting'   => $billSetting,
        'discounts'     => $discounts,
        'currencySymbol'=> $currencySymbol,
    ]);
}


 public function store(Request $request)
{


    $request->validate([
        'quotation_no'   => 'required|unique:quotations,quotation_no',
        'customer_type'  => 'required|in:retail,wholesale',
        'customer_id'    => 'nullable|exists:customers,id',
        'quotation_date' => 'required|date',

        'items' => 'required|array|min:1',
        'items.*.product_id' => 'required|exists:products,id',
        'items.*.quantity'   => 'required|numeric|min:1',
        'items.*.price'      => 'required|numeric|min:0',

        'discount' => 'nullable|numeric|min:0',
    ]);

    try {
        DB::beginTransaction();

        $quotationNo   = trim($request->quotation_no);
        $quotationDate = $request->quotation_date;
        $customerType  = strtolower(trim($request->customer_type));
        $discount      = max(0, (float) ($request->discount ?? 0));

        // ✅ Better null handling
        $customerId = $request->filled('customer_id')
            ? (int) $request->customer_id
            : null;

        $totalAmount = 0;
        $itemsData   = [];

        foreach ($request->items as $item) {
            $qty   = (float) $item['quantity'];
            $price = (float) $item['price'];
            $itemTotal = $qty * $price;

            $totalAmount += $itemTotal;

            $itemsData[] = [
                'product_id' => (int) $item['product_id'],
                'quantity'   => $qty,
                'price'      => $price,
                'total'      => $itemTotal,
            ];
        }

        // customer type → int
        $type = $customerType === 'wholesale' ? 2 : 1;

        $quotation = Quotation::create([
            'quotation_no'   => $quotationNo,
            'type'           => $type,
            'customer_id'    => $customerId, // ✅ null safe
            'user_id'        => Auth::id(),
            'total_amount'   => round($totalAmount, 2),
            'discount'       => round($discount, 2),
            'quotation_date' => $quotationDate,
            'status'         => 1,
        ]);

        foreach ($itemsData as $item) {
            QuotationProduct::create([
                'quotation_id' => $quotation->getKey(),
                'product_id'   => $item['product_id'],
                'quantity'     => $item['quantity'],
                'price'        => $item['price'],
                'total'        => round($item['total'], 2),
            ]);
        }

        DB::commit();

        return redirect()
            ->route('quotations.index')
            ->with('success', 'Quotation created successfully! No: ' . $quotation->quotation_no);

    } catch (\Exception $e) {


        DB::rollBack();
        Log::error('Quotation store error', ['error' => $e->getMessage()]);

        // Remove dd() for production
        return back()
            ->withInput()
            ->with('error', 'Quotation creation failed: ' . $e->getMessage());
    }
}




public function editQuotation()
{

    // Get all quotations for the dropdown selector
    $quotations = Quotation::select('id', 'quotation_no', 'quotation_date', 'total_amount', 'customer_id')
        ->with('customer:id,name')
        ->where('status', 1)
        ->orderBy('id', 'desc')
        ->get();

    $billSetting = BillSetting::latest('id')->first();

    $products = Product::select(
            'id',
            'name',
            'barcode',
            'retail_price',
            'wholesale_price',
            'shop_quantity_in_sales_unit',
            'shop_low_stock_margin',
            'image',
            'brand_id',
            'category_id',
            'type_id',
            'discount_id'
        )
        ->with(['brand:id,name', 'category:id,name', 'type:id,name', 'discount:id,name'])
        ->orderBy('name')
        ->get();


  $customers = Customer::select('id', 'name', 'status')
    ->orderBy('id', 'desc')
    ->get();

$brands = Brand::select('id', 'name')
    ->orderBy('id', 'desc')
    ->get();

$categories = Category::with('parent')
    ->select('id', 'name', 'parent_id')
    ->orderBy('id', 'desc')
    ->get();




$types = Type::select('id', 'name')
    ->orderBy('id', 'desc')
    ->get();

$discounts = Discount::select('id', 'name')
    ->orderBy('id', 'desc')
    ->get();

    $currencySymbol = CompanyInformation::first();

    return Inertia::render('Quotations/Edit', [
        'quotation' => null,
        'quotations' => $quotations,
        'customers' => $customers,
        'products' => $products,
        'brands' => $brands,
        'categories' => $categories,
        'types' => $types,
        'billSetting' => $billSetting,
        'discounts' => $discounts,
        'currencySymbol' => $currencySymbol,
    ]);
}

/**
 * Show the form for editing the specified quotation.
 */
public function edit($id)
{
    $quotation = Quotation::with(['products.product', 'customer'])->findOrFail($id);

    // Get all quotations for the dropdown selector
    $quotations = Quotation::select('id', 'quotation_no', 'quotation_date', 'total_amount', 'customer_id')
        ->with('customer:id,name')
        ->orderBy('id', 'desc')
        ->get();

    $billSetting = BillSetting::latest('id')->first();


    $products = Product::select(
            'id',
            'name',
            'barcode',
            'retail_price',
            'wholesale_price',
            'shop_quantity_in_sales_unit',
            'shop_low_stock_margin',
            'image',
            'brand_id',
            'category_id',
            'type_id',
            'discount_id'
        )
        ->with(['brand:id,name', 'category:id,name', 'type:id,name', 'discount:id,name'])
        ->orderBy('name')
        ->get();
  $customers = Customer::select('id', 'name', 'status')
    ->orderBy('id', 'desc')
    ->get();

$brands = Brand::select('id', 'name')
    ->orderBy('id', 'desc')
    ->get();

$categories = Category::with('parent')
    ->select('id', 'name', 'parent_id')
    ->orderBy('id', 'desc')
    ->get();




$types = Type::select('id', 'name')
    ->orderBy('id', 'desc')
    ->get();

$discounts = Discount::select('id', 'name')
    ->orderBy('id', 'desc')
    ->get();
    $currencySymbol = CompanyInformation::first();

    // Prepare quotation items for the form
    $quotationItems = $quotation->products->map(function ($item) {
        return [
            'product_id' => $item->product_id,
            'product_name' => $item->product->name ?? 'Unknown Product',
            'quantity' => $item->quantity,
            'price' => $item->price,
            'total' => $item->total,
        ];
    });

    return Inertia::render('Quotations/Edit', [
        'quotation' => [
            'id' => $quotation->id,
            'quotation_no' => $quotation->quotation_no,
            'customer_id' => $quotation->customer_id,
            'customer_type' => $quotation->type == 2 ? 'wholesale' : 'retail',
            'quotation_date' => $quotation->quotation_date,
            'total_amount' => $quotation->total_amount,
            'discount' => $quotation->discount,
            'items' => $quotationItems,
        ],
        'quotations' => $quotations,
        'customers' => $customers,
        'products' => $products,
        'brands' => $brands,
        'categories' => $categories,
        'types' => $types,
        'billSetting' => $billSetting,
        'discounts' => $discounts,
        'currencySymbol' => $currencySymbol,
    ]);
}

/**
 * Update the specified quotation in storage.
 */
 public function update(Request $request, $id)
{
    $quotation = Quotation::findOrFail($id);

    $request->validate([
        'customer_type'  => 'required|in:retail,wholesale',
        'customer_id'    => 'nullable|exists:customers,id',
        'quotation_date' => 'required|date',

        'items' => 'required|array|min:1',
        'items.*.product_id' => 'required|exists:products,id',
        'items.*.quantity'   => 'required|numeric|min:1',
        'items.*.price'      => 'required|numeric|min:0',

        'discount' => 'nullable|numeric|min:0',
    ]);

    try {
        DB::beginTransaction();

        $quotationDate = $request->quotation_date;
        $customerType  = strtolower(trim($request->customer_type));
        $discount      = max(0, (float) ($request->discount ?? 0));

        // ✅ Same null-safe handling as store()
        $customerId = $request->filled('customer_id')
            ? (int) $request->customer_id
            : null;

        $totalAmount = 0;
        $itemsData   = [];

        foreach ($request->items as $item) {
            $qty   = (float) $item['quantity'];
            $price = (float) $item['price'];
            $itemTotal = $qty * $price;

            $totalAmount += $itemTotal;

            $itemsData[] = [
                'product_id' => (int) $item['product_id'],
                'quantity'   => $qty,
                'price'      => $price,
                'total'      => $itemTotal,
            ];
        }

        // customer type → int (same as store)
        $type = $customerType === 'wholesale' ? 2 : 1;

        // 🔹 Update quotation
        $quotation->update([
            'type'           => $type,
            'customer_id'    => $customerId, // ✅ null safe
            'user_id'        => Auth::id(),
            'total_amount'   => round($totalAmount, 2),
            'discount'       => round($discount, 2),
            'quotation_date' => $quotationDate,
            'status'         => 1,
        ]);

        // 🔹 Replace quotation products
        QuotationProduct::where('quotation_id', $quotation->getKey())->delete();

        foreach ($itemsData as $item) {
            QuotationProduct::create([
                'quotation_id' => $quotation->getKey(),
                'product_id'   => $item['product_id'],
                'quantity'     => $item['quantity'],
                'price'        => $item['price'],
                'total'        => round($item['total'], 2),
            ]);
        }

        DB::commit();

        return redirect()
            ->route('quotations.edit', $quotation->getKey())
            ->with('success', 'Quotation updated successfully! No: ' . $quotation->quotation_no);

    } catch (\Exception $e) {

        DB::rollBack();
        Log::error('Quotation update error', ['error' => $e->getMessage()]);

        return back()
            ->withInput()
            ->with('error', 'Quotation update failed: ' . $e->getMessage());
    }
}



}
