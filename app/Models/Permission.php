<?php

namespace App\Models;

use Spatie\Permission\Models\Permission as SpatiePermission;
use App\Models\PermissionCategory;

class Permission extends SpatiePermission
{
    public function category()
    {
        return $this->belongsTo(PermissionCategory::class, 'permission_category_id');
    }
}
