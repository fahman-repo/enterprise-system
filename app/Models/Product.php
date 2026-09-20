<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use Auditable, HasFactory, SoftDeletes;

    protected $fillable = [
        'category_id',
        'brand_id',
        'unit_id',
        'sku',
        'barcode',
        'name',
        'description',
        'cost_price',
        'selling_price',
        'tax_rate',
        'track_stock',
        'stock_quantity',
        'reorder_level',
        'image_path',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'category_id' => 'integer',
            'brand_id' => 'integer',
            'unit_id' => 'integer',
            'cost_price' => 'decimal:2',
            'selling_price' => 'decimal:2',
            'tax_rate' => 'decimal:2',
            'track_stock' => 'boolean',
            'stock_quantity' => 'decimal:3',
            'reorder_level' => 'decimal:3',
            'is_active' => 'boolean',
        ];
    }

    public function auditLogName(): string
    {
        return 'product';
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    /**
     * Stock quantity without trailing zeros, e.g. "12" or "0.5".
     */
    public function formattedStockQuantity(): string
    {
        return rtrim(rtrim(number_format((float) $this->stock_quantity, 3, '.', ''), '0'), '.');
    }

    /**
     * Whether tracked stock has fallen to or below the reorder level.
     */
    public function isLowStock(): bool
    {
        return $this->track_stock
            && $this->reorder_level !== null
            && (float) $this->stock_quantity > 0
            && (float) $this->stock_quantity <= (float) $this->reorder_level;
    }

    /**
     * Whether the product is tracked but has no stock on hand.
     */
    public function isOutOfStock(): bool
    {
        return $this->track_stock && (float) $this->stock_quantity <= 0;
    }
}
