<?php

namespace App\Http\Controllers;

use App\Models\Products;
use App\Models\Categories;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'page' => 'integer',
            'size' => 'integer',
            'sort_by' => 'in:name',
            'sort_dir' => 'in:asc,desc'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status_code' => 400,
                'message' => 'Invalid input',
                'errors' => $validator->errors()
            ]);
        }

        $page = $request->input('page', 1);
        $pageSize = $request->input('pageSize', 5);
        $sortBy = $request->input('sort_by', 'name');
        $sortDir = $request->input('sort_dir', 'asc');

        $productsQuery = Products::query();
        $productsQuery = $productsQuery->orderBy($sortBy, $sortDir);

        $products = $productsQuery->paginate($pageSize, ['*'], 'page', $page);

        $result = [
            'status_code' => 200,
            'message' => 'OK',
            'data' => $products->items(),
            'meta' => [
                'pagination' => [
                    'total' => $products->total(),
                    'count' => $products->count(),
                    'per_page' => $products->perPage(),
                    'current_page' => $products->currentPage(),
                    'total_pages' => $products->lastPage(),
                    'links' => [
                        'next' => $products->nextPageUrl(),
                    ],
                ],
            ],
        ];

        return response()->json($result, 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'sku' => 'required|string',
            'category_id' => 'required|exists:categories,id',
            'description' => 'required|string',
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status_code' => 400,
                'message' => 'Validation error occurred',
                'errors' => $validator->errors(),
            ]);
        }

        // Create the product
        $product = new Products();
        $product->name = $request->input('name');
        $product->sku = $request->input('sku');
        $product->category_id = $request->input('category_id');
        $product->description = $request->input('description');
        $product->added_by = auth()->id();
        $product->save();

        // Handle image upload
        $image = $request->file('image');
        $originalName = $image->getClientOriginalName();
        $imageName = str_replace(' ', '-', $originalName); // Replace spaces with "-"
        $imagePath = $image->storeAs('images', $imageName, 'public');

        // Update the product with the image path
        $product->image = $imagePath;
        $product->save();



        // Retrieve category details for the response
        $category = Categories::find($product->category_id);

        $productDetails = [
            'product_id' => $product->id,
            'product_name' => $product->name,
            'product_sku' => $product->sku,
            'product_category_id' => $product->category_id,
            'product_category' => $category->name, // Assuming there's a 'name' attribute in the Category model
            'product_description' => $product->description,
            'image_path' => $product->image,
        ];

        return response()->json(['status_code' => 200, 'message' => 'OK', 'data' => [$productDetails]], 201);
    }


    /**
     * Display the specified resource.
     */
    public function show(Products $products)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Products $product)
    {
        // dd($request);
        $validator = Validator::make($request->all(), [
            'name' => 'string',
            'sku' => 'string|unique:products,sku,' . $product->id,
            'category_id' => 'exists:categories,id',
            'description' => 'string',
            'image' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status_code' => 400,
                'message' => 'Validation error occurred',
                'errors' => $validator->errors(),
            ]);
        }

        // Replace spaces with underscores in the SKU if provided
        if ($request->has('sku')) {
            $sku = str_replace(' ', '_', $request->input('sku'));
            $product->sku = $sku;
        }

        // Handle image update and deletion of the old image
        if ($request->hasFile('image')) {
            // Delete the old image
            if ($product->image) {
                Storage::disk('public')->delete($product->image);
            }

            // Upload and save the new image
            $image = $request->file('image');
            $originalName = $image->getClientOriginalName();
            $imageName = str_replace(' ', '-', $originalName); // Replace spaces with "-"
            $imagePath = $image->storeAs('images', $imageName, 'public');
            $product->image = $imagePath;
        }

        // Update other product details
        $product->name = $request->input('name', $product->name);
        $product->category_id = $request->input('category_id', $product->category_id);
        $product->description = $request->input('description', $product->description);
        $product->updated_by = auth()->id();

        // Save changes
        $product->save();

        // Retrieve category details for the response
        $category = Categories::find($product->category_id);

        $productDetails = [
            'product_id' => $product->id,
            'product_name' => $product->name,
            'product_sku' => $product->sku,
            'product_category_id' => $product->category_id,
            'product_category' => $category->name, // Assuming there's a 'name' attribute in the Category model
            'product_description' => $product->description,
            'image_path' => $product->image,
        ];

        return response()->json(['status_code' => 200, 'message' => 'OK', 'data' => $productDetails], 200);
    }









    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Products $product)
    {
        // Delete the product image file
        if ($product->image) {
            Storage::disk('public')->delete($product->image);
        }

        // Soft delete the product
        $product->delete();

        return response()->json(['status_code' => 200, 'message' => 'Product soft deleted successfully'], 200);
    }

    public function restore($productId)
    {
        // Find the soft-deleted product by its ID
        $product = Products::withTrashed()->find($productId);

        // Check if the product is soft-deleted
        if (!$product) {
            return response()->json(['status_code' => 404, 'message' => 'Product not found'], 404);
        }

        // Restore the soft-deleted product
        $product->restore();

        return response()->json(['status_code' => 200, 'message' => 'Product restored successfully'], 200);
    }
}
