<?php

/**
 * 机器人配置文件模板（请勿直接当作 config/bots.php 使用）
 *
 * 部署步骤：
 *   1. 复制本文件为 config/bots.php（该文件已被 .gitignore 忽略，不会进入版本库）
 *   2. 填入你自己的 app_id / client_secret
 *   3. 敏感信息（如 client_secret）建议改为通过环境变量注入，避免写进仓库
 *
 * 支持的环境变量（可选，未设置时回退到下方硬编码值）：
 *   QQBOT_BOT1_SECRET / QQBOT_BOT2_SECRET  —— 覆盖对应机器人的 client_secret
 *   QQBOT_ADMIN_PASSWORD                    —— 覆盖后台默认登录口令（默认 admin123）
 *   QQBOT_ADMIN_TOKEN                       —— 启用并覆盖后台 API Token 鉴权
 *   QQBOT_ENABLE_EDITOR                     —— 设为 1 才允许后台在线文件编辑器写入
 */

return [

    'bots' => [

        'bot1' => [

            'app_id'        => 'YOUR_APP_ID_1',

            // 推荐：用环境变量注入密钥，留空字符串代表「仅使用环境变量」
            'client_secret' => $_ENV['QQBOT_BOT1_SECRET'] ?? 'YOUR_CLIENT_SECRET_1',

            'intents'       => 1 << 25,

            'sandbox'       => false,

            'nickname'      => '我的机器人',

        ],

        'bot2' => [

            'app_id'        => 'YOUR_APP_ID_2',

            'client_secret' => $_ENV['QQBOT_BOT2_SECRET'] ?? 'YOUR_CLIENT_SECRET_2',

            'intents'       => 1 << 25,

            'sandbox'       => false,

            'nickname'      => '二号机器人',

        ],

    ],

    // Webhook 回调地址前缀与签名校验（生产环境务必保持 verify_sign = true）
    'webhook' => [
        'path_prefix'   => '/webhook',
        'verify_sign'   => true,
        'msg_seq_start' => 1,
    ],

    'log_path' => __DIR__ . '/logs/',

    'data_path' => __DIR__ . '/data/',
];
