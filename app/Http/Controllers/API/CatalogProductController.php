<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\API\BaseController as BaseController;
use App\Models\CatalogProduct as Product;
use App\Http\Resources\CatalogProductResource as ProductResource;

class CatalogProductController extends BaseController
{
    /**
     * Display a listing of all products.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(): JsonResponse
    {
        $products = Product::all();
    
        return $this->sendResponse(ProductResource::collection($products), 'Products retrieved successfully.');
    }

    /**
     * Store a newly created product in the database.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $input = $request->all();
    
        $validator = Validator::make($input, [
            'name' => 'required',
            'description' => 'required',
            'height' => 'required|numeric|min:0',
            'length' => 'required|numeric|min:0',
            'width' => 'required|numeric|min:0',
        ]);
    
        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors(), 400);
        }
    
        DB::beginTransaction();
    
        try {
            $product = Product::create($input);
            DB::commit();
            return $this->sendResponse(new ProductResource($product), 'Product created successfully.', 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->sendError('Error creating product.', ['details' => $e->getMessage()], 500);
        }
    }

    /**
     * Store a batch of products in the database.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function insertBatch(Request $request): JsonResponse
    {
        $input = $request->all();
    
        $validator = Validator::make($input, [
            '*.name' => 'required',
            '*.description' => 'required',
            '*.height' => 'required|numeric|min:0',
            '*.length' => 'required|numeric|min:0',
            '*.width' => 'required|numeric|min:0',
        ]);
    
        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors(), 400);
        }
    
        DB::beginTransaction();
    
        try {
            $products = [];
            foreach ($input as $productData) {
                $products[] = Product::create($productData);
            }
    
            DB::commit();
    
            return $this->sendResponse(ProductResource::collection($products), 'Products created successfully.', 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->sendError('Error creating products.', ['details' => $e->getMessage()], 500);
        }
    }
   
    /**
     * Display the specified product.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id): JsonResponse
    {
        $product = Product::find($id);
  
        if (is_null($product)) {
            return $this->sendError('Product not found.', ["The product with ID $id does not exist."], 404);
        }
   
        return $this->sendResponse(new ProductResource($product), 'Product retrieved successfully.');
    }

    /**
     * Display a batch of products by their IDs.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function showBatch(Request $request): JsonResponse
    {
        $validatedData = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:catalog_products,id',
        ]);

        $products = Product::whereIn('id', $validatedData['ids'])->get();

        return response()->json($products, 200);
    }
    
    /**
     * Update the specified product in the database.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id): JsonResponse
    {
        $input = $request->all();
        $validator = Validator::make($input, [
            'name' => 'string',
            'description' => 'string',
            'height' => 'numeric|min:0',
            'length' => 'numeric|min:0',
            'width' => 'numeric|min:0',
        ]);
    
        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors());
        }
    
        $product = Product::find($id);
    
        if ($product == null) {
            return $this->sendError('Validation Error.', ["The product doesn't exist."], 400);
        }
    
        DB::beginTransaction();
    
        try {
            $product->name = $input['name'] ?? $product->name;
            $product->description = $input['description'] ?? $product->description;
            $product->height = $input['height'] ?? $product->height;
            $product->length = $input['length'] ?? $product->length;
            $product->width = $input['width'] ?? $product->width;
            $product->save();
    
            DB::commit();
            return $this->sendResponse(new ProductResource($product), 'Product updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->sendError('Error updating product.', ['details' => $e->getMessage()], 500);
        }
    }

    /**
     * Update a batch of products in the database.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateBatch(Request $request): JsonResponse
    {
        // Validate the incoming request data
        $validatedData = $request->validate([
            '*.id' => 'required|integer|exists:catalog_products,id',
            '*.name' => 'string',
            '*.description' => 'string',
            '*.height' => 'numeric|min:0',
            '*.length' => 'numeric|min:0',
            '*.width' => 'numeric|min:0',
        ]);

        try {
            DB::beginTransaction();

            foreach ($validatedData as $data) {
                $dataToUpdate = [];
                if (isset($data['name'])) {
                    $dataToUpdate['name'] = $data['name'];
                }
                if (isset($data['description'])) {
                    $dataToUpdate['description'] = $data['description'];
                }
                if (isset($data['height'])) {
                    $dataToUpdate['height'] = $data['height'];
                }
                if (isset($data['length'])) {
                    $dataToUpdate['length'] = $data['length'];
                }
                if (isset($data['width'])) {
                    $dataToUpdate['width'] = $data['width'];
                }

                DB::table('catalog_products')
                    ->where('id', $data['id'])
                    ->update($dataToUpdate);
            }

            DB::commit();

            return response()->json(['message' => 'Records updated successfully'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Error updating records', 'details' => $e->getMessage()], 500);
        }
    }
   
    /**
     * Remove the specified product from storage.
     *
     * @param  \App\Models\CatalogProduct  $product
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Product $product): JsonResponse
    {
        DB::beginTransaction();
    
        try {
            $product->delete();
            DB::commit();
            return $this->sendResponse([], 'Product deleted successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->sendError('Error deleting product.', ['details' => $e->getMessage()], 500);
        }
    }

    /**
     * Remove a batch of products from storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteBatch(Request $request): JsonResponse
    {
        $validatedData = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:catalog_products,id',
        ]);

        try {
            DB::beginTransaction();
            DB::table('catalog_products')->whereIn('id', $validatedData['ids'])->delete();
            DB::commit();

            return response()->json(['message' => 'Records deleted successfully'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Error deleting records', 'details' => $e->getMessage()], 500);
        }
    }
}
