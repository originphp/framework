<?php

use Origin\Http\Session\Engine\ArrayEngine as SessionEngine;

return [
    'className' => SessionEngine::class,
    'name' => 'id',
    'idLength' => 32, // Must be at least 128 bits (16 bytes)
    'timeout' => 900
];
