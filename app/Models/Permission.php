<?php

namespace App\Models;

use Spatie\Permission\Models\Permission as SpatiePermission;

class Permission extends SpatiePermission
{
    protected $guarded = [];

    public function category()
    {
        return $this->belongsTo(PermissionCategory::class, 'category_id');
    }
}
