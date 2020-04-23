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
        $this->passportSignIn();

        $this->get(self::BASE_URL . 'base')
            ->seeStatusCode(200)
            ->seeJsonEquals(
                Category::whereNull('category_id')->get()->toArray()
            );
    }

    public function testUserCanGetSubCategoriesIds()
    {
        $this->passportSignIn();

        $this->get(self::BASE_URL . 'sub/ids')
            ->seeStatusCode(200)
            ->seeJsonContains(['id' => 5]);
    }

    public function testUserCanGetSubCategoriesList()
    {
        $this->passportSignIn();

        $this->get(self::BASE_URL . 'sub/list')
            ->seeStatusCode(200)
            ->seeJsonEquals(
                Category::whereNotNull('category_id')->with('parent')->get()->toArray()
            );
    }

    public function testUserCanGetSubCategoryWithSlug()
    {
        $this->passportSignIn();

        /** @var \App\Category $sub */
        $sub = (Category::whereNotNull('category_id')
            ->get())->first();

        $sub->loadMissing('parent');

        $this->get(self::BASE_URL . 'sub/' . $sub->slug)
            ->seeStatusCode(200)
            ->seeJsonContains(['name' => $sub->name])
            ->seeJsonContains(['parent' => $sub->parent->toArray()]);
    }

    public function testOnlyAuthrizedUserCanCreateSubCategory()
    {
        // $this->withoutExceptionHandling();
        $this->passportSignIn(null, ['create-sub']);

        /** @var \App\Category $c */
        $c = Category::whereNull('category_id')
            ->limit(2)
            ->get('id')[1];
        $name = 'some name';

        $this->post(self::BASE_URL . 'sub', [
            'parent_id' => $c->id,
            'name' => $name
        ])->seeStatusCode(201)
            ->seeJson(['name' => $name]);

        $this->seeInDatabase('categories', ['name' => $name]);

        // user without authorized scope
        $this->passportSignIn();
        $this->post(self::BASE_URL . 'sub', [])
            ->seeStatusCode(403);
    }
}
