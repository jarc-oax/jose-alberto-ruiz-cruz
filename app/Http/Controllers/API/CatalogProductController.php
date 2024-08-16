<?php
   
namespace App\Http\Controllers\API;
   
use Illuminate\Http\Request;
use App\Http\Controllers\API\BaseController as BaseController;
use App\Models\CatalogProduct as Product;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\CatalogProductResource as ProductResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
   
class CatalogProductController extends BaseController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(): JsonResponse
    {
        $products = Product::all();
    
        return $this->sendResponse(ProductResource::collection($products), 'Products retrieved successfully.');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
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
   
        if($validator->fails()){
            return $this->sendError('Validation Error.', $validator->errors(), 400);       
        }
   
        $product = Product::create($input);
   
        return $this->sendResponse(new ProductResource($product), 'Product created successfully.', 201);
    }

    /**
     * Store a batch of products.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
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
   
        if($validator->fails()){
            return $this->sendError('Validation Error.', $validator->errors(), 400);       
        }
   
        $products = [];
        foreach ($input as $product) {
            $products[] = Product::create($product);
        }
   
        return $this->sendResponse(ProductResource::collection($products), 'Products created successfully.', 201);
    }   
   
    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
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
     * Display a batch of catalog products
     *
     */
    public function showBatch(Request $request)
    {
        $validatedData = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:catalog_products,id',
        ]);

        $registros = Product::whereIn('id', $validatedData['ids'])->get();

        return response()->json($registros, 200);
    }
    
    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
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
   
        if($validator->fails()){
            return $this->sendError('Validation Error.', $validator->errors());
        }

        $product = Product::find($id);

        if ($product == null) {
            return $this->sendError('Validation Error.', ["The product doesn't exist."], 400); 
        }
   
        $product->name = $input['name'] ?? $product->name;
        $product->description = $input['description'] ?? $product->description;
        $product->height = $input['height'] ?? $product->height;
        $product->length = $input['length'] ?? $product->length;
        $product->width = $input['width'] ?? $product->width;
        $product->save();
   
        return $this->sendResponse(new ProductResource($product), 'Product updated successfully.');
    }

    public function updateBatch(Request $request)
    {
        // Validar los datos recibidos
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

            return response()->json(['message' => 'Registros actualizados con éxito'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Error al actualizar los registros', 'details' => $e->getMessage()], 500);
        }
    }
   
    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Product $product): JsonResponse
    {
        $product->delete();
   
        return $this->sendResponse([], 'Product deleted successfully.');
    }

    public function deleteBatch(Request $request)
    {
        $validatedData = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:catalog_products,id',
        ]);

        try {

            DB::beginTransaction();
            DB::table('catalog_products')->whereIn('id', $validatedData['ids'])->delete();
            DB::commit();

            return response()->json(['message' => 'Registros eliminados con éxito'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Error al eliminar los registros', 'details' => $e->getMessage()], 500);
        }
    }
}