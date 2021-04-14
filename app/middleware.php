<?php
    return [
        \think\middleware\AllowCrossDomain::class,
        \app\middleware\Auth::class,
        \app\middleware\After::class,
//

    ]
?>