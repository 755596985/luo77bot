---
<?php
declare(strict_types=1);

namespace QQBot\Plugin;

use QQBot\Core\EventDispatcher;
use QQBot\Core\Logger;
use QQBot\Events\C2CMessageEvent;
use QQBot\Events\GroupAtMessageEvent;
use QQBot\Message\MarkdownTemplate;

class OwnerSystemPlugin implements PluginInterface
{
    private Logger $logger;
    private string $dataPath;
    
    /**
     * ä¸»äººQQ OpenIDåè¡¨ï¼æ¯æå¤ä¸»äººï¼
     * æ³¨æï¼QQå®æ¹Bot APIè·åçæ¯OpenIDï¼ä¸æ¯åå§QQå·
     * è¯·éè¿ getUserOpenid() è·ååå¡«å¥
     */
    private array $masterOpenIds = [
        'YOUR_MASTER_OPENID_1',
        'YOUR_MASTER_OPENID_2',
    ];
    
    /**
     * ä¸»äººç³»ç»æ°æ®æä»¶
     */
    private string $ownerDataFile;

    // ===== æä»¶åä¿¡æ¯ =====
    public function getName(): string { return 'owner_system'; }
    public function getDisplayName(): string { return 'ä¸»äººç³»ç»'; }
    public function getDescription(): string { return 'ä¸»äººæéç®¡çç³»ç»ï¼æ¯æå¤ä¸»äººãå¨ææ·»å /ç§»é¤ä¸»äººãä¸»äººä¸å±æä»¤'; }
    public function getVersion(): string { return '1.0.1'; }
    public function getAuthor(): string { return 'AI Assistant'; }
    public function getIcon(): ?string { return 'ð'; }
    public function getTags(): array { return ['æé', 'ç®¡ç', 'ç³»ç»']; }

    // ===== æ³¨åäºä»¶çå¬ =====
    public function register(EventDispatcher $dispatcher, Logger $logger): void
    {
        $this->logger = $logger;
        $this->dataPath = __DIR__ . '/../data';
        $this->ownerDataFile = $this->dataPath . '/owner_system.json';
        
        // åå§åæ°æ®æä»¶
        $this->initDataFile();

        // çå¬åèæ¶æ¯
        $dispatcher->on(C2CMessageEvent::class, function (C2CMessageEvent $event): void {
            $this->handleMessage($event);
        });

        // çå¬ç¾¤è @ æ¶æ¯
        $dispatcher->on(GroupAtMessageEvent::class, function (GroupAtMessageEvent $event): void {
            $this->handleMessage($event);
        });
    }

    public function enable(): void
    {
        $this->logger->info('OwnerSystemPlugin enabled');
    }

    public function disable(): void
    {
        $this->logger->info('OwnerSystemPlugin disabled');
    }

    // ==================== æ ¸å¿é»è¾ ====================

