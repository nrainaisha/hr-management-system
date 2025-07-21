<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    protected $fillable = [
        'employee_id',
        'name',
        'contact_info',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
} 