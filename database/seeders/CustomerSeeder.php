<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Customer;

class CustomerSeeder extends Seeder
{
    public function run(): void
    {
        Customer::insert([
    [
        'id' => 1,
        'name' => 'Default Customer',
        'email' => null,
        'phone_number' => null,
        'address' => null,
        'credit_limit' => 0,
        'status' => '2',
    ],
    [
        'id' => 2,
        'name' => 'John Doe',
        'email' => 'john.doe@example.com',
        'phone_number' => '9876543210',
        'address' => 'New York, USA',
        'credit_limit' => 5000,
        'status' => '1',
    ],
    [
        'id' => 3,
        'name' => 'Jane Smith',
        'email' => 'jane.smith@example.com',
        'phone_number' => '9876543211',
        'address' => 'Los Angeles, USA',
        'credit_limit' => 7000,
        'status' => '1',
    ],
    [
        'id' => 4,
        'name' => 'Michael Brown',
        'email' => 'michael.brown@example.com',
        'phone_number' => '9876543212',
        'address' => 'Chicago, USA',
        'credit_limit' => 3000,
        'status' => '1',
    ],
    [
        'id' => 5,
        'name' => 'Emily Davis',
        'email' => 'emily.davis@example.com',
        'phone_number' => '9876543213',
        'address' => 'Houston, USA',
        'credit_limit' => 6000,
        'status' => '1',
    ],
    [
        'id' => 6,
        'name' => 'Daniel Wilson',
        'email' => 'daniel.wilson@example.com',
        'phone_number' => '9876543214',
        'address' => 'Phoenix, USA',
        'credit_limit' => 4000,
        'status' => '1',
    ],
    [
        'id' => 7,
        'name' => 'Sophia Miller',
        'email' => 'sophia.miller@example.com',
        'phone_number' => '9876543215',
        'address' => 'San Diego, USA',
        'credit_limit' => 8000,
        'status' => '1',
    ],
    [
        'id' => 8,
        'name' => 'James Anderson',
        'email' => 'james.anderson@example.com',
        'phone_number' => '9876543216',
        'address' => 'Dallas, USA',
        'credit_limit' => 4500,
        'status' => '1',
    ],
    [
        'id' => 9,
        'name' => 'Olivia Thomas',
        'email' => 'olivia.thomas@example.com',
        'phone_number' => '9876543217',
        'address' => 'San Jose, USA',
        'credit_limit' => 5500,
        'status' => '1',
    ],
    [
        'id' => 10,
        'name' => 'William Taylor',
        'email' => 'william.taylor@example.com',
        'phone_number' => '9876543218',
        'address' => 'Austin, USA',
        'credit_limit' => 6500,
        'status' => '1',
    ],
]);

    }
}
