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

// 4.5 先发第一条通知（只有订单信息，没有充值账号）
//     业务场景：拉取充值账号 = 供货商 10 分钟倒计时开始。
//     原流程先拉账号再通知，白白浪费几十秒。
//     改为：立刻发「新订单」通知让你第一时间知道有单 → 后台再拉账号补发第二条。
//     只有启用了自动拉取充值账号时才走两步流程，否则保持原样一次性发完整内容。
$copyEnabled = $orderNo !== null && $orderNo !== '' && (bool)($config['copy_enabled'] ?? false);

// 4.6 自动拉取充值账号：调用供货商 order/copy 接口（获取后订单自动变为处理中 status=2）
//     提取为独立函数，供「先通知后补账号」流程调用
$copyConfig = [
    'url'    => (string)($config['copy_url'] ?? ''),
    'token'  => (string)($config['copy_token'] ?? ''),
    'param'  => (string)($config['copy_param'] ?? 'orderSn'),
    'method' => strtoupper((string)($config['copy_method'] ?? 'POST')),
];
$copyTimeout = 8; // 单次拉取超时（秒），比原来 10 秒收紧，避免拖太久

/**
 * 拉取供货商充值账号
 * @return array{ok:bool, account:string, raw:string}
 */
function fetchRechargeAccount(string $orderNo, array $cfg, int $timeout): array
{
    if ($cfg['url'] === '') {
        return ['ok' => false, 'account' => '', 'raw' => 'url 为空'];
    }
    $sep = strpos($cfg['url'], '?') === false ? '?' : '&';
    $attempts = [
        ['type' => 'body'],
        ['type' => 'header', 'name' => 'token'],
        ['type' => 'header', 'name' => 'Token'],
        ['type' => 'query',  'name' => 'token'],
    ];
    foreach ($attempts as $at) {
        $target  = $cfg['url'];
        $headers = ['Accept: application/json'];
        $body    = '';
        if ($cfg['method'] === 'POST') {
            if ($at['type'] === 'body' && $cfg['token'] !== '') {
                $body = json_encode([$cfg['param'] => $orderNo, 'token' => $cfg['token']], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                $headers[] = 'Content-Type: application/json';
            } elseif ($at['type'] === 'header' && $cfg['token'] !== '') {
                $headers[] = $at['name'] . ': ' . $cfg['token'];
                $body = json_encode([$cfg['param'] => $orderNo], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                $headers[] = 'Content-Type: application/json';
            } else {
                $headers[] = 'Content-Type: application/json';
                $body = json_encode([$cfg['param'] => $orderNo], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                if ($at['type'] === 'query' && $cfg['token'] !== '') {
                    $target .= $sep . http_build_query([$at['name'] => $cfg['token']]);
                }
            }
            $ch = curl_init($target);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => $timeout,
                CURLOPT_CONNECTTIMEOUT => 4,
                CURLOPT_HTTPHEADER     => $headers,
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => $body,
            ]);
        } else {
            $target .= $sep . http_build_query([$cfg['param'] => $orderNo]);
            if ($at['type'] === 'header' && $cfg['token'] !== '') {
                $headers[] = $at['name'] . ': ' . $cfg['token'];
            } elseif ($at['type'] === 'query' && $cfg['token'] !== '') {
                $target .= $sep . http_build_query([$at['name'] => $cfg['token']]);
            }
            $ch = curl_init($target);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => $timeout,
                CURLOPT_CONNECTTIMEOUT => 4,
                CURLOPT_HTTPHEADER     => $headers,
            ]);
        }
        $resp    = curl_exec($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr = curl_error($ch);
        curl_close($ch);
        if ($resp === false || $httpCode >= 400) {
            continue;
        }
        $json = json_decode((string)$resp, true);
        if (is_array($json) && (int)($json['code'] ?? -1) === 0 && !empty($json['data']['recharge_account'])) {
            return ['ok' => true, 'account' => (string)$json['data']['recharge_account'], 'raw' => ''];
        }
    }
    return ['ok' => false, 'account' => '', 'raw' => '所有 Token 尝试均失败'];
}

/**
 * 通过机器人发送 C2C 消息（带重试）
 */
function sendBotMessage(Application $app, string $botId, string $openid, string $text): array
{
    $bot = $app->getBotManager()->getBot($botId);
    if ($bot === null) {
        return ['ok' => false, 'error' => 'bot not found: ' . $botId];
    }
    for ($attempt = 1; $attempt <= 3; $attempt++) {
        try {
            $bot->getClient()->sendC2CMessage($openid, ['content' => $text]);
            return ['ok' => true, 'error' => ''];
        } catch (\Throwable $e) {
            $err = $e->getMessage();
            if ($attempt < 3) {
                usleep(500000);
            }
        }
    }
    return ['ok' => false, 'error' => $err ?? 'unknown'];
}

