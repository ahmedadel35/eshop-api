<?php

namespace App\Http\Controllers;

use App\Order;
use App\Product;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    public const PER_PAGE = 50;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(int $perPage = self::PER_PAGE)
    {
        if (!auth()->guard('api')->user()->isAdmin()) {
            abort(403);
        }

        return response()->json(
            User::paginate($perPage)
        );
    }

    public function indexIds(int $perPage = self::PER_PAGE)
    {
        if (!auth()->guard('api')->user()->isAdmin()) {
            abort(403);
        }

        return response()->json(
            User::paginate($perPage, 'id')
        );
    }

    /**
     * Display the user profile
     * 
     * @param integer $userId
     * @return \Illuminate\Http\Response
     */
    public function show(int $userId = null)
    {
        $user = auth()->guard('api')->user();
        $adminArr = [];

        if ($user->isAdmin()) {
            if ($userId && is_int($userId)) {
                $user = User::find($userId);
                $state = $this->loadUserStats($user);
            } else {
                $state = $this->loadAdminStats($user);
                $adminArr = [
                    'users_count' => $state[4],
                    'reviews_count' => $state[5]
                ];
            }
        } else {
            $state = $this->loadUserStats($user);
        }

        return response()->json([
            'orders_count' => $state[0],
            'delivered_orders' => $state[1],
            'proudcts_count' => $state[2],
            'total_user_paymenst' => $state[3]
        ] + $adminArr);
    }

    /**
     * Display the user orders
     * 
     * @param integer $userId
     * @return \Illuminate\Http\Response
     */
    public function showOrders(Request $request, ?int $userId = null)
    {
        $user = auth()->guard('api')->user();
        $perPage = $request->get('perPage', self::PER_PAGE);

        if (($user->isAdmin() || $user->isSuper()) && $userId) {
            $user = User::findOrFail($userId);
        }

        $orders = Order::where('user_id', $user->id)
            ->with('product')
            ->latest()
            ->paginate($perPage);

        return response()->json($orders->toArray());
    }

    /**
     * Display the user products
     * 
     * @param integer $userId
     * @return \Illuminate\Http\Response
     */
    public function showProducts(
        Request $request,
        ?int $userId = null
    ) {
        $user = auth()->guard('api')->user();
        $perPage = $request->get('perPage', self::PER_PAGE);

        if ($userId) {
            $user = User::findOrFail($userId);
        }

        return response()->json(
            Product::without('rates')
                ->whereUserId($user->id)
                ->paginate($perPage)
        );
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\User  $user
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, int $userId)
    {
        // abort if not admin
        if (!auth()->guard('api')->user()->isAdmin()) {
            abort(403);
        }

        $req = (object) $this->validate($request, [
            'role' => 'sometimes|numeric|min:0|max:1'
        ]);

        $user = User::findOrFail($userId);

        $user->role = (bool) $req->role;
        $user->update();

        return response()->json([], 204);
    }

    private function loadUserStats($user): array
    {
        $countOrders = DB::table('orders')
            ->selectRaw('COUNT(id) as oc')
            ->where('user_id', $user->id)
            ->get();

        $sentOrders = DB::table('orders')
            ->selectRaw('COUNT(id) as sc')
            ->where('user_id', $user->id)
            ->where('sent', true)
            ->get();

        $products = DB::table('products')
            ->selectRaw('COUNT(id) as pc')
            ->where('user_id', $user->id)
            ->get();

        $totalPaid = DB::table('orders')
            ->selectRaw('SUM(total) as paid')
            ->where('user_id', $user->id)
            ->get();

        return [
            $countOrders[0]->oc,
            $sentOrders[0]->sc,
            $products[0]->pc,
            $totalPaid[0]->paid,
            0,
            0
        ];
    }

    private function loadAdminStats($user)
    {
        $productsCount = DB::table('products')
            ->selectRaw('COUNT(id) as pc')
            ->get();

        $countOrders = DB::table('orders')
            ->selectRaw('COUNT(id) as oc')
            ->get();

        $sentOrders = DB::table('orders')
            ->selectRaw('COUNT(id) as sc')
            ->where('sent', true)
            ->get();

        $totalPaid = DB::table('orders')
            ->selectRaw('SUM(total) as paid')
            ->get();

        $usersCount = DB::table('users')
            ->selectRaw('COUNT(id) as uc')
            ->get();

        $revCount = DB::table('rates')
            ->selectRaw('COUNT(id) as rc')
            ->get();

        return [
            $countOrders[0]->oc,
            $sentOrders[0]->sc,
            $productsCount[0]->pc,
            $totalPaid[0]->paid,
            $usersCount[0]->uc,
            $revCount[0]->rc
        ];
    }
}
