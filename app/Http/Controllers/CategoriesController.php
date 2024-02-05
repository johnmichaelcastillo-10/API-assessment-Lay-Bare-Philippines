<?php

namespace App\Http\Controllers;

use App\Models\Categories;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CategoriesController extends Controller
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
            'sort_dir' => 'in:asc,desc',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status_code' => 400,
                'message' => 'Invalid input',
                'errors' => $validator->errors(),
            ], 400);
        }

        $page = $request->input('page', 1);
        $pageSize = $request->input('size', 5);
        $sortBy = $request->input('sort_by', 'name');
        $sortDir = $request->input('sort_dir', 'asc');

        $categoriesQuery = Categories::query();

        $categoriesQuery = $categoriesQuery->orderBy($sortBy, $sortDir);

        $categories = $categoriesQuery->paginate($pageSize, ['*'], 'page', $page);

        $result = [
            'status_code' => 200,
            'message' => 'OK',
            'data' => $categories->items(),
            'meta' => [
                'pagination' => [
                    'total' => $categories->total(),
                    'count' => $categories->count(),
                    'per_page' => $categories->perPage(),
                    'current_page' => $categories->currentPage(),
                    'total_pages' => $categories->lastPage(),
                    'links' => [
                        'next' => $categories->nextPageUrl(),
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
            'name' => 'required|string|unique:categories,name',
            'description' => 'required|string',
            'product_manager_id' => 'required|int'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status_code' => 400,
                'message' => 'Validation error occurred',
                'errors' => $validator->errors()
            ]);
        }

        $category = new Categories();
        $category->name = $request->input('name');
        $category->description = $request->input('description');
        $category->product_manager_id = $request->input('product_manager_id');

        $category->save();

        return response()->json([
            'status_code' => 201,
            'message' => 'Category created successfully',
            'data' => $category
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Categories $categories)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Categories $category)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'string|unique:categories,name,' . $category->id,
            'description' => 'string',
            'product_manager_id' => 'int'
        ]);


        if ($validator->fails()) {
            return response()->json([
                'status_code' => 400,
                'message' => 'Validation error occurred',
                'errors' => $validator->errors()
            ]);
        }

        $category->update([
            'name' => $request->input('name', $category->name),
            'description' => $request->input('description', $category->description),
            'product_manager_id' => $request->input('product_manager_id', $category->product_manager_id),
        ]);

        return response()->json([
            'status_code' => 200,
            'message' => 'Category updated successfully',
            'data' => $category,
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Categories $category)
    {
        $category->delete();

        return response()->json([
            'status_code' => 200,
            'message' => 'Category soft-deleted successfully',
        ], 200);
    }
}
