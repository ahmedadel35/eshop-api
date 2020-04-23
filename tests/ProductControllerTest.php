<?php

use Laravel\Lumen\Testing\DatabaseMigrations;
use Laravel\Lumen\Testing\DatabaseTransactions;

class ProductControllerTest extends TestCase
{
    use DatabaseTransactions;

    private const BASE_URL = '/products/';

    public function testLoadingAllProductsIds()
    {
        // $this->withoutExceptionHandling();
        $this->passportSignIn();

        $this->get(self::BASE_URL . 'ids')
            ->seeStatusCode(200)
            ->seeJsonContains(
                ['current_page' => 1, 'per_page' => 50]
            );

        $this->get(self::BASE_URL . 'ids?page=2')
            ->seeStatusCode(200)
            ->seeJsonContains(
                ['current_page' => 2]
            );
    }

    public function testLoadingAllProductsIdsWithSettingPerPage()
    {
        $this->passportSignIn();

        $this->get(self::BASE_URL . 'ids/20')
            ->seeStatusCode(200)
            ->seeJsonContains(
                ['current_page' => 1, 'per_page' => 20]
            );
    }
}
