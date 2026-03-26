<?php

namespace Database\Seeders;
use App\Models\BillSetting;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class BillSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        BillSetting::create([
             'logo_path'         => null,
            'company_name'       => 'Your Company Name',
            'address'            => '123 Main Street, City, Country',
            'mobile_1'           => '0123456789',
            'mobile_2'           => null,
            'email'              => 'info@company.com',
            'website_url'        => 'https://company.com',
            'footer_description' => 'Thank you for your business!',
            'print_size'         => '80mm', // default value
        ]);
    }
}
