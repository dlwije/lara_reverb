<?php

namespace Modules\Product\Http\Requests;

use App\Rules\ExtraAttributes;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Storage;

class ProductRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'type'             => 'required|in:Standard,Service,Digital,Combo,Recipe',
            'name'             => 'required',
            'secondary_name'   => 'nullable',
            'code'             => 'required|string|max:20',
            'symbology'        => 'required|in:CODE128,CODE39,EAN8,EAN13,UPC',
            'category_id'      => 'required|exists:categories,id',
            'subcategory_id'   => 'nullable',
            'brand_id'         => 'nullable',
            'unit_id'          => 'nullable',
            'sale_unit_id'     => 'nullable',
            'purchase_unit_id' => 'nullable',
            'unit_prices'      => 'nullable|array',
            'cost'             => 'required|numeric',
            'price'            => 'required|numeric',
            'min_price'        => 'nullable|numeric',
            'max_price'        => 'nullable|numeric',
            'max_discount'     => 'nullable|numeric',
            'hsn_number'       => 'nullable',
            'sac_number'       => 'nullable',
            'weight'           => 'nullable|numeric',
            'dimensions'       => 'nullable',
            'rack_location'    => 'nullable',
            'supplier_id'      => 'nullable|exists:suppliers,id',
            'supplier_part_id' => 'nullable',
            'details'          => 'nullable|string',
            'file'             => 'nullable|required_if:type,Digital|mimes:zip',
            'featured'         => 'nullable|boolean',
            'hide_in_pos'      => 'nullable|boolean',
            'hide_in_shop'     => 'nullable|boolean',
            'tax_included'     => 'nullable|boolean',
            'can_edit_price'   => 'nullable|boolean',
            'has_expiry_date'  => 'nullable|boolean',
            'dont_track_stock' => 'nullable|boolean',
            'photo'            => 'nullable|mimes:jpg,jpeg,png,svg,webp',
            'photos'           => 'nullable|array',
            'photos.*'         => 'nullable|mimes:jpg,jpeg,png,svg,webp',
            'video_url'        => 'nullable|url',
            'alert_quantity'   => 'nullable|numeric',

            'has_variants'            => 'nullable|boolean',
            'variants'                => 'nullable|required_if:has_variants,true|array',
            'variations.*'            => 'nullable|required_if:has_variants,true|array',
            'variations.*.sku'        => 'nullable',
            'variations.*.code'       => 'required|alpha_dash|required_if:has_variants,true',
            'variations.*.meta'       => 'nullable|array|required_if:has_variants,true',
            'variations.*.price'      => 'nullable',
            'variations.*.cost'       => 'nullable',
            'variations.*.weight'     => 'nullable',
            'variations.*.dimensions' => 'nullable',
            'has_serials'             => 'nullable|boolean',
            'serials.*'               => 'nullable|array',

            'taxes'   => 'nullable|array',
            'taxes.*' => 'nullable|exists:taxes,id',

            'products'            => 'nullable|required_if:type,Combo|array',
            'products.*.id'       => 'nullable|required_if:type,Combo',
            'products.*.quantity' => 'nullable|required_if:type,Combo',

            'stores.*.store_id'       => 'required',
            'stores.*.quantity'       => 'nullable|numeric',
            'stores.*.price'          => 'nullable|numeric',
            'stores.*.taxes'          => 'nullable|array',
            'stores.*.alert_quantity' => 'nullable|numeric',

            'slug'        => 'nullable|string|max:50',
            'title'       => 'nullable|string|max:60',
            'description' => 'nullable|string|max:160',
            'keywords'    => 'nullable|string|max:190',
            'noindex'     => 'nullable|boolean',
            'nofollow'    => 'nullable|boolean',

            'extra_attributes' => ['nullable', new ExtraAttributes('product')],
        ];
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
//        return $this->user()->can($this->route('product') ? 'update-products' : 'create-products');
        return true;
    }

    public function validated($key = null, $default = null)
    {
        $data = parent::validated($key, $default);

        if ($this->has('photo') && $this->photo) {
            $data['photo'] = Storage::disk('asset')->url($this->photo->store('/images/products', 'asset'));
            if ($this->route('product')?->id && $this->route('product')?->photo) {
                Storage::disk('asset')->delete($this->route('product')->photo);
            }
        } else {
            unset($data['photo']);
        }

        return $data;
    }
}
