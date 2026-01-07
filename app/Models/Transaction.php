<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaction_id',
        'bank',
        'account_number',
        'amount',
        'qr_string',
        'qr_base64',
        'status',
    ];
}
