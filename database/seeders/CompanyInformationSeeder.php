<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\CompanyInformation;

class CompanyInformationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        CompanyInformation::create([
            'company_name' => 'ABC Company',
            'address'      => 'Colombo, Sri Lanka',
            'phone'        => '0771234567',
            'email'        => 'info@abc.com',
            'website'      => 'https://abc.com',
            'logo'         => null, 
            'currency'     => 'LKR',
        ]);
    }
}
