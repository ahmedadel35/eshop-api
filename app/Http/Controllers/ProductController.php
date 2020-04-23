<?php

namespace App\Http\Controllers;

use App\Category;
use App\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public const PER_PAGE = 50;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(int $perPage = self::PER_PAGE)
    {
        return response()->json(
            Product::without('rates')->paginate($perPage, ['id'])
        );
    }

    /**
     * Display a listing of product ids
     *
     * @return \Illuminate\Http\Response
     */
    public function indexIds(int $perPage = self::PER_PAGE)
    {
        return response()->json(
            Product::without('rates')->paginate($perPage, ['id'])
        );
    }

    /**
     * Display a listing of the resource by parent category slug.
     *
     * @return \Illuminate\Http\Response
     */
    public function indexSubCat(string $slug, int $perPage = 50)
    {
        return response()->json(
            Product::without('rates')->whereCategorySlug($slug)->paginate($perPage)
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
        $req = (object) $this->validate($request, [
            'category' => 'required|numeric|exists:categories,id',
            'name' => 'required|string',
            'brand' => 'required|string',
            'info' => 'required|string|min:10',
            'price' => 'required|numeric|min:1',
            'amount' => 'required|numeric|min:1',
            'save' => 'required|numeric|min:0|max:100',
            'color' => 'required|string',
            'is_used' => 'sometimes'
        ]);

        $sc = Category::find($req->category);

        $req->user_id = auth()->guard('api')->id();
        $req->category_slug = $sc->slug;
        $req->is_used = isset($req->is_used) ? false : true;
        $req->color = explode(',', $req->color);
        $req->img = [
            mt_rand(1, 15) . '.jpg',
            mt_rand(1, 15) . '.jpg',
            mt_rand(1, 15) . '.jpg'
        ];

        unset($req->category);

        $p = $sc->products()->create((array) $req);

        return response()->json(
            $p->withoutRelations('rates')
                ->makeHidden('rateAvg')
                ->toArray(),
            201
        );
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Product  $product
     * @return \Illuminate\Http\Response
     */
    public function show(string $slug)
    {
        return response()->json(
            Product::where('slug', $slug)->get()
        );
    }

    /**
     * Display list of the provider products ids
     *
     * @return \Illuminate\Http\Response
     */
    public function showCollection(Request $request, string $ids)
    {
        $ids = explode(',', $ids);

        if (sizeof($ids) > 500) {
            return response()->json([], 413);
        }

        if (!$request->has('rates')) {
            return response()->json(
                Product::without('rates')->findMany($ids)->makeVisible('rateAvg')
            );
        }

        return response()->json(
            Product::findMany($ids)->makeVisible('rateAvg')
        );
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Product  $product
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Product $product)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Product  $product
     * @return \Illuminate\Http\Response
     */
    public function destroy(Product $product)
    {
        //
    }
}
