<?php

use app\middleware\Auth;

return [
//    Auth::class,
//    After::class,
\app\middleware\Auth::class,
    \app\middleware\After::class,
]
?>