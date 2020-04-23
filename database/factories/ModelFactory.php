<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Category;
use App\DailyDeal;
use App\Order;
use App\Product;
use App\ProductInfo;
use App\Rate;
use App\User;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

/*
|--------------------------------------------------------------------------
| Model Factories
|--------------------------------------------------------------------------
|
| This directory should contain each of the model factory definitions for
| your application. Factories provide a convenient way to generate new
| model instances for testing / seeding your application's database.
|
*/

$factory->define(User::class, function ($faker) {
    return [
        'name' => $faker->name,
        'email' => $faker->unique()->safeEmail,
        'email_verified_at' => Carbon::now(),
        'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // password
        'remember_token' => Str::random(10),
    ];
});

$factory->define(Category::class, function ($faker) {
    return [
        "name" => $faker->sentence(2),
        'category_id' => rand(0, 1)===1 ? null : factory(Category::class)
    ];
});

$factory->define(DailyDeal::class, function ($faker) {
    return [
        'product_id' => factory(Product::class)
    ];
});

$factory->define(Order::class, function ($faker) {
    return [
        'user_id' => factory(User::class)->create(),
        'product_id' => factory(Product::class)->create(),
        'address' => $faker->address,
        'amount' => $faker->randomNumber(3),
        'total' => $faker->randomFloat(2, 15, 100000),
        'sent' => $faker->boolean
    ];
});

$factory->define(Product::class, function ($faker) {
    return [
        'user_id' => factory(User::class)->create(),
        // 'category_slug' => (factory(Category::class)->create())->slug,
        'name' => $faker->sentence,
        'info' => $faker->text,
        'price' => $faker->randomFloat(1, 50, 100000),
        'save' => rand(0, 99),
        'amount' => rand(1, 25),
        'is_used' => $faker->boolean(),
        'brand' => Arr::random(['somecompany', 'apple', 'nokia', 'microsoft', 'another company']),
        'color' => [$faker->colorName, $faker->colorName],
        'img' => [rand(1, 15) . '.jpg', rand(1, 15) . '.jpg', rand(1, 15) . '.jpg']
    ];
});


$factory->define(ProductInfo::class, function ($faker) {
    $info_arr = [
        // 'brand' => $faker->sentence,
        'package thickness' => $faker->randomFloat(4),
        'product weight' => $faker->randomFloat(4) . Arr::random(['Gram', 'Kg', 'Litre', 'Meter']),
        'package weight' => $faker->randomFloat(5) . Arr::random(['Gram', 'Kg', 'Litre', 'Meter']),
        'serial scan required' => false
    ];

    $randomVal = function () use ($faker) {
        return [
            $faker->randomDigit . Arr::random(['Gram', 'Kg', 'Litre', 'Meter']),
            $faker->unique()->sentence(4),
            $faker->boolean
        ];
    };

    for ($i = 0; $i < rand(25, 60); $i++) {
        $key = $faker->unique()->sentence(3);
        $value = Arr::random($randomVal());
        // check if this key is not already in info array
        // if (!isset($info_arr[$key])) {
        $info_arr[$key] = $value;
        // }
    }

    return [
        'product_id' => factory(Product::class),
        'info' => $info_arr
    ];
});


$factory->define(Rate::class, function ($faker) {
    return [
        'user_id' => factory(User::class)->create(),
        'product_id' => factory(Product::class)->create(),
        'rate' => $faker->randomFloat(1, 0, 5),
        'message' => $faker->sentence
    ];
});


$factory->define(DailyDeal::class, function ($faker) {
    return [
        'product_id' => factory(Product::class)
    ];
});