    /**
     * å¤çæ¶æ¯äºä»¶ï¼å¼å®¹ C2C å GroupAtï¼
     */
    private function handleMessage(C2CMessageEvent|GroupAtMessageEvent $event): void
    {
        $content = trim($event->getContent());
        $userId = strtoupper(trim($event->getUserOpenid()));
        
        // å è½½ä¸»äººåè¡¨ï¼ç»ä¸å¤§åï¼
        $masters = $this->getMasters();
        
        // å¤æ­æ¯å¦æ¯ä¸»äºº
        $isMaster = in_array($userId, $masters, true);
        
        // è®°å½æ¥å¿ï¼è°è¯ç¨ï¼
        $this->logger->info("æ¶å°æ¶æ¯: {$content} | ç¨æ·: {$userId} | ä¸»äºº: " . ($isMaster ? 'æ¯' : 'å¦'));
        $this->logger->info("å½åä¸»äººåè¡¨: " . json_encode($masters));

        // ========== å¬å±æä»¤ï¼ä»»ä½äººå¯ç¨ï¼ ==========
        
        // è·åèªå·±çOpenIDï¼æ¹ä¾¿éç½®ä¸»äººï¼
        if ($content === 'æçID') {
            $event->replyText("ä½ ç OpenID æ¯ï¼\n`{$userId}`\n\nå°æ­¤IDåç»æºå¨äººä¸»äººï¼å¯æ·»å ä¸ºä¸´æ¶ç®¡çåã");
            return;
        }

        // ========== ä¸»äººä¸å±æä»¤ ==========
        if (!$isMaster) {
            // éä¸»äººå°è¯æ§è¡ä¸»äººæä»¤æ¶çæç¤º
            if (str_starts_with($content, 'ä¸»äºº') || str_starts_with($content, 'ç³»ç»')) {
                $event->replyText('â ä½ æ²¡ææéæ§è¡æ­¤æä½ï¼è¯¥æä»¤ä»ä¸»äººå¯ç¨ã');
                $event->stopPropagation();
            }
            return;
        }

        // ä¸»äººæä»¤åå
        match (true) {
            $content === 'ä¸»äººå¸®å©' => $this->showOwnerHelp($event),
            $content === 'ä¸»äººåè¡¨' => $this->showMasterList($event, $masters),
            $content === 'ç³»ç»ç¶æ' => $this->showSystemStatus($event),
            
            str_starts_with($content, 'ä¸»äººæ·»å  ') => $this->addMaster($event, $content, $masters),
            str_starts_with($content, 'ä¸»äººç§»é¤ ') => $this->removeMaster($event, $content, $masters),
            
            str_starts_with($content, 'ä¸»äººå¹¿æ­ ') => $this->broadcastMessage($event, $content),
            str_starts_with($content, 'ä¸»äººç¦è¨ ') => $this->muteUser($event, $content),
            str_starts_with($content, 'ä¸»äººè§£ç¦ ') => $this->unmuteUser($event, $content),
            
            str_starts_with($content, 'ä¸»äººæµç§° ') => $this->setBotNickname($event, $content),
            str_starts_with($content, 'ä¸»äººæ§è¡ ') => $this->executeCommand($event, $content),
            
            $content === 'ä¸»äººéå¯' => $this->restartBot($event),
            $content === 'ä¸»äººæ¸çæ¥å¿' => $this->cleanLogs($event),
            
            default => null,
        };

        // å¦ææ¯ä¸»äººæä»¤ï¼é»æ­¢åç»­æä»¶å¤ç
        if (str_starts_with($content, 'ä¸»äºº') || str_starts_with($content, 'ç³»ç»')) {
            $event->stopPropagation();
        }
    }

    // ==================== ä¸»äººç®¡ç ====================

    /**
     * æ¾ç¤ºä¸»äººå¸®å©èå
     */
    private function showOwnerHelp(C2CMessageEvent|GroupAtMessageEvent $event): void
    {
        $help = <<<MD
**ð ä¸»äººç³»ç»å¸®å©èå**

**ä¸»äººç®¡ç**
- `ä¸»äººåè¡¨` â æ¥çææä¸»äºº
- `ä¸»äººæ·»å  <OpenID>` â æ·»å æ°ä¸»äºº
- `ä¸»äººç§»é¤ <OpenID>` â ç§»é¤ä¸»äººï¼ä¸è½ç§»é¤èªå·±ï¼

**ç³»ç»ä¿¡æ¯**
- `ç³»ç»ç¶æ` â æ¥çæ¡æ¶è¿è¡ç¶æ
- `ä¸»äººéå¯` â éå¯æ¡æ¶ï¼è°¨æä½¿ç¨ï¼
- `ä¸»äººæ¸çæ¥å¿` â æ¸çè¿ææ¥å¿æä»¶

**æ¶æ¯ç®¡ç**
- `ä¸»äººå¹¿æ­ <åå®¹>` â åææç¾¤åéå¹¿æ­ï¼ç¾¤èä¸­å¯ç¨ï¼

**æºå¨äººè®¾ç½®**
- `ä¸»äººæµç§° <æ°æµç§°>` â ä¿®æ¹æºå¨äººæµç§°

**è°è¯å·¥å·**
- `ä¸»äººæ§è¡ <å½ä»¤>` â æ§è¡ç³»ç»å½ä»¤ï¼å±é©ï¼ï¼
- `æçID` â è·åèªå·±çOpenIDï¼ä»»ä½äººå¯ç¨ï¼

â ï¸ æ³¨æï¼OpenID æ¯ QQ å®æ¹ Bot API çç¨æ·æ è¯ï¼ä¸æ¯åå§QQå·ã
MD;
        $event->replyMarkdown($help);
    }

