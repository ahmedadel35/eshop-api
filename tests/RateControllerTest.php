<?php

use App\Product;
use App\Rate;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Laravel\Lumen\Testing\DatabaseTransactions;

class RateControllerTest extends TestCase
{
    private const PRODUCT_BASE = '/product/';
    private const BASE_URL = 'rates';

    public function testUnAuthrizedUserCanNotLoadRates()
    {
        $this->get($this->getBaseUrl())->seeStatusCode(401);
    }

    public function testLodingProductRatesBySlug()
    {
        $this->withoutExceptionHandling();
        $this->passportSignIn();

        [$p, $baseUrl] = $this->getBaseUrl();

        $this->get($baseUrl . '/8')->seeStatusCode(200)
            ->seeJsonContains(['per_page' => 8])
            ->seeJsonContains(['rate' => $p->rates->first()->rate]);
    }

    public function testUserCanNotAddRateWithInvalidData()
    {
        $this->passportSignIn();

        [$p, $baseUrl] = $this->getBaseUrl();

        $this->post($baseUrl, [])
            ->seeStatusCode(422);
    }

    public function testUserCanAddRateOnlyOnce()
    {
        $this->passportSignIn();

        $p = factory(Product::class)->create();

        [$p, $baseUrl] = $this->getBaseUrl($p->id);

        $message = 'some words combined';

        $this->post($baseUrl, [
            'rate' => random_int(1, 5),
            'message' => $message
        ])->seeStatusCode(201)
            ->seeJsonContains(['message' => $message]);

        $this->seeInDatabase('rates', ['message' => $message]);

        $this->post($baseUrl, [
            'rate' => random_int(1, 5),
            'message' => $message
        ])->seeStatusCode(403);
    }

    public function testUserCanNotRateOwnedProduct()
    {
        $user = $this->passportSignIn();

        $p = Product::whereUserId($user->id)
            ->without('rates')
            ->get()[0];

        [$p, $baseUrl] = $this->getBaseUrl($p->id);

        $message = 'some words combined';

        $this->post($baseUrl, [
            'rate' => random_int(1, 5),
            'message' => $message
        ])->seeStatusCode(403);
    }

    public function testUserCanUpdateRate()
    {
        $this->withoutExceptionHandling();
        $user = $this->passportSignIn();

        $rate = Rate::with('product')
            ->whereUserId($user->id)
            ->get()[0];
        [$p, $baseUrl] = $this->getBaseUrl($rate->product->id);

        $message = 'some thing';

        $this->post($baseUrl . '/' . $rate->id, [
            'rate' => $rate->rate,
            'message' => $message
        ])->seeStatusCode(204);

        $this->seeInDatabase('rates', ['message' => $message]);
    }

    private function getBaseUrl(
        int $productId = null
    ): array {
        $p = Product::find($productId ?? random_int(20, 200));
        $baseUrl = self::PRODUCT_BASE . $p->slug . '/' . self::BASE_URL;

        return [$p, $baseUrl];
    }
}
