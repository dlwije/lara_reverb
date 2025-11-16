<?php

$providers = [
    App\Providers\AppServiceProvider::class,
//    App\Providers\AuthServiceProvider::class,
    App\Providers\FortifyServiceProvider::class,
    App\Providers\JetstreamServiceProvider::class,
    App\Providers\BroadcastServiceProvider::class,
];

if (file_exists(base_path('modules/Shop/ShopProvider.php'))) {
    $providers[] = Modules\Shop\ShopProvider::class;
}
return $providers;
