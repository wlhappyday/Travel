<?php
<<<<<<< HEAD
    return [
        \app\middleware\Auth::class,
        \app\middleware\After::class

    ]
=======

use app\middleware\After;
use app\middleware\Auth;
use think\middleware\AllowCrossDomain;

return [
    Auth::class,
    After::class,
    AllowCrossDomain::class
]
>>>>>>> 1d60d9487a575e51b12943ce63dab7df264506df
?>