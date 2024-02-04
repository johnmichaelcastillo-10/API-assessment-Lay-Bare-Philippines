<?php

namespace App\Http\Controllers;

use App\Models\Products;
use Illuminate\Http\Request;
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
            'sku' => 'required|unique:products,sku',
            'category_id' => 'required|int',
            'description' => 'required|string',

        ]);
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
    public function update(Request $request, Products $products)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Products $products)
    {
        //
    }
}
