<?php
declare(strict_types=1);

namespace QQBot\Plugin;

use QQBot\Core\EventDispatcher;
use QQBot\Core\Logger;
use QQBot\Events\C2CMessageEvent;
use QQBot\Events\GroupAtMessageEvent;
use QQBot\Service\ChatBridge;

/**
 * 消息桥接插件（零侵入）
 *
 * 将所有收到的用户消息存入 ChatBridge，供网页客服查看和回复。
 */
class ChatBridgePlugin implements PluginInterface
{
    private Logger $logger;
    private ?ChatBridge $bridge = null;

    public function getName(): string { return 'chat_bridge'; }
    public function getDisplayName(): string { return '消息桥接'; }
    public function getDescription(): string { return '将收到的消息存入桥接存储，供网页客服系统使用'; }
    public function getVersion(): string { return '1.0.0'; }
    public function getAuthor(): string { return 'QQBot Framework'; }
    public function getIcon(): ?string { return '🌉'; }
    public function getTags(): array { return ['桥接', '网页', '客服', '零侵入']; }

    public function register(EventDispatcher $dispatcher, Logger $logger): void
    {
        $this->logger = $logger;
        try {
            $this->bridge = new ChatBridge();
            $this->logger->info('[ChatBridgePlugin] ChatBridge 初始化成功');
        } catch (\Throwable $e) {
            $this->logger->error('[ChatBridgePlugin] ChatBridge 初始化失败: ' . $e->getMessage());
            return;
        }

        // 单聊消息
        $dispatcher->on(C2CMessageEvent::class, function (C2CMessageEvent $event): void {
            try {
                $this->bridge->saveIncoming(
                    $event->getUserOpenid(),
                    $event->getEventId(),
                    $event->getContent(),
                    'c2c'
                );
                $this->logger->debug('[ChatBridgePlugin] 单聊消息已存储', ['openid' => $event->getUserOpenid()]);
            } catch (\Throwable $e) {
                $this->logger->error('[ChatBridgePlugin] 存储单聊消息失败: ' . $e->getMessage());
            }
        });

        // 群聊 @ 消息（以群为对话单位）
        $dispatcher->on(GroupAtMessageEvent::class, function (GroupAtMessageEvent $event): void {
            try {
                $this->bridge->saveIncoming(
                    $event->getGroupOpenid(),
                    $event->getEventId(),
                    $event->getContent(),
                    'group',
                    $event->getGroupOpenid()
                );
                $this->logger->debug('[ChatBridgePlugin] 群聊消息已存储', ['group' => $event->getGroupOpenid()]);
            } catch (\Throwable $e) {
                $this->logger->error('[ChatBridgePlugin] 存储群聊消息失败: ' . $e->getMessage());
            }
        });
    }

    public function enable(): void
    {
        $this->logger->info('[ChatBridgePlugin] enabled');
    }

    public function disable(): void
    {
        $this->logger->info('[ChatBridgePlugin] disabled');
    }
}