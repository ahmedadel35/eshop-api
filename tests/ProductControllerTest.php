<?php

use App\Category;
use App\Http\Controllers\ProductController;
use App\Product;
use Illuminate\Support\Arr;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Laravel\Lumen\Testing\DatabaseTransactions;

class ProductControllerTest extends TestCase
{
    use DatabaseTransactions;

    private const BASE_URL = '/product/';

    /**
     * @dataProvider loadingAllProductsDataProvider
     */
    public function testLoadingAllProducts(string $segment)
    {
        // $this->withoutExceptionHandling();
        $this->passportSignIn();

        $this->get(self::BASE_URL . $segment)
            ->seeStatusCode(200)
            ->seeJsonContains(
                ['current_page' => 1, 'per_page' => ProductController::PER_PAGE]
            );

        $this->get(self::BASE_URL . $segment . '?page=2')
            ->seeStatusCode(200)
            ->seeJsonContains(
                ['current_page' => 2]
            );
    }

    /**
     * @dataProvider loadingAllProductsDataProvider
     */
    public function testLoadingAllProductsIdsWithSettingPerPage(string $segment)
    {
        $this->passportSignIn();

        $this->get(self::BASE_URL . $segment . '/20')
            ->seeStatusCode(200)
            ->seeJsonContains(
                ['current_page' => 1, 'per_page' => 20]
            );
    }

    public function testshowingProductWithSlug()
    {
        $this->passportSignIn();

        $p = Product::find(random_int(1, 1500));
        unset($p->rates);

        $this->get(self::BASE_URL . $p->slug)
            ->seeStatusCode(200)
            ->seeJson($p->toArray());
    }

    public function testLoadingProductListBySubCategorySlug()
    {
        $this->passportSignIn();

        $sub = Category::whereNotNull('category_id')
            ->limit(1)
            ->get('slug')[0];

        $this->get(self::BASE_URL . 'sub/' . $sub->slug)
            ->seeStatusCode(200)
            ->seeJsonContains(
                ['current_page' => 1, 'per_page' => ProductController::PER_PAGE]
            );

        $this->get(self::BASE_URL . 'sub/' . $sub->slug . '?page=2')
            ->seeStatusCode(200)
            ->seeJsonContains(
                ['current_page' => 2]
            );

        $this->get(self::BASE_URL . 'sub/' . $sub->slug . '/30?page=2')
            ->seeStatusCode(200)
            ->seeJsonContains(
                ['current_page' => 2, 'per_page' => 30]
            );
    }

    public function testLoadingListOfProductsByIds()
    {
        $this->passportSignIn();

        $products = Product::all();

        $ids = Arr::random($products->pluck('id')->toArray(), 10);

        // load with rates
        $this->get(
            self::BASE_URL . 'collect/' . implode(',', $ids) . '?rates=1'
        )->seeStatusCode(200)
            ->seeJsonContains((Product::find($ids[0]))->toArray());

        // load without rates
        $this->get(
            self::BASE_URL . 'collect/' . implode(',', $ids)
        )->seeStatusCode(200)
            ->seeJsonContains((Product::without('rates')->find($ids[0]))->toArray());
    }

    public function testLoadingListOfProductsByIdsRequiresIdsListIsLessThanOneThousand()
    {
        // $this->withoutExceptionHandling();
        $this->passportSignIn();

        $products = Product::all();

        $ids = Arr::random($products->pluck('id')->toArray(), 501);

        // load with rates
        $this->get(
            self::BASE_URL . 'collect/' . implode(',', $ids) . '?rates=1'
        )->seeStatusCode(413);
    }

    public function testStoringNewProductRejectedWithInvalidData()
    {
        $this->passportSignIn();

        $this->post(self::BASE_URL, [])
            ->seeStatusCode(422);
    }

    public function testUserCanStoreNewProduct()
    {
        $this->withoutExceptionHandling();
        $this->passportSignIn();

        $sc = Category::whereNotNull('category_id')->find(2);
        $p = factory(Product::class)->make();

        $pData = [
            'category' => $sc->id,
            'name' => $p->name,
            'brand' => $p->brand,
            'info' => $p->info,
            'price' => $p->price,
            'amount' => $p->amount,
            'save' => $p->save,
            'color' => implode(',', $p->color),
            'is_used' => $p->is_used
        ];

        $this->post(self::BASE_URL, $pData)
            ->seeStatusCode(201);
    }

