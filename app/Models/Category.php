<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'parent_id',
        'status',
    ];

    protected $appends = ['hierarchy_string'];

    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id','id');
    }

    // Recursive parent loading for hierarchy
    public function ancestors()
    {
        return $this->belongsTo(Category::class, 'parent_id')->with('ancestors');
    }

    public function getHierarchyStringAttribute(): string
    {
        $hierarchy = [];
        $category = $this;

        // Load parent recursively
        while ($category->parent_id) {
            $parent = Category::find($category->parent_id);
            if ($parent) {
                $hierarchy[] = $parent->name;
                $category = $parent;
            } else {
                break;
            }
        }

        return implode(' → ', array_reverse($hierarchy)) ?: '-';
    }
}
