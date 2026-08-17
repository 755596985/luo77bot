<?php

declare(strict_types=1);

/**
 * 回调推送入口
 *
 * 在后台「回调推送」页面生成推送地址后，填入第三方网站的
 * 推送地址 / Webhook / 回调地址即可。
 * 收到推送后会自动通过指定机器人发送 C2C 私聊消息给接收人。
 *
 * 示例推送地址: https://your-domain.com/callback.php?token=<后台生成的Token>
 */

require_once __DIR__ . '/vendor/autoload.php';

use QQBot\Core\Application;

header('Content-Type: application/json');

// 1. 加载回调配置
$dataDir    = __DIR__ . '/data';
$configFile = $dataDir . '/callback.json';
$config     = [];
if (is_file($configFile)) {
    $config = json_decode((string)file_get_contents($configFile), true) ?: [];
}

$token = (string)($config['token'] ?? '');

// 2. 校验 Token（支持 GET/POST 参数、Header X-Callback-Token、JSON body 中的 token）
$requestToken = (string)($_GET['token'] ?? $_POST['token'] ?? '');
if ($requestToken === '') {
    $requestToken = (string)($_SERVER['HTTP_X_CALLBACK_TOKEN'] ?? '');
}
$rawBody = (string)file_get_contents('php://input');
if ($requestToken === '' && $rawBody !== '') {
    $bodyJson = json_decode($rawBody, true);
    if (is_array($bodyJson)) {
        $requestToken = (string)($bodyJson['token'] ?? '');
    }
}

if ($token === '' || !hash_equals($token, $requestToken)) {
    http_response_code(401);
    echo json_encode(['code' => 401, 'message' => 'invalid token']);
    exit;
}

// 3. 检查开关与接收人
$enabled        = (bool)($config['enabled'] ?? true);
$botId          = (string)($config['bot_id'] ?? 'bot2');
$receiverOpenid = (string)($config['receiver_openid'] ?? '');

if (!$enabled) {
    http_response_code(403);
    echo json_encode(['code' => 403, 'message' => 'callback disabled']);
    exit;
}

if ($receiverOpenid === '') {
    http_response_code(503);
    echo json_encode(['code' => 503, 'message' => 'receiver_openid not configured, please set it in admin panel']);
    exit;
}

// 4. 提取推送内容与来源
$source  = (string)($_GET['source'] ?? $_POST['source'] ?? '');
$content = '';

if ($rawBody !== '') {
    $bodyJson = json_decode($rawBody, true);
    if (is_array($bodyJson)) {
        // JSON body：优先取常见内容字段，否则返回整个 JSON
        $content = (string)($bodyJson['content'] ?? $bodyJson['message'] ?? $bodyJson['text'] ?? $bodyJson['msg'] ?? $rawBody);
        if ($source === '') {
            $source = (string)($bodyJson['source'] ?? $bodyJson['source_name'] ?? '');
        }
    } else {
        $content = $rawBody;
    }
} else {
    // 表单 body
    $content = (string)($_POST['content'] ?? $_POST['message'] ?? $_POST['text'] ?? $_POST['msg'] ?? '');
    if ($content === '') {
        $content = json_encode($_POST, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}

if ($content === '' || $content === 'null' || $content === '[]' || $content === '{}') {
    $content = $rawBody !== '' ? $rawBody : json_encode($_POST, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

if ($source === '') {
    $source = (string)($_SERVER['HTTP_HOST'] ?? 'unknown');
}

// 5. 组装消息文本
$time     = date('Y-m-d H:i:s');
$ip       = (string)($_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '');
$method   = (string)($_SERVER['REQUEST_METHOD'] ?? 'POST');
$template = (string)($config['template'] ?? '【回调通知】\n来源: {source}\n内容: {content}\n时间: {time}');

$text = str_replace(
    ['{content}', '{source}', '{time}', '{ip}', '{method}'],
    [$content, $source, $time, $ip, $method],
    $template
);
$text = str_replace('\\n', "\n", $text);

// 6. 记录日志（发送成功后写入最终状态）
$logDir = $dataDir . '/callbacks';
if (!is_dir($logDir)) {
    @mkdir($logDir, 0777, true);
}
$logFile = $logDir . '/' . date('Ymd') . '.jsonl';
$shortContent = (function_exists('mb_substr') ? mb_substr($content, 0, 500, 'UTF-8') : substr($content, 0, 500));

// 7. 通过机器人发送 C2C 消息
try {
    $app = new Application(__DIR__ . '/config/bots.php');
    $app->boot();

    $bot = $app->getBotManager()->getBot($botId);
    if ($bot === null) {
        throw new RuntimeException('bot not found: ' . $botId);
    }

    $result = $bot->getClient()->sendC2CMessage($receiverOpenid, ['content' => $text]);

    $logLine = json_encode([
        'time'    => $time,
        'ip'      => $ip,
        'source'  => $source,
        'method'  => $method,
        'status'  => 'sent',
        'content' => $shortContent,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    @file_put_contents($logFile, $logLine . "\n", FILE_APPEND | LOCK_EX);

    echo json_encode(['code' => 0, 'message' => 'ok', 'sent' => true, 'msg_id' => $result['id'] ?? null]);
} catch (\Throwable $e) {
    $logLine = json_encode([
        'time'    => $time,
        'ip'      => $ip,
        'source'  => $source,
        'method'  => $method,
        'status'  => 'failed',
        'error'   => $e->getMessage(),
        'content' => $shortContent,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    @file_put_contents($logFile, $logLine . "\n", FILE_APPEND | LOCK_EX);

    http_response_code(502);
    echo json_encode(['code' => 502, 'message' => 'send failed', 'error' => $e->getMessage()]);
}
