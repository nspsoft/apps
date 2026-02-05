<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class RfqSupplier extends Pivot
{
    protected $table = 'rfq_suppliers';

    protected $fillable = [
        'rfq_id',
        'supplier_id',
        'status',
        'viewed_at',
        'responded_at',
    ];

    protected $casts = [
        'viewed_at' => 'datetime',
        'responded_at' => 'datetime',
    ];

    public function rfq()
    {
        return $this->belongsTo(Rfq::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }
}
