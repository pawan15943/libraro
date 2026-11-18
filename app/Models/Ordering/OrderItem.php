<?php

namespace App\Models\Ordering;

use App\Traits\HasBranch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrderItem extends Model
{
    use HasBranch, SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = [
        'price' => 'decimal:2',
        'is_enabled' => 'boolean',
        'is_available' => 'boolean',
        'is_featured' => 'boolean',
        'needs_upload' => 'boolean',
        'modes' => 'array',
    ];

    public function category()
    {
        return $this->belongsTo(OrderCategory::class, 'order_category_id');
    }

    /** Can this item be ordered with the given mode (seat / table / counter)? */
    public function allowsMode(string $mode): bool
    {
        return empty($this->modes) || in_array($mode, $this->modes, true);
    }

    public function imageUrl(): ?string
    {
        // stored as "public/upload/ordering/…" by FileUploadService
        return $this->image ? asset($this->image) : null;
    }
}
