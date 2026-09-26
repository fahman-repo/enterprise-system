<?php

namespace App\Services\Approvals;

use App\Models\ApprovalRequest;
use App\Models\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ProductsApprovalModule extends CrudApprovalModule
{
    public function key(): string
    {
        return 'products';
    }

    public function label(): string
    {
        return __('Products');
    }

    public function targetLabel(): string
    {
        return __('Product');
    }

    protected function modelClass(): string
    {
        return Product::class;
    }

    protected function fields(): array
    {
        return [
            'sku',
            'barcode',
            'name',
            'description',
            'category_id',
            'brand_id',
            'unit_id',
            'cost_price',
            'selling_price',
            'tax_rate',
            'stock_quantity',
            'reorder_level',
            'track_stock',
            'is_active',
            'image_path',
        ];
    }

    protected function rules(?Model $target, string $action): array
    {
        return [
            'sku' => ['required', Rule::unique('products', 'sku')->ignore($target?->getKey())],
            'barcode' => ['nullable', Rule::unique('products', 'barcode')->ignore($target?->getKey())],
            'name' => ['required'],
            'category_id' => ['nullable', Rule::exists('categories', 'id')],
            'brand_id' => ['nullable', Rule::exists('brands', 'id')],
            'unit_id' => ['nullable', Rule::exists('units', 'id')],
        ];
    }

    /**
     * Persist the update and delete the image that was replaced or removed.
     *
     * @param  array<string, mixed>  $payload
     */
    protected function applyUpdate(ApprovalRequest $request, Model $target, array $payload): void
    {
        /** @var Product $target */
        $old = $target->image_path;

        $target->update($payload);

        if (array_key_exists('image_path', $payload) && $old && $old !== $payload['image_path']) {
            Storage::disk('public')->delete($old);
        }
    }

    /**
     * Delete the file stored when the request was submitted but never applied.
     */
    public function discard(ApprovalRequest $request): void
    {
        $new = $request->proposed_payload['image_path'] ?? null;
        $old = $request->before_payload['image_path'] ?? null;

        if ($new && $new !== $old) {
            Storage::disk('public')->delete($new);
        }
    }
}
