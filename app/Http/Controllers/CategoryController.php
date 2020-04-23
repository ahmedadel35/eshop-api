<?php

namespace App\Http\Controllers;

use App\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return response()->json(
            Category::whereNull('category_id')->get()
        );
    }

    public function getSubIds()
    {
        return response()->json(
            Category::whereNotNull('category_id')->get('id')
        );
    }

    public function getSubList()
    {
        return response()->json(
            Category::whereNotNull('category_id')->with('parent')->get()
        );
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $req = $this->validate($request, [
            'parent_id' => 'required|numeric|min:1|exists:categories,id',
            'name' => 'required|string|min:3|max:255'
        ]);
        
        $sub = Category::create([
            'category_id' => $req['parent_id'],
            'name' => $req['name']
        ]);

        return response()->json($sub, 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Category  $category
     * @return \Illuminate\Http\Response
     */
    public function show(string $slug)
    {
        return response()->json(
            Category::where('slug', $slug)->with('parent')->get()
        );
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Category  $category
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Category $category)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Category  $category
     * @return \Illuminate\Http\Response
     */
    public function destroy(Category $category)
    {
        //
    }
}
