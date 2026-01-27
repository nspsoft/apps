<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Category extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['*'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    protected $fillable = [
        'company_id',
        'parent_id',
        'code',
        'name',
        'description',
        'type',
        'level',
        'path',
        'is_active',
    ];

    protected $casts = [
        'level' => 'integer',
        'is_active' => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /**
     * Get all descendants (recursive)
     */
    public function descendants(): HasMany
    {
        return $this->children()->with('descendants');
    }

    /**
     * Get all ancestor categories
     */
    public function ancestors(): array
    {
        $ancestors = [];
        $parent = $this->parent;

        while ($parent) {
            array_unshift($ancestors, $parent);
            $parent = $parent->parent;
        }

        return $ancestors;
    }

    /**
     * Get full path name
     */
    public function getFullPathAttribute(): string
    {
        $ancestors = $this->ancestors();
        $names = array_map(fn($cat) => $cat->name, $ancestors);
        $names[] = $this->name;

        return implode(' > ', $names);
    }

    protected static function booted(): void
    {
        static::saving(function (Category $category) {
            // Update level based on parent
            if ($category->parent_id) {
                $parent = Category::find($category->parent_id);
                $category->level = $parent ? $parent->level + 1 : 0;
                $category->path = $parent ? ($parent->path . '/' . $category->id) : (string) $category->id;
            } else {
                $category->level = 0;
                $category->path = (string) $category->id;
            }
        });
    }
}