    /**
     * æ¾ç¤ºä¸»äººåè¡¨
     */
    private function showMasterList(C2CMessageEvent|GroupAtMessageEvent $event, array $masters): void
    {
        if (empty($masters)) {
            $event->replyText('å½åæ²¡æéç½®ä»»ä½ä¸»äººã');
            return;
        }

        $list = "**ð ä¸»äººåè¡¨**\n\n";
        foreach ($masters as $index => $masterId) {
            $shortId = substr($masterId, 0, 8) . '...' . substr($masterId, -4);
            $list .= ($index + 1) . ". `{$shortId}`\n";
        }
        $list .= "\nå± " . count($masters) . " ä½ä¸»äºº";
        
        $event->replyMarkdown($list);
    }

    /**
     * æ·»å æ°ä¸»äºº
     */
    private function addMaster(C2CMessageEvent|GroupAtMessageEvent $event, string $content, array $masters): void
    {
        $newMasterId = strtoupper(trim(substr($content, 6))); // å»æ "ä¸»äººæ·»å  " å¹¶è½¬å¤§å
        
        if (empty($newMasterId) || strlen($newMasterId) < 10) {
            $event->replyText('â æ æç OpenIDï¼è¯·æä¾å®æ´çç¨æ· OpenIDã');
            return;
        }

        if (in_array($newMasterId, $masters, true)) {
            $event->replyText('â ï¸ è¯¥ç¨æ·å·²ç»æ¯ä¸»äººäºã');
            return;
        }

        $masters[] = $newMasterId;
        $this->saveMasters($masters);
        
        $shortId = substr($newMasterId, 0, 8) . '...' . substr($newMasterId, -4);
        $event->replyText("â å·²æåæ·»å ä¸»äººï¼`{$shortId}`");
        $this->logger->info("ä¸»äººæ·»å æå: {$newMasterId}");
    }

    /**
     * ç§»é¤ä¸»äºº
     */
    private function removeMaster(C2CMessageEvent|GroupAtMessageEvent $event, string $content, array $masters): void
    {
        $removeId = strtoupper(trim(substr($content, 6))); // å»æ "ä¸»äººç§»é¤ " å¹¶è½¬å¤§å
        $currentUserId = strtoupper(trim($event->getUserOpenid()));
        
        if (empty($removeId)) {
            $event->replyText('â è¯·æä¾è¦ç§»é¤ç OpenIDã');
            return;
        }

        // é²æ­¢ç§»é¤èªå·±
        if ($removeId === $currentUserId) {
            $event->replyText('â ä¸è½ç§»é¤èªå·±ï¼');
            return;
        }

        $index = array_search($removeId, $masters, true);
        if ($index === false) {
            $event->replyText('â è¯¥ç¨æ·ä¸å¨ä¸»äººåè¡¨ä¸­ã');
            return;
        }

        array_splice($masters, $index, 1);
        $this->saveMasters($masters);
        
        $shortId = substr($removeId, 0, 8) . '...' . substr($removeId, -4);
        $event->replyText("â å·²æåç§»é¤ä¸»äººï¼`{$shortId}`");
        $this->logger->info("ä¸»äººç§»é¤æå: {$removeId}");
    }

    // ==================== ç³»ç»ç®¡ç ====================

