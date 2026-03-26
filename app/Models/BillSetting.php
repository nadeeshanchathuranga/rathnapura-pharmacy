<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BillSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'logo_path',
        'company_name',
        'address',
        'mobile_1',
        'mobile_2',
        'email',
        'website_url',
        'footer_description',
        'print_size',
    ];
}
