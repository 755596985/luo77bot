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
$orderNo = null;

if ($rawBody !== '') {
    $bodyJson = json_decode($rawBody, true);
    if (is_array($bodyJson)) {
        // JSON body：优先取常见内容字段
        $content = (string)($bodyJson['content'] ?? $bodyJson['message'] ?? $bodyJson['text'] ?? $bodyJson['msg'] ?? '');
        if ($content === '') {
            // 无常见内容字段时：按订单模板提取关键字段（单号/商品/商品ID/价格），其余字段忽略
            $pick = function (array $keys) use ($bodyJson) {
                foreach ($keys as $k) {
                    if (array_key_exists($k, $bodyJson) && $bodyJson[$k] !== '' && $bodyJson[$k] !== null) {
                        $v = $bodyJson[$k];
                        if (is_array($v) || is_object($v)) {
                            $v = json_encode($v, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                        }
                        return $v;
                    }
                }
                return null;
            };
            $orderNo  = $pick(['ordersn', 'order_id', 'id', 'order_no', 'order_sn', 'trade_no', 'out_trade_no']);
            $goodsName = $pick(['goods_name', 'product_name', 'goods', 'product', 'name', 'title', 'item']);
            $goodsId  = $pick(['goods_id', 'product_id', 'sku']);
            $price    = $pick(['price', 'amount', 'money', 'total_amount', 'pay_price', 'cost']);

            $parts = [];
            if ($orderNo !== null)   { $parts[] = '单号: ' . $orderNo; }
            if ($goodsName !== null) { $parts[] = '商品: ' . $goodsName; }
            if ($goodsId !== null)   { $parts[] = '商品ID: ' . $goodsId; }
            if ($price !== null)     { $parts[] = '价格: ' . $price; }

            if ($parts !== []) {
                $content = "来新订单了\n" . implode("\n", $parts);
            } else {
                // 未匹配到订单字段时，兜底逐行输出原始 JSON 字段（中文不转义）
                $fieldNames = [
                    'status' => '状态', 'sign' => '签名', 'type' => '类型', 'source' => '来源', 'remark' => '备注', 'msg' => '消息', 'time' => '时间',
                ];
                $parts = [];
                foreach ($bodyJson as $k => $v) {
                    if (is_array($v) || is_object($v)) {
                        $v = json_encode($v, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                    }
                    $label = $fieldNames[$k] ?? $k;
                    $parts[] = $label . ': ' . $v;
                }
                $content = implode("\n", $parts);
            }
        }
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

// 兜底：还原 \uXXXX 形式的中文转义（第三方推送内容未按 UTF-8 解码时）
if (strpos($content, '\\u') !== false) {
    $content = preg_replace_callback('/\\\\u([0-9a-fA-F]{4})/', function ($m) {
        return mb_convert_encoding(pack('H*', $m[1]), 'UTF-8', 'UCS-2BE');
    }, $content);
}

if ($content === '' || $content === 'null' || $content === '[]' || $content === '{}') {
    $content = $rawBody !== '' ? $rawBody : json_encode($_POST, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

if ($source === '') {
    $source = (string)($_SERVER['HTTP_HOST'] ?? 'unknown');
}

// 4.5 自动拉取充值账号：调用供货商 order/copy 接口（获取后订单自动变为处理中 status=2）
$extraLine = '';
if ($orderNo !== null && $orderNo !== '' && (bool)($config['copy_enabled'] ?? false)) {
    $copyUrl    = (string)($config['copy_url'] ?? '');
    $copyToken  = (string)($config['copy_token'] ?? '');
    $copyParam  = (string)($config['copy_param'] ?? 'orderSn');
    $copyMethod = strtoupper((string)($config['copy_method'] ?? 'POST'));
    $sep        = strpos($copyUrl, '?') === false ? '?' : '&';

    // 依次尝试常见 Token 传法，命中 code=0 且有 recharge_account 即成功
    // POST 优先把 Token 放入 JSON body（第三方接口规范：{"token":"...","orderSn":"..."}）
    $attempts = [
        ['type' => 'body'],
        ['type' => 'header', 'name' => 'token'],
        ['type' => 'header', 'name' => 'Token'],
        ['type' => 'query',  'name' => 'token'],
    ];
    foreach ($attempts as $at) {
        $target  = $copyUrl;
        $headers = ['Accept: application/json'];
        if ($copyMethod === 'POST') {
            // POST：参数与 Token 放入 JSON body（接口规范），也保留 header / URL 参数备选
            if ($at['type'] === 'body' && $copyToken !== '') {
                $body = json_encode([$copyParam => $orderNo, 'token' => $copyToken], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                $headers[] = 'Content-Type: application/json';
            } elseif ($at['type'] === 'header' && $copyToken !== '') {
                $headers[] = $at['name'] . ': ' . $copyToken;
                $body = json_encode([$copyParam => $orderNo], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                $headers[] = 'Content-Type: application/json';
            } else {
                $headers[] = 'Content-Type: application/json';
                $body = json_encode([$copyParam => $orderNo], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                if ($at['type'] === 'query' && $copyToken !== '') {
                    $target .= $sep . http_build_query([$at['name'] => $copyToken]);
                }
            }
            $ch = curl_init($target);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 10,
                CURLOPT_CONNECTTIMEOUT => 5,
                CURLOPT_HTTPHEADER     => $headers,
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => $body,
            ]);
        } else {
            // GET：参数放入 URL
            $target .= $sep . http_build_query([$copyParam => $orderNo]);
            if ($at['type'] === 'header' && $copyToken !== '') {
                $headers[] = $at['name'] . ': ' . $copyToken;
            } elseif ($at['type'] === 'query' && $copyToken !== '') {
                $target .= $sep . http_build_query([$at['name'] => $copyToken]);
            }
            $ch = curl_init($target);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 10,
                CURLOPT_CONNECTTIMEOUT => 5,
                CURLOPT_HTTPHEADER     => $headers,
            ]);
        }
        $resp = curl_exec($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($resp === false || $httpCode >= 400) {
            continue;
        }
        $json = json_decode((string)$resp, true);
        if (is_array($json) && (int)($json['code'] ?? -1) === 0 && !empty($json['data']['recharge_account'])) {
            $extraLine = '充值账号: ' . (string)$json['data']['recharge_account'];
            break;
        }
    }
    if ($extraLine !== '') {
        $content .= "\n" . $extraLine;
    }
}

// 5. 组装消息文本
$time     = date('Y-m-d H:i:s');
$ip       = (string)($_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '');
$method   = (string)($_SERVER['REQUEST_METHOD'] ?? 'POST');

if (strncmp($content, '来新订单了', 5) === 0) {
    // 订单模板排版：直接输出（不带【回调通知】标题、来源），末尾带时间
    $text = $content . "\n时间: " . $time;
} else {
    // 其他内容：使用后台配置模板
    $template = (string)($config['template'] ?? '{content}');
    $text = str_replace(
        ['{content}', '{source}', '{time}', '{ip}', '{method}'],
        [$content, $source, $time, $ip, $method],
        $template
    );
    $text = str_replace('\\n', "\n", $text);
}

// 6. 记录日志（发送成功后写入最终状态）
$logDir = $dataDir . '/callbacks';
if (!is_dir($logDir)) {
    @mkdir($logDir, 0777, true);
}
$logFile = $logDir . '/' . date('Ymd') . '.jsonl';
$shortContent = (function_exists('mb_substr') ? mb_substr($content, 0, 500, 'UTF-8') : substr($content, 0, 500));

// 7. 通过机器人发送 C2C 消息（带重试，避免瞬时故障丢单）
$sendOk  = false;
$lastErr = null;
$maxAttempts = 3;

try {
    $app = new Application(__DIR__ . '/config/bots.php');
    $app->boot();

    $bot = $app->getBotManager()->getBot($botId);
    if ($bot === null) {
        throw new RuntimeException('bot not found: ' . $botId);
    }

    for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
        try {
            $bot->getClient()->sendC2CMessage($receiverOpenid, ['content' => $text]);
            $sendOk = true;
            break;
        } catch (\Throwable $e) {
            $lastErr = $e;
            if ($attempt < $maxAttempts) {
                usleep(500000); // 0.5s 后重试
            }
        }
    }
} catch (\Throwable $e) {
    $lastErr = $e;
}

$logLineBase = [
    'time'    => $time,
    'ip'      => $ip,
    'source'  => $source,
    'method'  => $method,
    'content' => $shortContent,
];

if ($sendOk) {
    $logLine = json_encode(
        array_merge($logLineBase, ['status' => 'sent']),
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );
    @file_put_contents($logFile, $logLine . "\n", FILE_APPEND | LOCK_EX);

    // 第三方推送平台要求响应包含 OK/ok/SUCCESS/success，统一返回纯文本 OK
    http_response_code(200);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'OK';
    exit;
}

// 发送失败：记录日志，并落盘到失败队列，便于后续人工/定时重发，避免丢单
$logLine = json_encode(
    array_merge($logLineBase, ['status' => 'failed', 'error' => $lastErr ? $lastErr->getMessage() : 'unknown']),
    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
);
@file_put_contents($logFile, $logLine . "\n", FILE_APPEND | LOCK_EX);

$failDir = $dataDir . '/callbacks/failed';
if (!is_dir($failDir)) {
    @mkdir($failDir, 0777, true);
}
@file_put_contents(
    $failDir . '/' . date('Ymd') . '.jsonl',
    json_encode(
        array_merge($logLineBase, [
            'text'     => $text,
            'bot_id'   => $botId,
            'receiver' => $receiverOpenid,
            'error'    => $lastErr ? $lastErr->getMessage() : 'unknown',
        ]),
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    ) . "\n",
    FILE_APPEND | LOCK_EX
);

http_response_code(502);
echo json_encode(['code' => 502, 'message' => 'send failed', 'error' => $lastErr ? $lastErr->getMessage() : 'unknown']);
