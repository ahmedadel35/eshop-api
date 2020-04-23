<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$router->get('/', function () use ($router) {
    return $router->app->version();
});

$router->group(
    ['middleware' => ['auth:api', 'throttle:30,1']],
    function () use ($router) {
        // categories
        $router->group(['prefix' => 'categories'],
        function () use ($router) {
            $router->get('base', 'CategoryController@index');
            $router->get('sub/ids', 'CategoryController@getSubIds');
            $router->get('sub/list', 'CategoryController@getSubList');
            $router->get('/sub/{slug}', 'CategoryController@show');

            $router->post('/sub', 'CategoryController@store');
        });
    }
);
