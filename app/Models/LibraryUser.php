<?php

namespace App\Models;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Traits\HasRoles;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Traits\HasPermissions;
use App\Traits\HasLibraryUserPermissions;

class LibraryUser extends Authenticatable
{
    use HasFactory;
 
     use HasRoles;
     use HasLibraryUserPermissions;
    protected $guarded = []; 
    protected $guard_name = 'library_user';
    protected $casts = [
        'branch_id' => 'array',  // This ensures 'branch_id' is treated as an array
    ];
    public function getBranchIdAttribute($value)
    {
        return json_decode($value, true); // This will return an array
    }

    public function parentLibrary()
    {
        return $this->belongsTo(Library::class, 'library_id');
    }
    

    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'library_user_permissions', 'library_user_id', 'permission_id');
    }

    public function devices()
    {
        return $this->morphMany(\App\Models\DeviceToken::class, 'user');
    }

}
