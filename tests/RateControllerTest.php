<?php

use App\Product;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Laravel\Lumen\Testing\DatabaseTransactions;

class RateControllerTest extends TestCase
{
    private const PRODUCT_BASE = '/product/';
    private const BASE_URL = 'rates';

    public function testUnAuthrizedUserCanNotLoadRates()
    {
        $p = Product::find(20);

        $this->get(
            self::PRODUCT_BASE . $p->slug . '/' . self::BASE_URL
        )->seeStatusCode(401);
    }

    public function testLodingProductRatesBySlug()
    {
        $this->withoutExceptionHandling();
        $this->passportSignIn();

        $p = Product::find(20);

        $this->get(
            self::PRODUCT_BASE . $p->slug . '/' . self::BASE_URL.'/8'
        )->seeStatusCode(200)
        ->seeJsonContains(['per_page' => 8])
        ->seeJsonContains(['rate' => $p->rates->first()->rate]);
    }
}
