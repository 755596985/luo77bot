<?php

return [

    'bots' => [

        'bot1' => [

            'app_id'        => '102009159',

            'client_secret' => 'YOUR_CLIENT_SECRET_1',

            'intents'       => 1 << 25,

            'sandbox'       => false,

            'nickname'      => '我的机器人',

        ],

        'bot2' => [

            'app_id'        => '1905431485',

            'client_secret' => 'YOUR_CLIENT_SECRET_2',

            'intents'       => 1 << 25,

            'sandbox'       => false,

            'nickname'      => '二号机器人',

        ],

    ],

    'log_path' => __DIR__ . '/logs/',

    'data_path' => __DIR__ . '/data/',

];