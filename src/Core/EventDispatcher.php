<?php

declare(strict_types=1);

namespace QQBot\Core;

/**
 * 事件分发器
 * 支持监听与触发事件
 */
class EventDispatcher
{
    /** @var array<string, array<callable>> */
    private array $listeners = [];

    /** @var Logger|null 可选的日志记录器，未注入时回退到 error_log */
    private ?Logger $logger = null;

    public function setLogger(Logger $logger): void
    {
        $this->logger = $logger;
    }

    /**
     * 注册事件监听器
     *
     * @param string   $eventName 事件类名或标识符
     * @param callable $listener  监听器回调
     * @param int      $priority  优先级，数字越大越先执行
     */
    public function on(string $eventName, callable $listener, int $priority = 0): void
    {
        $this->listeners[$eventName][] = ['listener' => $listener, 'priority' => $priority];
        // 按优先级降序排列
        usort($this->listeners[$eventName], fn(array $a, array $b): int => $b['priority'] <=> $a['priority']);
    }

    /**
     * 分发事件
     *
     * @param object $event 事件对象
     */
    public function dispatch(object $event): void
    {
        $eventName = get_class($event);

        if (empty($this->listeners[$eventName])) {
            return;
        }

        foreach ($this->listeners[$eventName] as $item) {
            try {
                $item['listener']($event);
            } catch (\Throwable $e) {
                // 单个插件异常隔离：记录日志后继续，避免影响其余插件与整体 webhook 响应
                $msg = '插件处理事件 ' . $eventName . ' 时抛出异常: ' . $e->getMessage();
                if ($this->logger !== null) {
                    $this->logger->error($msg, ['file' => $e->getFile(), 'line' => $e->getLine()]);
                } else {
                    error_log('[EventDispatcher] ' . $msg . ' @ ' . $e->getFile() . ':' . $e->getLine());
                }
                // 注意：不走 continue，统一走下方的传播检查。
                // 插件抛异常前若已标记停止传播（如回复被平台 msg_seq 去重拒绝），
                // 也应中断分发，避免后续插件再次回复造成用户收到多条消息
            }

            // 如果事件设置了停止传播，则中断（无论插件正常返回还是抛出异常）
            if (method_exists($event, 'isPropagationStopped') && $event->isPropagationStopped()) {
                break;
            }
        }
    }
}
