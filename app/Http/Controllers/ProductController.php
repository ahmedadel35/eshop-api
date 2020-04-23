<?php

namespace App\Http\Controllers;

use App\Category;
use App\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class ProductController extends Controller
{
    public const PER_PAGE = 50;
    private const VALIDATE = [
        'category' => 'required|numeric|exists:categories,id',
        'name' => 'required|string',
        'brand' => 'required|string',
        'info' => 'required|string|min:10',
        'price' => 'required|numeric|min:1',
        'amount' => 'required|numeric|min:1',
        'save' => 'required|numeric|min:0|max:100',
        'color' => 'required|string',
        'is_used' => 'sometimes'
    ];

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

    public function indexByBrands(
        string $slug,
        string $brands,
        int $perPage = self::PER_PAGE
    ) {
        $brands = explode(',', $brands);

        return response()->json(
            Product::with(['pCat'])
                ->whereCategorySlug($slug)
                ->whereIn('brand', $brands)
                ->paginate($perPage)
        );
    }

    public function indexByCondition(
        string $slug,
        int $cond,
        int $perPage = self::PER_PAGE
    ) {
        return response()->json(
            Product::with(['pCat'])
                ->whereCategorySlug($slug)
                ->whereIsUsed(!!$cond)
                ->paginate($perPage)
        );
    }

    public function indexByPrice(
        string $slug,
        string $prices,
        int $perPage = self::PER_PAGE
    ) {
        [$from, $to] = explode(',', $prices);

        return response()->json(
            Product::with('pCat')
                ->whereCategorySlug($slug)
                ->whereBetween(
                    DB::raw('price-(save/100*price)'),
                    [$from, $to]
                )->paginate($perPage)
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
        $req = (object) $this->validate($request, self::VALIDATE);

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
    public function update(Request $request, string $slug)
    {
        $req = (object) $this->validate($request, Arr::except(self::VALIDATE, 'category'));

        $product = Product::without('rates')
            ->whereSlug($slug)
            ->get()[0];

        if (Gate::denies('update', $product)) {
            abort(403);
        }

        $product->name = $req->name;
        $product->brand = $req->brand;
        $product->info = $req->info;
        $product->price = $req->price;
        $product->amount = $req->amount;
        $product->save = $req->save;
        $product->color = explode(',', $req->color);
        $product->is_used = isset($req->is_used) ? false : true;

        $product->update();

        return response()->json([], 204);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Product  $product
     * @return \Illuminate\Http\Response
     */
    public function destroy(string $slug)
    {
        $product = Product::without('rates')
            ->whereSlug($slug)
            ->get()[0];

        if (Gate::denies('delete', $product)) {
            abort(403);
        }

        $product->delete();

        return response()->json([], 204);
    }
}
