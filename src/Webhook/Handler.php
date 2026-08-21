<?php

declare(strict_types=1);

namespace QQBot\Webhook;

use QQBot\Api\Client;
use QQBot\Core\EventDispatcher;
use QQBot\Core\Logger;
use QQBot\Events\C2CMessageEvent;
use QQBot\Events\GroupAtMessageEvent;

/**
 * Webhook 事件处理器
 * 负责解析 Payload、分发事件、处理回调验证
 */
class Handler
{
    private Logger $logger;
    private EventDispatcher $dispatcher;
    private Validator $validator;
    private Client $client;
    private string $botSecret;
    private bool $verifySign;

    /** @var array<string, int> 记录每个 msg_id 的 msg_seq，用于去重 */
    private array $msgSeqMap = [];

    /** 是否已通过 fastcgi_finish_request 向平台发送过响应 */
    private bool $responseSent = false;

    public function __construct(
        Logger $logger,
        EventDispatcher $dispatcher,
        Validator $validator,
        Client $client,
        string $botSecret,
        bool $verifySign = true,
    ) {
        $this->logger      = $logger;
        $this->dispatcher  = $dispatcher;
        $this->validator   = $validator;
        $this->client      = $client;
        $this->botSecret   = $botSecret;
        $this->verifySign  = $verifySign;
    }

    public function getDispatcher(): EventDispatcher
    {
        return $this->dispatcher;
    }

    /**
     * 处理 Webhook HTTP 请求
     *
     * @param array  $headers HTTP 请求头（小写键名）
     * @param string $body    HTTP 请求体
     *
     * @return array 响应数组，将被 json_encode 后返回给平台
     */
    public function handle(array $headers, string $body): array
    {
        // 1. 先解析 Payload 获取 op 码
        $payload = json_decode($body, true);
        if (!is_array($payload)) {
            $this->logger->error('Invalid JSON payload');
            http_response_code(400);
            return ['code' => 400, 'message' => 'Invalid JSON'];
        }

        $op = (int) ($payload['op'] ?? 0);
        $t  = $payload['t'] ?? '';
        $d  = $payload['d'] ?? [];

        $this->logger->debug('Webhook received', ['op' => $op, 'type' => $t]);

        // 2. OpCode 13（回调地址验证）没有 Ed25519 签名头，直接处理
        if ($op === 13) {
            return $this->handleValidation($d);
        }

        // 3. 其他情况（如事件推送 OpCode 0）验证 Ed25519 签名
        if ($this->verifySign) {
            $signature = $headers['x-signature-ed25519'] ?? '';
            $timestamp = $headers['x-signature-timestamp'] ?? '';

            if (!$this->validator->validate($this->botSecret, $signature, $timestamp, $body)) {
                $this->logger->error('Webhook signature validation failed');
                http_response_code(401);
                return ['code' => 401, 'message' => 'Invalid signature'];
            }
        }

        // 4. 根据 OpCode 处理
        return match ($op) {
            0  => $this->handleDispatch($t, $d),      // 事件推送
            default => $this->ack(),                   // 其他情况返回 ACK
        };
    }

    /**
     * 是否已向平台发送过响应（fastcgi_finish_request 已提前返回）
     */
    public function isResponseSent(): bool
    {
        return $this->responseSent;
    }

    /**
     * 获取下一个 msg_seq，用于被动消息去重
     */
    private function getNextMsgSeq(string $msgId): int
    {
        if ($msgId === '') {
            return 1;
        }
        if (!isset($this->msgSeqMap[$msgId])) {
            $this->msgSeqMap[$msgId] = 0;
        }
        $this->msgSeqMap[$msgId]++;
        return $this->msgSeqMap[$msgId];
    }

    /**
     * 处理事件推送 (OpCode 0 Dispatch)
     */
    private function handleDispatch(string $eventType, array $data): array
    {
        $msgId = (string)($data['id'] ?? '');

        // ---------- 事件级持久化去重 ----------
        // QQ 平台在 webhook 响应超时（AI 推理耗时较长）时会重推同一事件，
        // PHP 每个请求都是独立进程，必须落盘去重，否则同一条消息会被
        // 重复处理、重复回复（用户会收到多条相同消息）
        if ($msgId !== '') {
            $dedupDir = __DIR__ . '/../../data/dedup';
            if (!is_dir($dedupDir)) {
                @mkdir($dedupDir, 0755, true);
            }
            $marker = $dedupDir . '/' . md5($msgId) . '.marker';

            // 'x' 模式独占创建：并发的重推请求中只有一个能创建成功
            $fh = @fopen($marker, 'x');
            if ($fh === false) {
                $this->logger->info('Duplicate event skipped', ['id' => $msgId]);
                return $this->ack();
            }
            @fwrite($fh, date('c'));
            fclose($fh);

            // 顺带清理 1 小时前的旧标记（每小时最多触发一次）
            $gcFlag = $dedupDir . '/.last_gc';
            if (!is_file($gcFlag) || (time() - (int)@filemtime($gcFlag)) > 3600) {
                @touch($gcFlag);
                foreach ((glob($dedupDir . '/*.marker') ?: []) as $old) {
                    if ((time() - (int)@filemtime($old)) > 3600) {
                        @unlink($old);
                    }
                }
            }
        }

        // ---------- 先向平台返回 ACK，再执行耗时的插件处理 ----------
        // AI 推理可能超过平台 webhook 超时时间，先返回 200 可避免平台重推
        $this->responseSent = true;
        http_response_code(200);
        header('Content-Type: application/json');
        echo '{}';
        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        }

        $nextSeq = $this->getNextMsgSeq($msgId);

        switch ($eventType) {
            case 'C2C_MESSAGE_CREATE':
                $event = new C2CMessageEvent($data, $this->client, $this->logger);
                $this->logger->info('C2C message received', [
                    'user'    => $event->getUserOpenid(),
                    'content' => mb_substr($event->getContent(), 0, 100),
                ]);
                break;

            case 'GROUP_AT_MESSAGE_CREATE':
                $event = new GroupAtMessageEvent($data, $this->client, $this->logger);
                $this->logger->info('Group AT message received', [
                    'group'   => $event->getGroupOpenid(),
                    'member'  => $event->getMemberOpenid(),
                    'content' => mb_substr($event->getContent(), 0, 100),
                ]);
                break;

            default:
                // 不处理其他类型事件（如频道相关）
                $this->logger->debug('Unhandled event type', ['type' => $eventType]);
                return $this->ack();
        }

        // 将 msg_seq 注入事件对象，供 reply 使用
        $event->setNextSeq($nextSeq);

        // 分发事件给插件
        $this->dispatcher->dispatch($event);

        return $this->ack();
    }

    /**
     * 处理回调地址验证 (OpCode 13)
     * 平台会发送 plain_token 和 event_ts，需要返回签名
     */
    private function handleValidation(array $data): array
    {
        $plainToken = $data['plain_token'] ?? '';
        $eventTs    = $data['event_ts'] ?? '';

        $this->logger->info('Handling webhook validation', ['event_ts' => $eventTs]);

        $signature = $this->validator->signValidation($this->botSecret, $eventTs, $plainToken);

        return [
            'plain_token' => $plainToken,
            'signature'   => $signature,
        ];
    }

    /**
     * 返回 HTTP Callback ACK (OpCode 12)
     */
    private function ack(): array
    {
        return [];
    }
}
