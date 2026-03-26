<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Quotation extends Model
{
    use HasFactory;
    use SoftDeletes;


protected $fillable = [
    'quotation_no',
    'customer_id',
    'user_id',
    'type',
    'total_amount',
    'discount',
    'net_amount',
    'balance',
    'quotation_date',
    'status',
];
    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function products()
    {
        return $this->hasMany(QuotationProduct::class, 'quotation_id');
    }



}
