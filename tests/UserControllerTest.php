<?php

use App\Order;
use App\Product;
use App\User;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Laravel\Lumen\Testing\DatabaseTransactions;

class UserControllerTest extends TestCase
{
    use DatabaseTransactions;
    public const BASE_URL = '/user/';

    public function testOnlyAdminCanLoadUsersList()
    {
        $user = $this->passportSignIn(1);
        $this->assertTrue($user->isAdmin());

        $this->get('user/list')
            ->seeStatusCode(200)
            ->seeJsonContains(['name' => $user->name]);
    }

    public function testUnAuthrizedUserCanNotLoadUsersList()
    {
        $user = $this->passportSignIn(3);
        $this->assertFalse($user->isAdmin());

        $this->get('user/list')
            ->seeStatusCode(403);
    }

    public function testOnlyAdminCanLoadUsersIds()
    {
        $user = $this->passportSignIn(1);
        $this->assertTrue($user->isAdmin());

        $this->get('user/list')
            ->seeStatusCode(200);
    }

    public function testUserCanLoadProfile()
    {
        $user = $this->passportSignIn(25);
        $this->assertFalse($user->isAdmin());

        $this->get(self::BASE_URL . 'profile')
            ->seeStatusCode(200)
            ->seeJsonContains([
                'proudcts_count' => $user->products->count(),
                'orders_count' => 0
            ])->seeJsonDoesntContains(['reviews_count']);
    }

    public function testAdminProfileContainsWebsiteStats()
    {
        $user = $this->passportSignIn(1);
        $this->assertTrue($user->isAdmin());

        $products_count = Product::selectRaw('COUNT(id) as pc')
            ->get()[0]->pc;
        $orders_count = Order::selectRaw('COUNT(id) as oc')
            ->get()[0]->oc;

        $this->get(self::BASE_URL . 'profile')
            ->seeStatusCode(200)
            ->seeJsonContains([
                'proudcts_count' => $products_count,
                'orders_count' => $orders_count,
            ]);
    }

    public function testAdminCanLoadAnyOneProfile()
    {
        $admin = $this->passportSignIn(1);
        $this->assertTrue($admin->isAdmin());
        $user = User::find(random_int(25, 200));

        $products_count = Product::selectRaw('COUNT(id) as pc')
            ->whereUserId($user->id)
            ->get()[0]->pc;

        $this->get(self::BASE_URL . 'profile/' . $user->id)
            ->seeStatusCode(200)
            ->seeJsonContains([
                'proudcts_count' => $products_count,
            ]);
    }

    public function testUserCanLoadSubmittedOrders()
    {
        $this->withoutExceptionHandling();
        $user = $this->passportSignIn(3);

        $order = factory(Order::class)->create([
            'user_id' => $user->id
        ]);

        $this->get(self::BASE_URL . 'orders')
            ->seeStatusCode(200)
            ->seeJsonContains(['address' => $order->address]);
    }
}