// 5. 先拉取充值账号（供货商给的拉取时间足够，拉到后 10 分钟倒计时才开始）
$extraLines = '';
$fetchStatus = 'skipped'; // skipped / ok / failed
if ($copyEnabled) {
    $fetchResult = fetchRechargeAccount($orderNo, $copyConfig, $copyTimeout);
    if ($fetchResult['ok']) {
        // 拉到账号的时刻 = 10 分钟倒计时起点
        $deadline = date('H:i', time() + 600);
        $extraLines = "\n充值账号: " . $fetchResult['account']
            . "\n⏰ 截止时间: " . $deadline . "（10分钟内必须充值完成）";
        $fetchStatus = 'ok';
    } else {
        $extraLines = "\n⚠️ 充值账号拉取失败，请尽快手动处理或退款";
        $fetchStatus = 'failed';
    }
}

// 6. 组装完整消息文本（订单信息 + 充值账号 + 截止时间，一条搞定）
$time     = date('Y-m-d H:i:s');
$ip       = (string)($_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '');
$method   = (string)($_SERVER['REQUEST_METHOD'] ?? 'POST');

if (strncmp($content, '来新订单了', 5) === 0) {
    // 订单模板排版：直接输出 + 充值账号 + 截止时间，末尾带时间
    $text = $content . $extraLines . "\n时间: " . $time;
} else {
    // 其他内容：使用后台配置模板
    $template = (string)($config['template'] ?? '{content}');
    $text = str_replace(
        ['{content}', '{source}', '{time}', '{ip}', '{method}'],
        [$content . $extraLines, $source, $time, $ip, $method],
        $template
    );
    $text = str_replace('\\n', "\n", $text);
}

// 7. 记录日志目录
$logDir = $dataDir . '/callbacks';
if (!is_dir($logDir)) {
    @mkdir($logDir, 0777, true);
}
$logFile = $logDir . '/' . date('Ymd') . '.jsonl';
$shortContent = (function_exists('mb_substr') ? mb_substr($content, 0, 500, 'UTF-8') : substr($content, 0, 500));

// 8. 发送消息（带重试）
$app = null;
try {
    $app = new Application(__DIR__ . '/config/bots.php');
    $app->boot();
} catch (\Throwable $e) {
    @file_put_contents(
        $logFile,
        json_encode(['time' => $time, 'ip' => $ip, 'source' => $source, 'method' => $method, 'content' => $shortContent, 'status' => 'failed', 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n",
        FILE_APPEND | LOCK_EX
    );
    @mkdir($dataDir . '/callbacks/failed', 0777, true);
    @file_put_contents(
        $dataDir . '/callbacks/failed/' . date('Ymd') . '.jsonl',
        json_encode(['time' => $time, 'text' => $text, 'bot_id' => $botId, 'receiver' => $receiverOpenid, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n",
        FILE_APPEND | LOCK_EX
    );
    http_response_code(502);
    echo json_encode(['code' => 502, 'message' => 'app init failed', 'error' => $e->getMessage()]);
    exit;
}

$sendResult = sendBotMessage($app, $botId, $receiverOpenid, $text);
$sendOk  = $sendResult['ok'];
$lastErr = $sendResult['error'];

$logLineBase = [
    'time'    => $time,
    'ip'      => $ip,
    'source'  => $source,
    'method'  => $method,
    'content' => $shortContent,
    'fetch'   => $fetchStatus,
];

// 9. 发送成功 → 返回 OK
if ($sendOk) {
    @file_put_contents(
        $logFile,
        json_encode(array_merge($logLineBase, ['status' => 'sent']), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n",
        FILE_APPEND | LOCK_EX
    );
    http_response_code(200);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'OK';
    // 提前关闭 HTTP 连接（拉取已完成，后续无耗时操作，但保持习惯）
    if (function_exists('fastcgi_finish_request')) {
        fastcgi_finish_request();
    }
    exit;
}

// 10. 发送失败 → 落盘失败队列
@file_put_contents(
    $logFile,
    json_encode(array_merge($logLineBase, ['status' => 'failed', 'error' => $lastErr]), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n",
    FILE_APPEND | LOCK_EX
);
@mkdir($dataDir . '/callbacks/failed', 0777, true);
@file_put_contents(
    $dataDir . '/callbacks/failed/' . date('Ymd') . '.jsonl',
    json_encode(array_merge($logLineBase, ['text' => $text, 'bot_id' => $botId, 'receiver' => $receiverOpenid, 'error' => $lastErr]), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n",
    FILE_APPEND | LOCK_EX
);
http_response_code(502);
echo json_encode(['code' => 502, 'message' => 'send failed', 'error' => $lastErr]);

