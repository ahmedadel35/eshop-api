<?php

namespace App\Http\Controllers;

use App\Product;
use App\Rate;
use Illuminate\Http\Request;

class RateController extends Controller
{
    public const PER_PAGE = 7;
    public const VALIDATE_ROLES = [
        'rate' => 'required|numeric|min:1|max:5',
        'message' => 'sometimes|string|max:196'
    ];

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(
        string $slug,
        int $perPage = self::PER_PAGE
    ) {
        $p = Product::without('rates')->whereSlug($slug)
            ->get('id')[0];

        return response()->json(
            Rate::whereProductId($p->id)->paginate($perPage)
        );
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, string $slug)
    {
        $r = (object) $this->validate($request, self::VALIDATE_ROLES);

        $p = Product::without('rates')
            ->whereSlug($slug)
            ->get(['id', 'user_id'])[0];
        $userId = auth()->guard('api')->id();

        $found = Rate::selectRaw('COUNT(id) as c_id')
            ->whereUserId($userId)
            ->whereProductId($p->id)
            ->get('c_id')[0];

        if ($userId === (int) $p->user_id || (int) $found->c_id > 0) {
            abort(403);
        }

        $rate = $p->rates()->create([
            'user_id' => $userId,
            'rate' => $r->rate,
            'message' => $r->message
        ]);

        return response()->json($rate, 201);
    }

    /**
     * Undocumented function
     *
     * @param Request $request
     * @param string $slug product slug
     * @param integer $id rate id
     * @return void
     */
    public function update(
        Request $request,
        string $slug,
        int $id
    ) {
        $r = (object) $this->validate($request, self::VALIDATE_ROLES);

        $rate = Rate::findOrFail($id);

        $rate->rate = $r->rate;
        $rate->message = $r->message;

        $rate->update();

        return response()->json([], 204);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Rate  $rate
     * @return \Illuminate\Http\Response
     */
    public function destroy(Rate $rate)
    {
        //
    }
}
