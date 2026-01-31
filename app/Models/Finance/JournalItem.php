<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JournalItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'journal_id',
        'coa_id',
        'debit',
        'credit'
    ];

    public function journal()
    {
        return $this->belongsTo(Journal::class);
    }

    public function coa()
    {
        return $this->belongsTo(Coa::class);
    }
}