    /**
     * æ¾ç¤ºç³»ç»ç¶æ
     */
    private function showSystemStatus(C2CMessageEvent|GroupAtMessageEvent $event): void
    {
        $phpVersion = PHP_VERSION;
        $memoryUsage = round(memory_get_usage(true) / 1024 / 1024, 2);
        $memoryPeak = round(memory_get_peak_usage(true) / 1024 / 1024, 2);
        $uptime = $this->getUptime();
        
        // ç»è®¡æä»¶æ°é
        $pluginPath = __DIR__ . '/../plugins';
        $pluginCount = count(glob($pluginPath . '/*Plugin.php'));
        
        // ç»è®¡æ¥å¿å¤§å°
        $logPath = __DIR__ . '/../logs';
        $logSize = $this->getDirSize($logPath);
        
        $status = <<<MD
**ð ç³»ç»ç¶æ**

| é¡¹ç® | ç¶æ |
|------|------|
| PHPçæ¬ | {$phpVersion} |
| åå­å ç¨ | {$memoryUsage} MB / å³°å¼ {$memoryPeak} MB |
| è¿è¡æ¶é´ | {$uptime} |
| å·²å è½½æä»¶ | {$pluginCount} ä¸ª |
| æ¥å¿å¤§å° | {$logSize} |
| ä¸»äººæ°é | {$this->getMasterCount()} ä½ |

ç³»ç»è¿è¡æ­£å¸¸ â
MD;
        
        $event->replyMarkdown($status);
    }

    /**
     * å¹¿æ­æ¶æ¯ï¼ä»ç¾¤èä¸­å¯ç¨ï¼
     */
    private function broadcastMessage(C2CMessageEvent|GroupAtMessageEvent $event, string $content): void
    {
        if ($event instanceof C2CMessageEvent) {
            $event->replyText('â å¹¿æ­åè½åªè½å¨ç¾¤èä¸­ä½¿ç¨ã');
            return;
        }

        $message = trim(substr($content, 6)); // å»æ "ä¸»äººå¹¿æ­ "
        
        if (empty($message)) {
            $event->replyText('â å¹¿æ­åå®¹ä¸è½ä¸ºç©ºã');
            return;
        }

        // æ³¨æï¼QQ Bot API ä¸æ¯æç´æ¥è·åç¾¤åè¡¨è¿è¡å¹¿æ­
        // è¿éä»ä½ä¸ºç¤ºä¾ï¼å®éå®ç°éè¦ç»´æ¤ç¾¤åè¡¨
        $event->replyText("ð¢ å¹¿æ­åå®¹å·²è®°å½ï¼\n{$message}\n\nï¼æ³¨ï¼QQå®æ¹Bot APIä¸æ¯æä¸»å¨è·åç¾¤åè¡¨ï¼éèªè¡ç»´æ¤ç¾¤IDæ°æ®åºï¼");
        $this->logger->info("ä¸»äººå¹¿æ­: {$message}");
    }

    /**
     * è®¾ç½®æºå¨äººæµç§°ï¼ééåBot APIå®ç°ï¼
     */
    private function setBotNickname(C2CMessageEvent|GroupAtMessageEvent $event, string $content): void
    {
        $nickname = trim(substr($content, 6)); // å»æ "ä¸»äººæµç§° "
        
        if (empty($nickname) || mb_strlen($nickname) > 20) {
            $event->replyText('â æµç§°ä¸è½ä¸ºç©ºä¸ä¸è½è¶è¿20ä¸ªå­ç¬¦ã');
            return;
        }

        // æ³¨æï¼QQå®æ¹Bot APIç®åä¸æ¯æä¿®æ¹æºå¨äººæµç§°
        // è¿éä»è®°å½å°æ¬å°éç½®
        $this->saveConfig('bot_nickname', $nickname);
        $event->replyText("â æºå¨äººæµç§°å·²è®¾ç½®ä¸ºï¼{$nickname}\nï¼æ³¨ï¼QQå®æ¹APIæä¸æ¯æä¿®æ¹æµç§°ï¼ä»æ¬å°è®°å½ï¼");
    }

