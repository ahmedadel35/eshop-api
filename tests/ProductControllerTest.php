<?php

use Laravel\Lumen\Testing\DatabaseMigrations;
use Laravel\Lumen\Testing\DatabaseTransactions;

class ProductControllerTest extends TestCase
{
    use DatabaseTransactions;

    private const BASE_URL = '/products/';

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
                ['current_page' => 1, 'per_page' => 50]
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
