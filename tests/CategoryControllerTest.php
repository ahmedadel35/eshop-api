<?php

use App\Category;
use App\User;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Laravel\Lumen\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
// use Illuminate\Foundation\Testing\WithFaker;

class CategoryControllerTest extends TestCase
{
    use DatabaseTransactions;


    private const BASE_URL = '/categories/';

    public function testOnlyAuthrizedAppCanGetBaseCategoriesList()
    {
        $this->get(self::BASE_URL . '/base')
            ->assertResponseStatus(401);
    }

    public function testUserCanGetBaseCategoriesList()
    {
        // $this->withoutExceptionHandling();
        Passport::actingAs(
            factory(User::class)->create()
        );

        $this->get(self::BASE_URL . 'base')
            ->seeStatusCode(200)
            ->seeJsonEquals(
                Category::whereNull('category_id')->get()->toArray()
            );
    }

    public function testUserCanGetSubCategoriesIds()
    {
        Passport::actingAs(
            factory(User::class)->create()
        );

        $this->get(self::BASE_URL . 'sub/ids')
            ->seeStatusCode(200)
            ->seeJsonContains(['id' => 5]);
    }

    public function testUserCanGetSubCategoriesList()
    {
        Passport::actingAs(
            factory(User::class)->create()
        );

        $this->get(self::BASE_URL . 'sub/list')
            ->seeStatusCode(200)
            ->seeJsonEquals(
                Category::whereNotNull('category_id')->with('parent')->get()->toArray()
            );
    }
}
