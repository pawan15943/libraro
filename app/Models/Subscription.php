<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Models\Permission;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasPermissions;
use App\Models\Scopes\LibraryScope;
class Subscription extends Model
{
    use HasPermissions;
    use HasFactory;
    use HasFactory,SoftDeletes,Notifiable;
    protected $table = 'subscriptions';
    protected $guarded = []; 

    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'subscription_permission', 'subscription_id', 'permission_id');
    }

    public function libraries()
    {
        return $this->hasMany(Library::class, 'library_type');
    }
    
    
    
}
