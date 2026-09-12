<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Admin\Product\ImportProductsRequest;
use App\Http\Requests\Api\Admin\Product\StoreProductRequest;
use App\Http\Requests\Api\Admin\Product\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Support\JsonApi;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Rap2hpoutre\FastExcel\FastExcel;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProductController extends Controller
{
    /**
     * Maps camelCase request fields to the model's snake_case attributes.
     */
    private const ATTRIBUTE_MAP = [
        'isActive' => 'is_active',
        'categoryId' => 'category_id',
        'stockQuantity' => 'stock_quantity',
        'metaTitle' => 'meta_title',
        'metaDescription' => 'meta_description',
        'metaKeywords' => 'meta_keywords',
        'ogImage' => 'og_image',
    ];

    /**
     * Translatable fields, exported/imported as JSON-encoded strings.
     */
    private const TRANSLATABLE_FIELDS = ['title', 'description', 'metaTitle', 'metaDescription'];

    public function index(): JsonResponse
    {
        $products = Product::query()->paginate();

        return ProductResource::collection($products)->response();
    }

    public function store(StoreProductRequest $request): JsonResponse
    {
        $product = Product::create($this->mapAttributes($request->validated()))->fresh();

        return (new ProductResource($product))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Product $product): JsonResponse
    {
        return (new ProductResource($product))->response();
    }

    public function update(UpdateProductRequest $request, Product $product): JsonResponse
    {
        $product->update($this->mapAttributes($request->validated()));

        return (new ProductResource($product))->response();
    }

    public function destroy(Product $product): JsonResponse
    {
        $product->delete();

        return JsonApi::meta(['message' => 'Product deleted successfully.']);
    }

    /**
     * Export all products as an Excel spreadsheet.
     */
    public function export(): StreamedResponse
    {
        $rows = Product::query()->orderBy('id')->get()->map(
            fn (Product $product) => $this->toExportRow($product)
        );

        $response = (new FastExcel($rows))->download('products.xlsx');
        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        return $response;
    }

    /**
     * Bulk create/update products from an uploaded spreadsheet.
     *
     * Rows are matched to existing products by `slug`: a matching slug is
     * updated, otherwise a new product is created. Invalid rows are skipped
     * and reported back instead of failing the whole import.
     */
    public function import(ImportProductsRequest $request): JsonResponse
    {
        $rows = (new FastExcel)->import($request->file('file'));

        $created = 0;
        $updated = 0;
        $errors = [];

        foreach ($rows as $index => $row) {
            $validator = Validator::make($this->normalizeImportRow($row), $this->importRules());

            if ($validator->fails()) {
                $errors[] = ['row' => $index + 2, 'errors' => $validator->errors()->all()];

                continue;
            }

            $attributes = $this->mapAttributes($validator->validated());
            $product = Product::updateOrCreate(['slug' => $attributes['slug']], $attributes);

            $product->wasRecentlyCreated ? $created++ : $updated++;
        }

        return JsonApi::meta([
            'message' => 'Import completed.',
            'created' => $created,
            'updated' => $updated,
            'failed' => count($errors),
            'errors' => $errors,
        ]);
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function mapAttributes(array $validated): array
    {
        $attributes = [];

        foreach ($validated as $key => $value) {
            $attributes[self::ATTRIBUTE_MAP[$key] ?? $key] = $value;
        }

        return $attributes;
    }

    /**
     * @return array<string, mixed>
     */
    private function toExportRow(Product $product): array
    {
        return [
            'sku' => $product->sku,
            'slug' => $product->slug,
            'title' => json_encode($product->getTranslations('title'), JSON_UNESCAPED_UNICODE),
            'description' => json_encode($product->getTranslations('description'), JSON_UNESCAPED_UNICODE),
            'isActive' => $product->is_active ? 1 : 0,
            'categoryId' => $product->category_id,
            'price' => $product->price,
            'stockQuantity' => $product->stock_quantity,
            'image' => $product->image,
            'metaTitle' => json_encode($product->getTranslations('meta_title'), JSON_UNESCAPED_UNICODE),
            'metaDescription' => json_encode($product->getTranslations('meta_description'), JSON_UNESCAPED_UNICODE),
            'metaKeywords' => $product->meta_keywords,
            'ogImage' => $product->og_image,
        ];
    }

    /**
     * Decodes the JSON-encoded translatable columns and casts loosely-typed
     * spreadsheet values (booleans, empty strings) before validation.
     *
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function normalizeImportRow(array $row): array
    {
        foreach (self::TRANSLATABLE_FIELDS as $field) {
            if (! array_key_exists($field, $row) || $row[$field] === '' || $row[$field] === null) {
                unset($row[$field]);

                continue;
            }

            if (is_string($row[$field])) {
                $decoded = json_decode($row[$field], true);

                if (json_last_error() === JSON_ERROR_NONE) {
                    $row[$field] = $decoded;
                }
            }
        }

        if (array_key_exists('isActive', $row) && $row['isActive'] !== '' && $row['isActive'] !== null) {
            $row['isActive'] = filter_var($row['isActive'], FILTER_VALIDATE_BOOLEAN);
        }

        if (array_key_exists('categoryId', $row) && $row['categoryId'] === '') {
            $row['categoryId'] = null;
        }

        return $row;
    }

    /**
     * Validation rules for an import row. Unlike {@see StoreProductRequest},
     * the slug is not required to be unique: a matching slug is treated as
     * an update rather than a duplicate.
     *
     * @return array<string, mixed>
     */
    private function importRules(): array
    {
        return [
            'sku' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255'],
            'title' => ['required', 'array', 'min:1'],
            'title.*' => ['required', 'string'],
            'description' => ['nullable', 'array'],
            'description.*' => ['nullable', 'string'],
            'isActive' => ['nullable', 'boolean'],
            'categoryId' => ['nullable', 'integer', 'exists:product_categories,id'],
            'price' => ['required', 'numeric', 'min:0'],
            'stockQuantity' => ['required', 'integer', 'min:0'],
            'image' => ['nullable', 'string', 'max:255'],
            'metaTitle' => ['nullable', 'array'],
            'metaTitle.*' => ['nullable', 'string'],
            'metaDescription' => ['nullable', 'array'],
            'metaDescription.*' => ['nullable', 'string'],
            'metaKeywords' => ['nullable', 'string'],
            'ogImage' => ['nullable', 'string', 'max:255'],
        ];
    }
}
