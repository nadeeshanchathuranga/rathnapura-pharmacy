<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Supplier;

class SupplierSeeder extends Seeder
{
    public function run(): void
    {
       Supplier::insert([
    [
        'id' => 1,
        'name' => 'Default Supplier',
        'email' => null,
        'phone_number' => null,
        'address' => null,
        'status' => '2',
    ],
    [
        'id' => 2,
        'name' => 'Global Traders Ltd',
        'email' => 'contact@globaltraders.com',
        'phone_number' => '9876500001',
        'address' => 'New York, USA',
        'status' => '1',
    ],
    [
        'id' => 3,
        'name' => 'Sunrise Suppliers',
        'email' => 'info@sunrisesuppliers.com',
        'phone_number' => '9876500002',
        'address' => 'Los Angeles, USA',
        'status' => '1',
    ],
    [
        'id' => 4,
        'name' => 'Metro Wholesale',
        'email' => 'sales@metrowholesale.com',
        'phone_number' => '9876500003',
        'address' => 'Chicago, USA',
        'status' => '1',
    ],
    [
        'id' => 5,
        'name' => 'Prime Distributors',
        'email' => 'support@primedistributors.com',
        'phone_number' => '9876500004',
        'address' => 'Houston, USA',
        'status' => '1',
    ],
    [
        'id' => 6,
        'name' => 'Evergreen Supplies',
        'email' => 'hello@evergreensupplies.com',
        'phone_number' => '9876500005',
        'address' => 'Phoenix, USA',
        'status' => '1',
    ],
    [
        'id' => 7,
        'name' => 'Urban Mart Suppliers',
        'email' => 'urban@mart.com',
        'phone_number' => '9876500006',
        'address' => 'San Diego, USA',
        'status' => '1',
    ],
    [
        'id' => 8,
        'name' => 'Fresh Goods Co',
        'email' => 'sales@freshgoods.com',
        'phone_number' => '9876500007',
        'address' => 'Dallas, USA',
        'status' => '1',
    ],
    [
        'id' => 9,
        'name' => 'Tech Parts Supply',
        'email' => 'orders@techparts.com',
        'phone_number' => '9876500008',
        'address' => 'San Jose, USA',
        'status' => '1',
    ],
    [
        'id' => 10,
        'name' => 'Daily Needs Supplier',
        'email' => 'contact@dailyneeds.com',
        'phone_number' => '9876500009',
        'address' => 'Austin, USA',
        'status' => '1',
    ],
]);

    }
}