    /**
     * æ§è¡ç³»ç»å½ä»¤ï¼å±é©æä½ï¼ï¼
     */
    private function executeCommand(C2CMessageEvent|GroupAtMessageEvent $event, string $content): void
    {
        $command = trim(substr($content, 6)); // å»æ "ä¸»äººæ§è¡ "
        
        if (empty($command)) {
            $event->replyText('â å½ä»¤ä¸è½ä¸ºç©ºã');
            return;
        }

        // å®å¨éå¶ï¼ç¦æ­¢æ§è¡å±é©å½ä»¤
        $dangerous = ['rm -rf', 'dd', 'mkfs', 'format', 'del /f', 'rd /s'];
        foreach ($dangerous as $danger) {
            if (str_contains(strtolower($command), $danger)) {
                $event->replyText('ð« æ£æµå°å±é©å½ä»¤ï¼å·²é»æ­¢æ§è¡ï¼');
                $this->logger->warning("ä¸»äººå°è¯æ§è¡å±é©å½ä»¤: {$command}");
                return;
            }
        }

        // æ§è¡å½ä»¤
        exec($command . ' 2>&1', $output, $returnCode);
        $result = implode("\n", $output);
        
        if (empty($result)) {
            $result = 'ï¼å½ä»¤æ§è¡å®æï¼æ è¾åºï¼';
        }

        // æªæ­è¿é¿è¾åº
        if (mb_strlen($result) > 800) {
            $result = mb_substr($result, 0, 800) . "\n...ï¼è¾åºå·²æªæ­ï¼";
        }

        $event->replyMarkdown("**æ§è¡ç»æ**\n```\n{$result}\n```\nè¿åç : {$returnCode}");
        $this->logger->info("ä¸»äººæ§è¡å½ä»¤: {$command}");
    }

    /**
     * éå¯æ¡æ¶ï¼éè¿touchæä»¶è§¦åï¼
     */
    private function restartBot(C2CMessageEvent|GroupAtMessageEvent $event): void
    {
        $restartFile = $this->dataPath . '/restart.flag';
        file_put_contents($restartFile, date('Y-m-d H:i:s'));
        
        $event->replyText('ð éå¯æä»¤å·²åéï¼æ¡æ¶å°å¨ä¸æ¬¡è¯·æ±æ¶éæ°åå§å...');
        $this->logger->info('ä¸»äººè§¦åæ¡æ¶éå¯');
    }

    /**
     * æ¸çæ¥å¿æä»¶
     */
    private function cleanLogs(C2CMessageEvent|GroupAtMessageEvent $event): void
    {
        $logPath = __DIR__ . '/../logs';
        $deleted = 0;
        $saved = 0;
        $today = date('Y-m-d');

        if (!is_dir($logPath)) {
            $event->replyText('æ¥å¿ç®å½ä¸å­å¨ã');
            return;
        }

        $files = glob($logPath . '/*.log');
        foreach ($files as $file) {
            $filename = basename($file);
            // ä¿çä»å¤©åæ¨å¤©çæ¥å¿
            if (str_contains($filename, $today) || str_contains($filename, date('Y-m-d', strtotime('-1 day')))) {
                $saved++;
                continue;
            }
            
            unlink($file);
            $deleted++;
        }

        $event->replyText("â æ¥å¿æ¸çå®æ\nå é¤: {$deleted} ä¸ªæä»¶\nä¿ç: {$saved} ä¸ªæä»¶ï¼ä»å¤©/æ¨å¤©ï¼");
        $this->logger->info("ä¸»äººæ¸çæ¥å¿: å é¤{$deleted}ä¸ªï¼ä¿ç{$saved}ä¸ª");
    }

    // ==================== ç¾¤ç®¡åè½ï¼å ä½ï¼ ====================

