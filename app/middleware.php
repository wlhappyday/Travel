<?php
<<<<<<< HEAD
    return [
        \think\middleware\AllowCrossDomain::class,
        \app\middleware\Auth::class,
        \app\middleware\After::class,
//

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
>>>>>>> 94315b523dc0423556f91f6e3fa02697459c7a53
?>