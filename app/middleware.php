<?php

use app\middleware\After;
use app\middleware\Auth;
use think\middleware\AllowCrossDomain;

return [
    Auth::class,
    After::class,
    AllowCrossDomain::class
]
?>