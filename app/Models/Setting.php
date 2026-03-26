<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
   use HasFactory;

    protected $fillable = [
        'company_name',
        'company_email',
        'company_contact',
        'company_address',
        'company_logo',
        'website',
        'status'
    ];
}