    public function testUserCanNotUpdateProductWithInvalidData()
    {
        $this->passportSignIn();

        $p = Product::find(5);

        $this->post(self::BASE_URL . $p->slug . '/patch', [])
            ->seeStatusCode(422);
    }

    public function testUserCanUpdateProductOnlyOwnedProducts()
    {
        // authrized user
        $user = $this->passportSignIn();

        $p = factory(Product::class)->create([
            'user_id' => $user->id
        ]);

        $pData = [
            'name' => $p->name,
            'brand' => $p->brand,
            'info' => $p->info,
            'price' => $p->price,
            'amount' => $p->amount,
            'save' => $p->save,
            'color' => implode(',', $p->color),
            'is_used' => $p->is_used
        ];

        $this->post(self::BASE_URL . $p->slug . '/patch', $pData)
            ->seeStatusCode(204);

        // unauthorized user
        $this->passportSignIn(8);
        $this->post(self::BASE_URL . $p->slug . '/patch', $pData)
            ->seeStatusCode(403);
    }

    public function testSuperUserOrAdminCanUpdateAnyProduct()
    {
        $p = factory(Product::class)->create([
            'user_id' => 25
        ]);

        $pData = [
            'name' => $p->name . 'wzcasd asdwc',
            'brand' => $p->brand,
            'info' => $p->info,
            'price' => $p->price,
            'amount' => $p->amount,
            'save' => $p->save,
            'color' => implode(',', $p->color),
            'is_used' => $p->is_used
        ];

        // super user
        $user = $this->passportSignIn(2);
        $this->assertTrue($user->isSuper());
        $this->post(self::BASE_URL . $p->slug . '/patch', $pData)
            ->seeStatusCode(204);

        // admin user
        $user = $this->passportSignIn(1);
        $this->assertTrue($user->isAdmin());
        $this->post(self::BASE_URL . $p->slug . '/patch', $pData)
            ->seeStatusCode(204);
    }

    public function testUserCanDeleteOnlyOwnedProducts()
    {
        // authrized user
        $user = $this->passportSignIn();

        $p = factory(Product::class)->create([
            'user_id' => $user->id
        ]);

        $this->post(self::BASE_URL . $p->slug . '/delete')
            ->seeStatusCode(204);

        // unauthrized user
        $p = factory(Product::class)->create([
            'user_id' => $user->id
        ]);
        $this->passportSignIn(25);
        $this->post(self::BASE_URL . $p->slug . '/delete')
            ->seeStatusCode(403);
    }

    public function testAdminCanDeleteAnyProduct()
    {
        $user = $this->passportSignIn(1);
        $this->assertTrue($user->isAdmin());

        $p = factory(Product::class)->create([
            'user_id' => 7
        ]);

        $this->post(self::BASE_URL . $p->slug . '/delete')
            ->seeStatusCode(204);
    }

    public function testProductsCanBeFilteredByBrands()
    {
        $this->passportSignIn();

        $sc = Category::whereNotNull('category_id')->first();
        $products = Product::whereCategorySlug($sc->slug)->limit(30)->get();

        $brands = Arr::pluck($products, 'brand');
        $brands = Arr::shuffle($brands);
        $brands = implode(',', $brands);

        $this->get(
            self::BASE_URL .
                'filter/sub/' .
                $sc->slug .
                '/brands/' .
                $brands
        )->seeStatusCode(200)
            ->seeJsonContains(['name' => $products->first()->name]);
    }

    public function testProductsCanBeFilteredByCondtion()
    {
        $this->passportSignIn();

        $sc = Category::whereNotNull('category_id')->first();
        $products = Product::whereCategorySlug($sc->slug)->where('is_used', true)->get();

        $this->get(
            self::BASE_URL . 'filter/sub/' . $sc->slug .
                '/condition/1'
        )->seeStatusCode(200)
            ->seeJsonContains([
                'name' => $products->last()->name,
                'per_page' => ProductController::PER_PAGE
            ]);

        $this->get(
            self::BASE_URL . 'filter/sub/' . $sc->slug .
                '/condition/1/20'
        )->seeStatusCode(200)
            ->seeJsonContains([
                'name' => $products->last()->name,
                'per_page' => 20
            ]);
    }

    public function loadingAllProductsDataProvider(): array
    {
        return [
            [
                'load Products ids' => 'ids',
                'load products list' => 'list',
            ]
        ];
    }
}
