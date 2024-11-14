<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class Saoke extends Model
{
    use HasFactory;

    protected $table = 'saoke';

    protected $primaryKey = 'id';

    protected $fillable = [
        'date_time',
        'trans_no',
        'credit',
        'debit',
        'detail'
    ];
}
