<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Coa extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'type', // Asset, Liability, Equity, Revenue, Expense
        'parent_id'
    ];

    public function children()
    {
        return $this->hasMany(Coa::class, 'parent_id');
    }

    public function parent()
    {
        return $this->belongsTo(Coa::class, 'parent_id');
    }
    
    public function journal_items()
    {
        return $this->hasMany(JournalItem::class);
    }
}
