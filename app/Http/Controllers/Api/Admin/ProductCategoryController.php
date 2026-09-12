<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Admin\ProductCategory\ImportProductCategoriesRequest;
use App\Http\Requests\Api\Admin\ProductCategory\StoreProductCategoryRequest;
use App\Http\Requests\Api\Admin\ProductCategory\UpdateProductCategoryRequest;
use App\Http\Resources\ProductCategoryResource;
use App\Models\ProductCategory;
use App\Support\JsonApi;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Rap2hpoutre\FastExcel\FastExcel;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProductCategoryController extends Controller
{
    /**
     * Maps camelCase request fields to the model's snake_case attributes.
     */
    private const ATTRIBUTE_MAP = [
        'metaTitle' => 'meta_title',
        'metaDescription' => 'meta_description',
        'metaKeywords' => 'meta_keywords',
        'ogImage' => 'og_image',
    ];

    /**
     * Translatable fields, exported/imported as JSON-encoded strings.
     */
    private const TRANSLATABLE_FIELDS = ['description', 'metaTitle', 'metaDescription'];

    public function index(): JsonResponse
    {
        $productCategories = ProductCategory::query()->paginate();

        return ProductCategoryResource::collection($productCategories)->response();
    }

    public function store(StoreProductCategoryRequest $request): JsonResponse
    {
        $productCategory = ProductCategory::create($this->mapAttributes($request->validated()));

        return (new ProductCategoryResource($productCategory))
            ->response()
            ->setStatusCode(201);
    }

    public function show(ProductCategory $product_category): JsonResponse
    {
        return (new ProductCategoryResource($product_category))->response();
    }

    public function update(UpdateProductCategoryRequest $request, ProductCategory $product_category): JsonResponse
    {
        $product_category->update($this->mapAttributes($request->validated()));

        return (new ProductCategoryResource($product_category))->response();
    }

    public function destroy(ProductCategory $product_category): JsonResponse
    {
        $product_category->delete();

        return JsonApi::meta(['message' => 'Product category deleted successfully.']);
    }

    /**
     * Export all product categories as an Excel spreadsheet.
     */
    public function export(): StreamedResponse
    {
        $rows = ProductCategory::query()->orderBy('id')->get()->map(
            fn (ProductCategory $productCategory) => $this->toExportRow($productCategory)
        );

        $response = (new FastExcel($rows))->download('product-categories.xlsx');
        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        return $response;
    }

    /**
     * Bulk create/update product categories from an uploaded spreadsheet.
     *
     * Rows are matched to existing product categories by `slug`: a matching
     * slug is updated, otherwise a new product category is created. Invalid
     * rows are skipped and reported back instead of failing the whole import.
     */
    public function import(ImportProductCategoriesRequest $request): JsonResponse
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
            $productCategory = ProductCategory::updateOrCreate(['slug' => $attributes['slug']], $attributes);

            $productCategory->wasRecentlyCreated ? $created++ : $updated++;
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
    private function toExportRow(ProductCategory $productCategory): array
    {
        return [
            'slug' => $productCategory->slug,
            'title' => $productCategory->title,
            'description' => json_encode($productCategory->getTranslations('description'), JSON_UNESCAPED_UNICODE),
            'image' => $productCategory->image,
            'metaTitle' => json_encode($productCategory->getTranslations('meta_title'), JSON_UNESCAPED_UNICODE),
            'metaDescription' => json_encode($productCategory->getTranslations('meta_description'), JSON_UNESCAPED_UNICODE),
            'metaKeywords' => $productCategory->meta_keywords,
            'ogImage' => $productCategory->og_image,
        ];
    }

    /**
     * Decodes the JSON-encoded translatable columns and casts loosely-typed
     * spreadsheet values (empty strings) before validation.
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

        return $row;
    }

    /**
     * Validation rules for an import row. Unlike
     * {@see StoreProductCategoryRequest}, the slug is not required to be
     * unique: a matching slug is treated as an update rather than a
     * duplicate.
     *
     * @return array<string, mixed>
     */
    private function importRules(): array
    {
        return [
            'slug' => ['required', 'string', 'max:255'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'array'],
            'description.*' => ['nullable', 'string'],
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