    /**
     * ç¦è¨ç¨æ·ï¼éè¦ç¾¤ç®¡æéåç¸åºAPIæéï¼
     */
    private function muteUser(C2CMessageEvent|GroupAtMessageEvent $event, string $content): void
    {
        // QQå®æ¹Bot API v2 æä¸æ¯æç´æ¥ç¦è¨
        $event->replyText('â ï¸ QQå®æ¹Bot API v2 æä¸æ¯æç¦è¨æä½ï¼éç³è¯·ç¸åºæéã');
    }

    /**
     * è§£é¤ç¦è¨
     */
    private function unmuteUser(C2CMessageEvent|GroupAtMessageEvent $event, string $content): void
    {
        $event->replyText('â ï¸ QQå®æ¹Bot API v2 æä¸æ¯æè§£ç¦æä½ï¼éç³è¯·ç¸åºæéã');
    }

    // ==================== æ°æ®æä¹å ====================

    /**
     * åå§åæ°æ®æä»¶
     */
    private function initDataFile(): void
    {
        if (!is_dir($this->dataPath)) {
            mkdir($this->dataPath, 0755, true);
        }

        if (!file_exists($this->ownerDataFile)) {
            // åå§åæ¶å°é»è®¤ä¸»äººç»ä¸è½¬å¤§å
            $defaultMasters = array_map('strtoupper', $this->masterOpenIds);
            $data = [
                'masters' => $defaultMasters,
                'config' => [],
                'created_at' => date('Y-m-d H:i:s'),
            ];
            file_put_contents($this->ownerDataFile, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }
    }

    /**
     * è·åä¸»äººåè¡¨ï¼ç»ä¸å¤§åï¼
     */
    private function getMasters(): array
    {
        if (!file_exists($this->ownerDataFile)) {
            return array_map('strtoupper', $this->masterOpenIds);
        }

        $data = json_decode(file_get_contents($this->ownerDataFile), true);
        $masters = $data['masters'] ?? $this->masterOpenIds;
        return array_map('strtoupper', $masters);
    }

    /**
     * ä¿å­ä¸»äººåè¡¨
     */
    private function saveMasters(array $masters): void
    {
        $data = json_decode(file_get_contents($this->ownerDataFile), true);
        $data['masters'] = $masters;
        $data['updated_at'] = date('Y-m-d H:i:s');
        file_put_contents($this->ownerDataFile, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    /**
     * ä¿å­éç½®é¡¹
     */
    private function saveConfig(string $key, mixed $value): void
    {
        $data = json_decode(file_get_contents($this->ownerDataFile), true);
        $data['config'][$key] = $value;
        file_put_contents($this->ownerDataFile, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    /**
     * è·åä¸»äººæ°é
     */
    private function getMasterCount(): int
    {
        return count($this->getMasters());
    }

    // ==================== å·¥å·æ¹æ³ ====================

    /**
     * è·åè¿è¡æ¶é´
     */
    private function getUptime(): string
    {
        if (function_exists('sys_getloadavg')) {
            $uptime = @file_get_contents('/proc/uptime');
            if ($uptime !== false) {
                $seconds = (int) floatval(explode(' ', $uptime)[0]);
                $days = floor($seconds / 86400);
                $hours = floor(($seconds % 86400) / 3600);
                $minutes = floor(($seconds % 3600) / 60);
                return "{$days}å¤© {$hours}å°æ¶ {$minutes}åé";
            }
        }
        return 'æªç¥';
    }

    /**
     * è·åç®å½å¤§å°
     */
    private function getDirSize(string $path): string
    {
        if (!is_dir($path)) {
            return '0 B';
        }

        $size = 0;
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path)) as $file) {
            $size += $file->getSize();
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $unitIndex = 0;
        while ($size > 1024 && $unitIndex < count($units) - 1) {
            $size /= 1024;
            $unitIndex++;
        }

        return round($size, 2) . ' ' . $units[$unitIndex];
    }
}