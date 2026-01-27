<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PayrollItem extends Model
{
    protected $table = 'hr_payroll_items';
    protected $fillable = ['payroll_id', 'name', 'amount', 'type'];

    public function payroll(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Payroll::class);
    }
}
