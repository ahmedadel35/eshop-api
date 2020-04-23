<?php

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
}
