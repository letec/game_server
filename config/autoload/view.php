<?php

declare(strict_types=1);

use Hyperf\View\Mode;
use Hyperf\View\Engine\BladeEngine;

return [
    'engine' => BladeEngine::class,
    'mode'   => Mode::TASK,
    'config' => [
        'view_path'  => BASE_PATH . '/view',
        'cache_path' => BASE_PATH . '/runtime/cache',
    ],
];