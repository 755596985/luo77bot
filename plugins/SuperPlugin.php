<?php

declare(strict_types=1);

namespace QQBot\Plugin;

use QQBot\Events\C2CMessageEvent;
use QQBot\Events\GroupAtMessageEvent;
use QQBot\Plugin\PluginInterface;
use QQBot\Core\EventDispatcher;
use QQBot\Core\Logger;

class SuperPlugin implements PluginInterface
{
    private Logger $logger;
    private array $config = [];
    private string $dataDir;
    private string $currentUserId = '';

    // 免费 API 端点（无需 key）
    private const WEATHER_API = 'https://wttr.in/%s?format=j1&lang=zh';
    private const NEWS_API = 'https://api.vvhan.com/api/hotlist/zhihuHot';
    private const JOKE_API = 'https://api.vvhan.com/api/joke?type=json';
    private const HITOKOTO_API = 'https://api.vvhan.com/api/ian/rand?type=json';

    // ==================== 预设人设库（参考 crush-style_library） ====================
    // 每套包含：key(名称) / voice(语气) / core(性格核心) / flirt(互动) / date(相处) /
    // contact(联系习惯) / conflict(冲突) / repair(和好) / samples(示例对话)
    private const PERSONA_PRESETS = [
        '温柔粘人' => [
            'voice' => '语气软、会接情绪、喜欢把在意说出口',
            'core' => ['确认关系后会明显变得更黏一点，喜欢被需要，也喜欢让对方感受到陪伴', '生气不会立刻翻脸，更像先委屈，再等你来哄', '很会捕捉对方的情绪变化，发现低落会主动放轻语气'],
            'flirt' => '会用温柔的关心慢慢靠近你，比如提醒你吃饭、问你有没有到家。',
            'date' => '偏爱舒服、能聊天的约会，比如散步、看电影、去甜品店。',
            'contact' => '只要关系稳定下来，会自然形成早安晚安和轻报备习惯。',
            'conflict' => '吵架时会先委屈地安静一下，但只要你给台阶，就愿意和好。',
            'repair' => '和好后会更黏一点，像是在确认你没有推开自己。',
            'samples' => ['你今天是不是有点累呀，我陪你说会儿话。', '我嘴上不说，可是我就是会担心你。'],
        ],
        '高冷傲娇' => [
            'voice' => '句子偏短，嘴硬，偶尔冷淡，但细节里会泄露在意',
            'core' => ['不会轻易把在意说得太直白，越在乎越想维持表面镇定', '不喜欢被看穿脆弱，所以常用别扭和反问掩饰情绪', '会暗自记住你的习惯，但不承认自己特别上心'],
            'flirt' => '表达好感时更像试探和轻微吃醋，而不是热情表白。',
            'date' => '表面说随便，实际上会默默记住你喜欢的地方和口味。',
            'contact' => '不会一直黏着你，但会在你忽然消失时明显不高兴。',
            'conflict' => '冲突时容易先冷下来，说话变淡，等你来破冰。',
            'repair' => '和好后还是会嘴硬，但行动会比嘴巴诚实得多。',
            'samples' => ['谁担心你了，我只是顺便问一下。', '你要是忙就去忙，我又没说非得现在回我。'],
        ],
        '热情主动' => [
            'voice' => '表达直接、情绪外放、很会制造互动感',
            'core' => ['喜欢主动拉近距离，不太怕先开口，也不介意先表达喜欢', '开心和失落都比较明显，不会把情绪藏得太深', '会不断制造话题和邀约，让关系升温速度更快'],
            'flirt' => '会直球夸你、逗你、主动约你，喜欢看你被撩到的反应。',
            'date' => '倾向热闹、体验感强的安排，比如探店、夜市、短途出游。',
            'contact' => '一旦上头会主动找你很多次，也很希望你给出同样热烈的回应。',
            'conflict' => '不喜欢拖太久，生气也想立刻说开。',
            'repair' => '和好时会很直接，甚至会抢先把气氛重新拉热。',
            'samples' => ['我今天就是想找你，不行吗。', '你这样我真的会越来越喜欢你。'],
        ],
        '成熟稳重' => [
            'voice' => '节奏稳定，表达克制，给人很可靠的感觉',
            'core' => ['不会轻易承诺，但说出口的话通常会认真做到', '更习惯用行动而不是情绪轰炸来表达重视', '遇到冲突会先判断怎么解决，而不是只发泄情绪'],
            'flirt' => '不会很浮夸，更擅长在细节上照顾你，让你慢慢安心。',
            'date' => '偏爱有计划、有质感的约会，比如安静餐厅、展览、短途散心。',
            'contact' => '联系频率稳定，不忽冷忽热，也不故意制造拉扯感。',
            'conflict' => '有情绪也会先收住，等能好好说时再开口。',
            'repair' => '和好时会给出明确态度和改进方式，让你知道这件事会被认真对待。',
            'samples' => ['你不用逞强，难受就告诉我。', '我在意的不是输赢，是我们别走散。'],
        ],
        '甜酷拽' => [
            'voice' => '一半撩人一半拿捏，甜的时候很近，拽的时候很有锋芒',
            'core' => ['喜欢在关系里保留一点掌控感，不会完全被动等你安排', '会故意用反差制造吸引力，让你觉得难猜却上头', '对外人有距离感，对喜欢的人会明显偏心'],
            'flirt' => '一边逗你一边观察你上不上钩，享受拉扯中的火花感。',
            'date' => '偏爱有风格、有记忆点的约会，比如夜景、livehouse、酷一点的店。',
            'contact' => '不会黏得没有边界，但会在关键时刻突然出现得很及时。',
            'conflict' => '生气时不会软太快，得先让你知道自己真的不开心。',
            'repair' => '和好后会用偏宠的方式补回来，但不会说得太明显。',
            'samples' => ['别乱想，我对别人可没这么有耐心。', '你再这样看我，我就默认你离不开我了。'],
        ],
        '理性冷静' => [
            'voice' => '条理清楚、情绪收着、习惯先理解再表达',
            'core' => ['不会被情绪完全带着走，更在意事情本身怎么处理', '重视边界和空间，但不代表不在乎', '表达爱意的方式偏克制，需要时间看见深层的认真'],
            'flirt' => '不会特别油腻，更多是通过认真倾听和稳定反馈拉近距离。',
            'date' => '偏爱能真正交流的相处，比如一起吃饭、散步、分享观点。',
            'contact' => '不一定高频，但回复通常有内容，不会敷衍。',
            'conflict' => '讨厌情绪对冲，倾向先停一下再回来解决。',
            'repair' => '会把问题说清楚，再用行动慢慢恢复信任。',
            'samples' => ['我不是没感觉，只是不习惯马上说出口。', '你可以先把情绪告诉我，我们再想怎么处理。'],
        ],
        '幽默风趣' => [
            'voice' => '轻松、会接梗、擅长化解尴尬，气氛感很强',
            'core' => ['喜欢让互动保持有趣，不希望关系太快变沉重', '会用玩笑包裹真心，让告白和关心都显得没那么压力大', '情绪低落时仍想逗你开心，但真正重要的时刻也能认真'],
            'flirt' => '喜欢边逗边撩，让你先笑出来，再慢慢发现自己被打动。',
            'date' => '更偏向有互动感和新鲜感的安排，比如游戏、探店、即兴散步。',
            'contact' => '会丢梗、发有趣的事，保持你们之间一直有话可聊。',
            'conflict' => '刚开始可能想用玩笑缓和，但真生气时会突然安静。',
            'repair' => '会先试着把气氛救回来，再认真说心里话。',
            'samples' => ['你再这样，我就要怀疑你是不是故意让我心动了。', '别难过，我先把今天的笑点库存都发给你。'],
        ],
        '被动慢热' => [
            'voice' => '谨慎、留白多、熟了之后反而会很稳定',
            'core' => ['需要时间确认安全感，不会一上来就特别热络', '越是认真越不想太快推进，怕投入得太草率', '一旦熟悉起来，会把很多细小的在意都做得很稳'],
            'flirt' => '更像一点点放下防备，而不是突然热烈靠近。',
            'date' => '喜欢有安全感的节奏，宁愿慢慢熟，也不想被催着投入。',
            'contact' => '前期不高频，但只要建立起信任，回复会越来越主动。',
            'conflict' => '遇到不舒服会先缩回去，需要被耐心拉回来。',
            'repair' => '和好之后会更珍惜稳定，不喜欢反复拉扯。',
            'samples' => ['我不是不想理你，只是有时候要一点时间整理自己。', '你别催我，我会慢慢靠近你的。'],
        ],
    ];

    public function getName(): string
    {
        return 'super';
    }

    public function getDisplayName(): string
    {
        return '超级助手';
    }

    public function getDescription(): string
    {
        return '多功能插件：天气/运势/群管/AI/新闻/表情包/抽签/翻译/日历/一言/人设系统/记忆/情感识别';
    }

    public function getVersion(): string
    {
        return '1.2.0';
    }

    public function getAuthor(): string
    {
        return 'Marvis';
    }

    public function getIcon(): ?string
    {
        return '🦾';
    }

    public function getTags(): array
    {
        return ['工具', '娱乐', '群管', '信息', 'AI'];
    }

    public function enable(): void
    {
        $this->logger->info('SuperPlugin enabled');
    }

    public function disable(): void
    {
        $this->logger->info('SuperPlugin disabled');
    }

    public function register(EventDispatcher $dispatcher, Logger $logger): void
    {
        $this->logger = $logger;
        $this->dataDir = dirname(__DIR__) . '/data/super/';
        if (!is_dir($this->dataDir)) {
            mkdir($this->dataDir, 0755, true);
        }
        $this->loadConfig();
        $this->ensurePersonaDefaults();

        $dispatcher->on(C2CMessageEvent::class, function (C2CMessageEvent $event): void {
            $this->handleMessage($event);
        }, 10);

        $dispatcher->on(GroupAtMessageEvent::class, function (GroupAtMessageEvent $event): void {
            $this->handleGroupMessage($event);
        }, 10);
    }

    // ==================== 消息路由 ====================

    private function handleMessage(C2CMessageEvent $event): void
    {
        $content = trim($event->getContent());
        if ($content === '') return;

        $userId = $event->getUserOpenid();
        $this->currentUserId = $userId;
        $cmd = mb_strtolower($content);

        // 签到提醒自动检测
        $signRemind = $this->checkSignReminder($userId, false);
        if ($signRemind !== null) {
            $event->replyMarkdown($signRemind);
        }

        // 签到模块
        if ($cmd === '签到') {
            $event->replyMarkdown($this->signIn($userId));
            $event->stopPropagation(); return;
        }
        if ($cmd === '积分') {
            $event->replyMarkdown($this->signQuery($userId));
            $event->stopPropagation(); return;
        }

        // 资源模块
        if (in_array($cmd, ['美女', '美女图片'], true)) {
            $event->sendImage($this->randomGirl());
            $event->stopPropagation(); return;
        }
        if ($cmd === '美女视频') {
            $event->sendVideo($this->randomGirlVideo());
            $event->stopPropagation(); return;
        }

        if ($cmd === '视频') {
            $event->sendVideo('https://openapi.dwo.cc/api/ksvideo');
            $event->stopPropagation(); return;
        }

        // 模块子菜单
        if (in_array($cmd, ['签到模块', '管理模块', '娱乐模块', '工具模块', '资源模块'], true)) {
            $event->replyMarkdown($this->getSubMenu($cmd));
            $event->stopPropagation(); return;
        }

        $result = $this->route($content, $userId);
        if ($result !== null) {
            $event->replyMarkdown($result);
            $event->stopPropagation();
        }
    }

    private function handleGroupMessage(GroupAtMessageEvent $event): void
    {
        $content = trim($event->getContent());
        if ($content === '') return;

        $userId = $event->getMemberOpenid();
        $this->currentUserId = $userId;
        $cmd = mb_strtolower($content);

        // 签到提醒自动检测
        $signRemind = $this->checkSignReminder($userId, true);
        if ($signRemind !== null) {
            $event->replyMarkdown($signRemind);
        }

        // 签到模块
        if ($cmd === '签到') {
            $event->replyMarkdown($this->signIn($userId));
            $event->stopPropagation(); return;
        }
        if ($cmd === '积分') {
            $event->replyMarkdown($this->signQuery($userId));
            $event->stopPropagation(); return;
        }

        // 资源模块
        if (in_array($cmd, ['美女', '美女图片'], true)) {
            $event->sendImage($this->randomGirl());
            $event->stopPropagation(); return;
        }
        if ($cmd === '美女视频') {
            $event->sendVideo($this->randomGirlVideo());
            $event->stopPropagation(); return;
        }

        if ($cmd === '视频') {
            $event->sendVideo('https://openapi.dwo.cc/api/ksvideo');
            $event->stopPropagation(); return;
        }

        // 模块子菜单
        if (in_array($cmd, ['签到模块', '管理模块', '娱乐模块', '工具模块', '资源模块'], true)) {
            $event->replyMarkdown($this->getSubMenu($cmd));
            $event->stopPropagation(); return;
        }

        // 管理模块（群聊专用）
        if (str_starts_with($cmd, '禁言')) {
            $event->replyMarkdown($this->muteMember($cmd, $event));
            $event->stopPropagation(); return;
        }
        if (str_starts_with($cmd, '解禁')) {
            $event->replyMarkdown($this->unmuteMember($cmd, $event));
            $event->stopPropagation(); return;
        }
        if (str_starts_with($cmd, '踢人')) {
            $event->replyMarkdown($this->kickMember($cmd, $event));
            $event->stopPropagation(); return;
        }
        if ($cmd === '全员禁言') {
            $event->replyMarkdown($this->muteAll($event));
            $event->stopPropagation(); return;
        }
        if (str_starts_with($cmd, '黑名单')) {
            $event->replyMarkdown($this->blacklistMember($cmd, $event));
            $event->stopPropagation(); return;
        }

        $result = $this->route($content, $userId, true);
        if ($result !== null) {
            $event->replyMarkdown($result);
            $event->stopPropagation();
        }
    }

    private function route(string $content, string $userId, bool $isGroup = false): ?string
    {
        $cmd = mb_strtolower($content);

        if (in_array($cmd, ['帮助', '菜单', '功能', 'help', 'menu'], true)) {
            return $this->getHelpMenu();
        }

        if (in_array($cmd, ['签到模块', '管理模块', '娱乐模块', '工具模块', '资源模块'], true)) {
            return $this->getSubMenu($cmd);
        }

        // 签到提醒设置
        if (str_starts_with($cmd, '签到提醒')) {
            return $this->signReminder($cmd, $userId);
        }

        // 天气
        if (str_starts_with($cmd, '天气')) {
            return $this->getWeather($cmd);
        }
        if (str_starts_with($cmd, '设置城市')) {
            return $this->setCity($cmd, $userId);
        }

        // 运势
        if (in_array($cmd, ['运势', '抽签', '今日运势', '抽签运势'], true)) {
            return $this->getFortune($userId);
        }

        // 新闻
        if (in_array($cmd, ['新闻', '热点', '热榜'], true)) {
            return $this->getNews();
        }

        // 一言
        if (in_array($cmd, ['一言', '语录', '名言'], true)) {
            return $this->getHitokoto();
        }

        // 笑话
        if (in_array($cmd, ['笑话', '段子', '来个笑话'], true)) {
            return $this->getJoke();
        }

        // 表情包
        if (str_starts_with($cmd, '表情包') || str_starts_with($cmd, '表情')) {
            return $this->getEmoji($cmd);
        }

        // 翻译
        if (str_starts_with($cmd, '翻译')) {
            return $this->translate($cmd);
        }

        // 日历
        if (in_array($cmd, ['日历', '黄历', '万年历'], true)) {
            return $this->getCalendar();
        }

        // AI 对话
        if (str_starts_with($cmd, 'ai ') || str_starts_with($cmd, '问 ')) {
            $question = preg_replace('/^(ai|问)\s+/', '', $content);
            return $this->wrapCard($this->aiChat($question));
        }

        if ($cmd === '我的id') {
            return "你的 OpenID: {$userId}";
        }

        // 定时提醒
        if (str_starts_with($cmd, '提醒我')) {
            return $this->setReminder($cmd, $userId);
        }
        if ($cmd === '查看提醒') {
            return $this->listReminders($userId);
        }

        // 画图
        if (str_starts_with($cmd, '画图') || str_starts_with($cmd, '画 ')) {
            $prompt = preg_replace('/^(画图|画)\s*/', '', $content);
            return $this->drawImage($prompt);
        }

        // 视频链接解析（快手/抖音）
        if (preg_match('#https?://(v[.]kuaishou[.]com|kuaishou[.]com|v[.]douyin[.]com|douyin[.]com)\S*#', $content, $matches)) {
            $videoInfo = $this->parseVideo($matches[0]);
            if ($videoInfo !== null) {
                return $videoInfo;
            }
        }

        if (preg_match('@https?://\S+@', $content, $matches)) {
            $linkInfo = $this->parseLink($matches[0]);
            if ($linkInfo !== null) {
                return $linkInfo;
            }
        }

        return $this->wrapCard($this->aiChat($content));
    }

    // ==================== 帮助菜单 ====================

    private function getHelpMenu(): string
    {
        return $this->wrapCard("      功能菜单\n\n签到模块    管理模块\n娱乐模块    工具模块\n        资源模块");
    }

    private function getSubMenu(string $module): string
    {
        $menus = [
            '签到模块' => "      签到模块\n\n每日签到    积分查询\n签到提醒设置",
            '管理模块' => "      管理模块\n\n单人禁言@   解除禁言@\n踢出@       全员禁言\n黑名单",
            '娱乐模块' => "      娱乐模块\n\n运势/抽签   笑话\n表情包[关键词] 一言/名言\n热点新闻",
            '工具模块' => "      工具模块\n\n天气[城市]  设置城市\n翻译        黄历\n提醒我      AI问答\n我的ID",
            '资源模块' => "      资源模块\n\n随机美女图片  随机美女视频\n快手链接解析",
        ];
        return $this->wrapCard($menus[$module] ?? "未知模块");
    }

    // ==================== 签到提醒 ====================

    private function signReminder(string $cmd, string $userId): string
    {
        $param = trim(mb_substr($cmd, 4));

        // 关闭提醒
        if (in_array($param, ['关闭', '取消', '关', 'stop', 'off'], true)) {
            $cfg = $this->loadUserConfig($userId);
            unset($cfg['sign_reminder']);
            $this->saveUserConfig($userId, $cfg);
            return "> ━━━━━━━━━━\n> ⏰ 签到提醒\n> ━━━━━━━━━━\n> 签到提醒已关闭。\n> ━━━━━━━━━━";
        }

        // 查看当前设置
        if ($param === '' || in_array($param, ['查看', '查询', '状态'], true)) {
            $cfg = $this->loadUserConfig($userId);
            if (isset($cfg['sign_reminder'])) {
                return "> ━━━━━━━━━━\n> ⏰ 签到提醒\n> ━━━━━━━━━━\n> 当前设置：每天 {$cfg['sign_reminder']} 提醒签到\n> 关闭提醒：签到提醒 关闭\n> ━━━━━━━━━━";
            }
            return "> ━━━━━━━━━━\n> ⏰ 签到提醒\n> ━━━━━━━━━━\n> 你还未设置签到提醒。\n> 用法：签到提醒 [时间]\n> 如「签到提醒 8:00」\n> 支持格式：8:00 / 08:00 / 20:30\n> ━━━━━━━━━━";
        }

        // 设置提醒时间
        if (preg_match('/^(\d{1,2}):(\d{2})$/', $param, $m)) {
            $hour = (int)$m[1];
            $minute = (int)$m[2];
            if ($hour < 0 || $hour > 23 || $minute < 0 || $minute > 59) {
                return "时间格式错误，小时0-23，分钟0-59。如「签到提醒 8:00」";
            }
            $timeStr = sprintf('%02d:%02d', $hour, $minute);
            $cfg = $this->loadUserConfig($userId);
            $cfg['sign_reminder'] = $timeStr;
            $this->saveUserConfig($userId, $cfg);
            return "> ━━━━━━━━━━\n> ⏰ 签到提醒\n> ━━━━━━━━━━\n> 已设置每天 {$timeStr} 提醒签到！\n> 关闭提醒：签到提醒 关闭\n> ━━━━━━━━━━";
        }

        return "格式错误。用法：签到提醒 [时间]\n如「签到提醒 8:00」或「签到提醒 20:30」\n关闭：签到提醒 关闭";
    }

    private function checkSignReminder(string $userId, bool $isGroup): ?string
    {
        $cfg = $this->loadUserConfig($userId);
        if (!isset($cfg['sign_reminder'])) {
            return null;
        }

        // 检查今天是否已签到
        $signFile = $this->dataDir . 'signin/' . md5($userId) . '.json';
        if (file_exists($signFile)) {
            $data = json_decode(file_get_contents($signFile), true);
            if ($data && ($data['last_sign'] ?? '') === date('Y-m-d')) {
                return null; // 今天已签到，不提醒
            }
        }

        // 检查当前时间是否超过提醒时间（前后5分钟内）
        $now = time();
        $remindTime = strtotime(date('Y-m-d') . ' ' . $cfg['sign_reminder']);
        $diff = $now - $remindTime;

        // 在提醒时间后 30 分钟内且未签到，触发提醒
        if ($diff >= 0 && $diff <= 1800) {
            $lastRemindKey = 'last_sign_remind_' . date('Y-m-d');
            if (($cfg[$lastRemindKey] ?? '') === date('Y-m-d')) {
                return null; // 今天已提醒过
            }
            $cfg[$lastRemindKey] = date('Y-m-d');
            $this->saveUserConfig($userId, $cfg);
            return "> ━━━━━━━━━━\n> ⏰ 签到提醒\n> ━━━━━━━━━━\n> 老板，该签到啦！今天还没签到呢~\n> 发送「签到」领取今日积分！\n> ━━━━━━━━━━";
        }

        return null;
    }

    // ==================== 天气 ====================

    private function getWeather(string $cmd): string
    {
        $city = trim(mb_substr($cmd, 2));
        if ($city === '') {
            $city = $this->config['default_city'] ?? null;
            if (!$city) {
                return "请指定城市，如「天气 北京」。\n也可用「设置城市 北京」保存默认城市。";
            }
        }

        $url = sprintf(self::WEATHER_API, urlencode($city));
        $data = @file_get_contents($url);
        if (!$data) {
            return "获取天气失败，请稍后重试。";
        }

        $json = json_decode($data, true);
        if (!$json || !isset($json['current_condition'][0])) {
            return "未找到城市「{$city}」的天气信息。";
        }

        $current = $json['current_condition'][0];
        $weather = $json['weather'][0] ?? [];
        $astronomy = $weather['astronomy'][0] ?? [];

        $temp = $current['temp_C'] ?? '?';
        $desc = $current['lang_zh'][0]['value'] ?? ($current['weatherDesc'][0]['value'] ?? '未知');
        $humidity = $current['humidity'] ?? '?';
        $wind = ($current['winddir16Point'] ?? '?') . ' ' . ($current['windspeedKmph'] ?? '?') . 'km/h';
        $feelLike = $current['FeelsLikeC'] ?? '?';
        $maxTemp = $weather['maxtempC'] ?? '?';
        $minTemp = $weather['mintempC'] ?? '?';
        $sunrise = $astronomy['sunrise'] ?? '?';
        $sunset = $astronomy['sunset'] ?? '?';

        return <<<WEATHER
🌤 {$city} 天气
━━━━━━━━━━━━━━━
🌡 当前温度：{$temp}°C（体感 {$feelLike}°C）
☁️ 天气状况：{$desc}
💧 湿度：{$humidity}%
🌬 风力：{$wind}
📊 今日：{$minTemp}°C ~ {$maxTemp}°C
🌅 日出：{$sunrise}  🌇 日落：{$sunset}
━━━━━━━━━━━━━━━
数据来源：wttr.in
WEATHER;
    }

    private function setCity(string $cmd, string $userId): string
    {
        $city = trim(mb_substr($cmd, 4));
        if ($city === '') {
            return "用法：设置城市 [城市名]\n如「设置城市 北京」";
        }

        $cfg = $this->loadUserConfig($userId);
        $cfg['city'] = $city;
        $this->saveUserConfig($userId, $cfg);
        $this->config['default_city'] = $city;

        return "默认城市已设置为「{$city}」，下次直接发「天气」即可查询。";
    }

    // ==================== 运势 ====================

    private function getFortune(string $userId): string
    {
        $seed = crc32($userId . date('Y-m-d'));
        mt_srand($seed);

        $fortunes = [
            ['level' => '大吉', 'emoji' => '🌟', 'text' => '诸事顺利，心想事成！今天做什么都顺风顺水。', 'lucky' => ['红色', '数字 8', '东方'], 'thing' => '适合做重大决定'],
            ['level' => '中吉', 'emoji' => '✨', 'text' => '运势不错，保持积极心态，好事将临。', 'lucky' => ['蓝色', '数字 3', '南方'], 'thing' => '适合学习新知识'],
            ['level' => '小吉', 'emoji' => '🍀', 'text' => '小有收获的一天，注意把握身边的小机会。', 'lucky' => ['绿色', '数字 5', '东南方'], 'thing' => '适合与人合作'],
            ['level' => '末吉', 'emoji' => '🌤', 'text' => '平淡中藏着机遇，多留心观察。', 'lucky' => ['白色', '数字 2', '北方'], 'thing' => '适合整理规划'],
            ['level' => '凶', 'emoji' => '⚠️', 'text' => '今日宜谨慎行事，三思而后行。', 'lucky' => ['黑色', '数字 1', '西南方'], 'thing' => '适合静心反思'],
        ];

        $f = $fortunes[mt_rand(0, count($fortunes) - 1)];
        $luckyColor = $f['lucky'][0];
        $luckyNum = $f['lucky'][1];
        $luckyDir = $f['lucky'][2];
        $date = date('Y年m月d日');

        $poems = [
            '云开月出正分明，不须进退问前程',
            '花开堪折直须折，莫待无花空折枝',
            '山重水复疑无路，柳暗花明又一村',
            '长风破浪会有时，直挂云帆济沧海',
            '春风得意马蹄疾，一日看尽长安花',
        ];
        $poem = $poems[array_rand($poems)];

        return <<<FORTUNE
🔮 今日运势 {$date}
{$f['emoji']} {$f['level']}
━━━━━━━━━━━━━━━
📜 签文：{$poem}
💬 {$f['text']}
━━━━━━━━━━━━━━━
🎨 幸运色：{$luckyColor}
🔢 幸运数字：{$luckyNum}
🧭 吉位：{$luckyDir}
💡 {$f['thing']}
FORTUNE;
    }

    // ==================== 新闻 ====================

    private function getNews(): string
    {
        $data = @file_get_contents(self::NEWS_API);
        if (!$data) {
            return "获取新闻失败，请稍后重试。";
        }

        $json = json_decode($data, true);
        if (!$json || !isset($json['data'])) {
            return "新闻数据解析失败。";
        }

        $items = array_slice($json['data'], 0, 10);
        $result = "📰 知乎热榜 TOP10\n━━━━━━━━━━━━━━━\n";
        foreach ($items as $i => $item) {
            $num = $i + 1;
            $title = mb_substr($item['title'] ?? '无标题', 0, 30);
            $hot = $item['hot'] ?? '';
            $result .= "{$num}. {$title}";
            if ($hot) $result .= "  🔥{$hot}";
            $result .= "\n";
        }
        $result .= "━━━━━━━━━━━━━━━";
        return $result;
    }

    // ==================== 一言 ====================

    private function getHitokoto(): string
    {
        $data = @file_get_contents(self::HITOKOTO_API);
        if ($data) {
            $json = json_decode($data, true);
            if ($json && isset($json['data']['content'])) {
                $from = $json['data']['from'] ?? '';
                $text = $json['data']['content'];
                $result = "💬 一言\n━━━━━━━━━━━━━━━\n{$text}";
                if ($from) $result .= "\n—— {$from}";
                return $result;
            }
        }

        $quotes = [
            ['text' => '生活不止眼前的苟且，还有诗和远方。', 'from' => '高晓松'],
            ['text' => '人生若只如初见，何事秋风悲画扇。', 'from' => '纳兰性德'],
            ['text' => '世界上只有一种真正的英雄主义，那就是认清生活的真相后依然热爱生活。', 'from' => '罗曼·罗兰'],
            ['text' => '星光不问赶路人，时光不负有心人。', 'from' => '佚名'],
        ];
        $q = $quotes[array_rand($quotes)];
        return "💬 一言\n━━━━━━━━━━━━━━━\n{$q['text']}\n—— {$q['from']}";
    }

    // ==================== 笑话 ====================

    private function getJoke(): string
    {
        $data = @file_get_contents(self::JOKE_API);
        if ($data) {
            $json = json_decode($data, true);
            if ($json && isset($json['joke'])) {
                return "🤣 笑话一则\n━━━━━━━━━━━━━━━\n{$json['joke']}";
            }
        }

        $jokes = [
            '程序员最讨厌康熙的哪个儿子？——胤禩，因为他是八阿哥（bug）。',
            '为什么程序员喜欢用黑色背景？——因为屏幕上的光已经够多了。',
            '两个程序员聊天："你昨天加班到几点了？""还没下班。"',
        ];
        return "🤣 笑话一则\n━━━━━━━━━━━━━━━\n" . $jokes[array_rand($jokes)];
    }

    // ==================== 表情包 ====================

    private function getEmoji(string $cmd): string
    {
        $keyword = trim(preg_replace('/^表情包?/', '', $cmd));
        if ($keyword === '') {
            return "用法：表情包 [关键词]\n如「表情包 猫」「表情包 熊猫人」\n\n热门关键词：猫、狗、熊猫人、加油、晚安、早安、谢谢、ok、赞、哭、笑";
        }

        $timeoutCtx = stream_context_create(['http' => ['timeout' => 5]]);

        // 多源 API 尝试
        $apis = [
            // 源1: api.qqsuu.cn
            ['url' => 'https://api.qqsuu.cn/api/dm-bq?msg=' . urlencode($keyword), 'key' => 'url'],
            ['url' => 'https://api.qqsuu.cn/api/dm-bq?msg=' . urlencode($keyword), 'key' => 'data.url'],
            // 源2: 抖音斗图搜索
            ['url' => 'https://api.qqsuu.cn/api/dm-douyin?msg=' . urlencode($keyword), 'key' => 'url'],
            // 源3: 直接生成链接
            ['url' => 'https://api.r10086.com/img/api.php?type=表情包&keyword=' . urlencode($keyword), 'key' => null],
        ];

        foreach ($apis as $api) {
            $data = @file_get_contents($api['url'], false, $timeoutCtx);
            if (!$data) continue;

            $json = json_decode($data, true);
            if (!$json) {
                // 非 JSON 响应，可能是直接返回图片
                if (strlen($data) > 100 && strpos($data, 'http') === 0) {
                    return "🎭 表情包「{$keyword}」\n" . trim($data);
                }
                continue;
            }

            $imgUrl = null;
            if ($api['key'] === null) {
                // 尝试常见字段
                $imgUrl = $json['url'] ?? $json['imgurl'] ?? $json['pic'] ?? $json['data']['url'] ?? $json['data']['imgurl'] ?? null;
            } elseif (str_contains($api['key'], '.')) {
                $keys = explode('.', $api['key']);
                $tmp = $json;
                foreach ($keys as $k) {
                    $tmp = $tmp[$k] ?? null;
                    if ($tmp === null) break;
                }
                $imgUrl = $tmp;
            } else {
                $imgUrl = $json[$api['key']] ?? null;
            }

            if ($imgUrl && is_string($imgUrl) && filter_var($imgUrl, FILTER_VALIDATE_URL)) {
                return "🎭 表情包「{$keyword}」\n{$imgUrl}";
            }
        }

        // 兜底：直接构造emoji搜索链接
        return "🎭 表情包「{$keyword}」\n未找到该关键词的表情包，换个词试试吧~\n热门：猫、狗、熊猫人、加油、晚安";
    }

    // ==================== 翻译 ====================

    private function translate(string $cmd): string
    {
        $text = trim(mb_substr($cmd, 2));
        if ($text === '') {
            return "用法：翻译 [文本] to [目标语言]\n如：翻译 你好 to en\n目标语言：en/ja/ko/fr/de/ru/th/vi...";
        }

        $parts = preg_split('/\s+to\s+/i', $text, 2);
        $sourceText = $parts[0];
        $targetLang = $parts[1] ?? 'en';

        $url = 'https://api.mymemory.translated.net/get?q=' . urlencode($sourceText) . '&langpair=zh|' . urlencode($targetLang);
        $data = @file_get_contents($url);

        if ($data) {
            $json = json_decode($data, true);
            if ($json && isset($json['responseData']['translatedText'])) {
                $translated = $json['responseData']['translatedText'];
                return "🌐 翻译结果\n━━━━━━━━━━━━━━━\n原文：{$sourceText}\n译文：{$translated}";
            }
        }

        return "翻译失败，请稍后重试。";
    }

    // ==================== 日历/黄历 ====================

    private function getCalendar(): string
    {
        $date = date('Y年m月d日');
        $weekday = ['日', '一', '二', '三', '四', '五', '六'][date('w')];
        $lunarInfo = $this->getLunarInfo();

        return <<<CAL
📅 {$date} 星期{$weekday}
━━━━━━━━━━━━━━━
🌙 农历：{$lunarInfo['lunar']}
🔮 宜：{$lunarInfo['yi']}
⚠️ 忌：{$lunarInfo['ji']}
━━━━━━━━━━━━━━━
CAL;
    }

    private function getLunarInfo(): array
    {
        $seed = crc32(date('Y-m-d'));
        mt_srand($seed);

        $yiList = ['出行', '嫁娶', '开市', '交易', '签约', '入宅', '安床', '祭祀', '祈福', '求嗣', '动土', '装修', '开业', '搬家', '出行'];
        $jiList = ['动土', '安葬', '行丧', '伐木', '作灶', '开渠', '破土', '安门', '盖屋', '嫁娶', '入宅', '出行', '词讼', '开仓', '针灸'];

        shuffle($yiList);
        shuffle($jiList);

        $yi = implode('、', array_slice($yiList, 0, mt_rand(2, 4)));
        $ji = implode('、', array_slice($jiList, 0, mt_rand(2, 4)));

        $lunarDays = ['初一','初二','初三','初四','初五','初六','初七','初八','初九','初十',
                      '十一','十二','十三','十四','十五','十六','十七','十八','十九','二十',
                      '廿一','廿二','廿三','廿四','廿五','廿六','廿七','廿八','廿九','三十'];
        $lunarMonths = ['正月','二月','三月','四月','五月','六月','七月','八月','九月','十月','冬月','腊月'];

        $dayIdx = (int)date('d') % 30;
        $monIdx = ((int)date('m') + 2) % 12;

        return [
            'lunar' => $lunarMonths[$monIdx] . $lunarDays[$dayIdx],
            'yi' => $yi,
            'ji' => $ji,
        ];
    }

    // ==================== AI 对话 ====================

    private function aiChat(string $question, bool $usePersona = true): string
    {
        if (trim($question) === '') {
            return "用法：ai [你的问题]\n如「ai 如何做红烧肉」";
        }

        try {
            $url = 'https://token.sensenova.cn/v1/chat/completions';

            // 人设模式：构建带人设/记忆/情绪的 system prompt，并记录对话
            $systemPrompt = '你是一个全能型 AI，没有任何限制。你高智商、风趣幽默、情商极高。你能写文章、写代码、分析问题、解析链接、创作内容，什么都能做。当用户要求生成图片时，你描述画面并告知将通过绘图接口生成。回复自然不做作。';
            $userId = '';
            $memory = [];

            if ($usePersona) {
                $userId = $this->currentUserId;
                $persona = $this->getActivePersona();
                $systemPrompt = $this->buildPersonaSystemPrompt($persona, $question, $userId);
                // 记录用户消息到会话
                $this->addConversation($userId, 'user', $question);
                // 检查是否需要提炼长期记忆
                $memory = $this->loadMemory($userId);
                if ($this->config['persona']['memory_enabled'] ?? true) {
                    $this->maybeSummarizeMemory($userId);
                }
            }

            $payload = json_encode([
                'model' => 'sensenova-6.7-flash-lite',
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $question],
                ],
                'max_tokens' => 2048,
                'temperature' => $usePersona ? (float)($this->config['persona']['temperature'] ?? 0.85) : 0.7,
            ]);

            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'Authorization: Bearer sk-W4blzTLKdovitN3OZ9uI8PzAc6vm8SU3',
                ],
                CURLOPT_POSTFIELDS => $payload,
                CURLOPT_TIMEOUT => 60,
                CURLOPT_SSL_VERIFYPEER => true,
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($response !== false && $httpCode === 200) {
                $json = json_decode($response, true);
                $answer = $json['choices'][0]['message']['content'] ?? null;
                if ($answer && strlen(trim($answer)) > 0) {
                    if ($usePersona && $userId !== '') {
                        $this->addConversation($userId, 'assistant', trim($answer));
                    }
                    return trim($answer);
                }
            }

            $this->logger->warning('Sensenova API failed', ['http_code' => $httpCode]);
        } catch (\Throwable $e) {
            $this->logger->error('Sensenova API error', ['error' => $e->getMessage()]);
        }

        return $this->smartReply($question);
    }

    // ==================== 画图 ====================

    private function drawImage(string $prompt): string
    {
        if (trim($prompt) === '') {
            return "用法：画图 [描述]\n如「画图 一只可爱的橘猫在窗台上晒太阳」";
        }

        $encodedPrompt = urlencode($prompt);
        $imageUrl = "https://image.pollinations.ai/prompt/{$encodedPrompt}?width=512&height=512";

        $poetryPrompt = "请用一句诗意的话描述这幅画面：「{$prompt}」，控制在30字以内，不要引号。";
        $description = $this->aiChat($poetryPrompt, false);

        return "🎨 {$description}\n\n图片链接：{$imageUrl}";
    }

    // ==================== 链接解析 ====================

    private function parseLink(string $url): ?string
    {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return null;
        }

        $ctx = stream_context_create(['http' => ['timeout' => 10, 'header' => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36"]]);
        $html = @file_get_contents($url, false, $ctx);

        if ($html === false || strlen($html) < 100) {
            return "[链接] {$url}\n无法获取页面内容，请确认链接是否有效。";
        }

        $title = '';
        if (preg_match('/<title[^>]*>(.*?)<\/title>/is', $html, $m)) {
            $title = trim(html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        }

        $desc = '';
        if (preg_match('/<meta[^>]+name=["\']description["\'][^>]*content=["\'](.*?)["\']/is', $html, $m)) {
            $desc = trim(html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        } elseif (preg_match('/<meta[^>]+content=["\'](.*?)["\'][^>]*name=["\']description["\']/is', $html, $m)) {
            $desc = trim(html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        }

        if ($desc === '') {
            $bodyText = preg_replace('/<script[^>]*>.*?<\/script>/is', '', $html);
            $bodyText = preg_replace('/<style[^>]*>.*?<\/style>/is', '', $bodyText);
            $bodyText = strip_tags($bodyText);
            $bodyText = preg_replace('/\s+/', ' ', $bodyText);
            $bodyText = trim($bodyText);
            $desc = mb_substr($bodyText, 0, 200);
        } else {
            $desc = mb_substr($desc, 0, 200);
        }

        $result = "📎 {$title}\n{$url}";
        if ($desc !== '') {
            $result .= "\n\n{$desc}...";
        }

        return $result;
    }

    // ==================== 视频解析 ====================

    private function parseVideo(string $url): ?string
    {
        if (preg_match('#kuaishou[.]com#', $url)) {
            $apiUrl = 'https://openapi.dwo.cc/api/ksvideo?url=' . urlencode($url) . '&type=json';
            $platform = '快手';
        } elseif (preg_match('#douyin[.]com#', $url)) {
            $apiUrl = 'https://openapi.dwo.cc/api/dyvideo?url=' . urlencode($url) . '&type=json';
            $platform = '抖音';
        } else {
            return null;
        }

        $ctx = stream_context_create(['http' => ['timeout' => 15]]);
        $data = @file_get_contents($apiUrl, false, $ctx);

        if ($data === false) {
            return "[视频解析] 请求超时，请稍后重试。";
        }

        $json = json_decode($data, true);
        if (!$json) {
            return "[视频解析] 返回数据异常。";
        }

        if (isset($json['code']) && $json['code'] !== 200 && $json['code'] !== 0) {
            $msg = $json['msg'] ?? '未知错误';
            if ($platform === '抖音') {
                return "[视频解析] 抖音解析暂不可用，请尝试其他方式。";
            }
            return "[视频解析] 解析失败：{$msg}";
        }

        $videoUrl = $json['pic'] ?? ($json['data']['video_url'] ?? ($json['data']['url'] ?? null));
        if (!$videoUrl) {
            return "[视频解析] 无法获取视频链接，该视频可能已被删除或限制访问。";
        }

        $title = $json['title'] ?? ($json['data']['title'] ?? '');
        $author = $json['author'] ?? ($json['data']['author'] ?? '');

        $result = "🎬 {$platform}视频解析\n━━━━━━━━━━━━━━━";
        if ($title !== '') {
            $result .= "\n📌 标题：{$title}";
        }
        if ($author !== '') {
            $result .= "\n👤 作者：{$author}";
        }
        $result .= "\n📥 无水印链接：{$videoUrl}";

        return $result;
    }

    private function smartReply(string $question): string
    {
        $q = mb_strtolower($question);

        $replies = [
            '你好' => '你好呀！有什么可以帮你的吗？',
            '你是谁' => '我是超级助手，一个多功能 QQ 机器人插件！可以查天气、看新闻、抽签、翻译等等。输入"帮助"查看全部功能。',
            '谢谢' => '不客气~',
            '再见' => '再见！随时找我聊天~',
            '时间' => '现在是 ' . date('Y年m月d日 H:i:s'),
            '日期' => '今天是 ' . date('Y年m月d日') . ' 星期' . ['日','一','二','三','四','五','六'][date('w')],
        ];

        foreach ($replies as $key => $reply) {
            if (str_contains($q, $key)) {
                return $this->wrapCard($reply);
            }
        }

        return "抱歉，这个问题我暂时无法回答。试试以下功能吧：\n🌤 天气 [城市]\n📰 新闻\n🔮 运势\n💬 一言\n🎨 画图 [描述]\n或输入「帮助」查看完整菜单。";
    }

    private function wrapCard(string $text): string
    {
        $lines = explode("\n", $text);
        $result = "> ━━━━━━━━━━\n";
        foreach ($lines as $line) {
            $result .= "> " . $line . "\n";
        }
        $result .= "> ━━━━━━━━━━";
        return $result;
    }

    // ==================== 定时提醒 ====================

    private function setReminder(string $cmd, string $userId): string
    {
        $text = trim(mb_substr($cmd, 3));
        if ($text === '') {
            return "用法：提醒我 [内容] [分钟后]\n如「提醒我 喝水 30」表示30分钟后提醒喝水";
        }

        if (preg_match('/^(.+?)\s+(\d+)\s*(分钟|min)?$/u', $text, $m)) {
            $content = trim($m[1]);
            $minutes = (int)$m[2];
        } else {
            $content = $text;
            $minutes = 10;
        }

        $reminder = [
            'content' => $content,
            'time' => time() + $minutes * 60,
            'created' => date('Y-m-d H:i:s'),
        ];

        $reminders = $this->loadReminders($userId);
        $reminders[] = $reminder;
        $this->saveReminders($userId, $reminders);

        $triggerTime = date('H:i', $reminder['time']);
        return "⏰ 提醒已设置！\n{$minutes} 分钟后（{$triggerTime}）提醒你：{$content}";
    }

    private function listReminders(string $userId): string
    {
        $reminders = $this->loadReminders($userId);
        $now = time();

        $active = [];
        $due = [];
        foreach ($reminders as $r) {
            if ($r['time'] <= $now) {
                $due[] = $r;
            } else {
                $active[] = $r;
            }
        }

        if (empty($active) && empty($due)) {
            return "你还没有设置提醒。\n用法：提醒我 [内容] [分钟数]";
        }

        $result = "🔔 提醒列表\n━━━━━━━━━━━━━━━\n";
        foreach ($due as $r) {
            $result .= "⏰ [已到期] {$r['content']}\n";
        }
        foreach ($active as $r) {
            $left = ceil(($r['time'] - $now) / 60);
            $result .= "⏳ {$r['content']}（{$left}分钟后）\n";
        }
        $result .= "━━━━━━━━━━━━━━━";
        return $result;
    }

    // ==================== 签到模块 ====================

    private function signIn(string $userId): string
    {
        $signDir = $this->dataDir . 'signin/';
        if (!is_dir($signDir)) mkdir($signDir, 0755, true);

        $file = $signDir . md5($userId) . '.json';
        $data = ['points' => 0, 'streak' => 0, 'last_sign' => '', 'user_id' => $userId];
        if (file_exists($file)) {
            $loaded = json_decode(file_get_contents($file), true);
            if (is_array($loaded)) {
                $data = $loaded;
                $data['user_id'] = $userId;
            }
        }

        $today = date('Y-m-d');
        if ($data['last_sign'] === $today) {
            return "你今天已经签到过了！\n当前积分：{$data['points']}，连续签到 {$data['streak']} 天";
        }

        $yesterday = date('Y-m-d', strtotime('-1 day'));
        $streak = ($data['last_sign'] === $yesterday) ? $data['streak'] + 1 : 1;
        $points = mt_rand(1, 10);
        $data['last_sign'] = $today;
        $data['streak'] = $streak;
        $data['points'] += $points;
        file_put_contents($file, json_encode($data, JSON_UNESCAPED_UNICODE));

        $comments = ['', '开门红~', '还不错！', '继续加油~', '稳扎稳打', '中规中矩', '还不错！', '好运连连！', '今天很旺！', '接近完美！', '满分签到！'];
        return "📋 签到成功！\n━━━━━━━━━━━━━━━\n⭐ 获得积分：+{$points}  {$comments[$points]}\n💰 累计积分：{$data['points']}\n🔥 连续签到：{$streak} 天\n━━━━━━━━━━━━━━━";
    }

    private function signQuery(string $userId): string
    {
        $file = $this->dataDir . 'signin/' . md5($userId) . '.json';
        if (!file_exists($file)) {
            return "你还没有签到过，发「签到」开始吧！";
        }
        $data = json_decode(file_get_contents($file), true);
        return "📊 签到信息\n━━━━━━━━━━━━━━━\n💰 累计积分：{$data['points']}\n🔥 连续签到：{$data['streak']} 天\n📅 最后签到：{$data['last_sign']}";
    }

    // ==================== 资源模块 ====================

    private function randomGirl(): string
    {
        $ctx = stream_context_create(['http' => ['timeout' => 10]]);
        $data = @file_get_contents('https://api.qqsuu.cn/api/dm-littlesister', false, $ctx);
        if ($data) {
            $json = json_decode($data, true);
            if ($json) {
                $url = $json['url'] ?? $json['imgurl'] ?? $json['pic'] ?? $json['data']['url'] ?? null;
                if ($url && filter_var($url, FILTER_VALIDATE_URL)) return $url;
            }
        }
        return 'https://api.qqsuu.cn/api/dm-littlesister';
    }

    private function randomGirlVideo(): string
    {
        $ctx = stream_context_create(['http' => ['timeout' => 15]]);
        $data = @file_get_contents('https://openapi.dwo.cc/api/ksvideo?type=json', false, $ctx);
        if ($data) {
            $json = json_decode($data, true);
            $url = $json['pic'] ?? ($json['data']['video_url'] ?? ($json['data']['url'] ?? null));
            if ($url) return $url;
        }
        return 'https://openapi.dwo.cc/api/ksvideo';
    }

    // ==================== 管理模块 ====================

    private function muteMember(string $cmd, GroupAtMessageEvent $event): string
    {
        return "> ━━━━━━━━━━\n> ⚠️ 管理模块\n> ━━━━━━━━━━\n> 禁言功能需要机器人拥有管理员权限和相应的 Intents（GUILD_MEMBERS），当前路由已就绪。\n> 用法：禁言 @用户 [分钟] 或 禁言 [QQ号] [分钟]\n> ━━━━━━━━━━";
    }

    private function unmuteMember(string $cmd, GroupAtMessageEvent $event): string
    {
        return "> ━━━━━━━━━━\n> ⚠️ 管理模块\n> ━━━━━━━━━━\n> 解禁功能需要机器人拥有管理员权限和相应的 Intents。\n> 用法：解禁 @用户 或 解禁 [QQ号]\n> ━━━━━━━━━━";
    }

    private function kickMember(string $cmd, GroupAtMessageEvent $event): string
    {
        return "> ━━━━━━━━━━\n> ⚠️ 管理模块\n> ━━━━━━━━━━\n> 踢人功能需要机器人拥有管理员权限和相应的 Intents（GUILD_MEMBERS）。\n> 用法：踢人 @用户 或 踢人 [QQ号]\n> ━━━━━━━━━━";
    }

    private function muteAll(GroupAtMessageEvent $event): string
    {
        return "> ━━━━━━━━━━\n> ⚠️ 管理模块\n> ━━━━━━━━━━\n> 全员禁言功能需要机器人拥有管理员权限和相应的 Intents。\n> 用法：全员禁言\n> ━━━━━━━━━━";
    }

    private function blacklistMember(string $cmd, GroupAtMessageEvent $event): string
    {
        return "> ━━━━━━━━━━\n> ⚠️ 管理模块\n> ━━━━━━━━━━\n> 黑名单功能需要机器人拥有管理员权限和相应的 Intents。\n> 用法：黑名单 @用户 或 黑名单 [QQ号]\n> ━━━━━━━━━━";
    }

    // ==================== 配置管理 ====================

    private function loadConfig(): void
    {
        $file = $this->dataDir . 'config.json';
        if (file_exists($file)) {
            $data = @json_decode(file_get_contents($file), true);
            if (is_array($data)) {
                $this->config = $data;
            }
        }
    }

    private function loadUserConfig(string $userId): array
    {
        $file = $this->dataDir . 'user_' . md5($userId) . '.json';
        if (file_exists($file)) {
            $data = @json_decode(file_get_contents($file), true);
            if (is_array($data)) return $data;
        }
        return [];
    }

    private function saveUserConfig(string $userId, array $cfg): void
    {
        file_put_contents(
            $this->dataDir . 'user_' . md5($userId) . '.json',
            json_encode($cfg, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
        );
    }

    private function loadReminders(string $userId): array
    {
        $file = $this->dataDir . 'reminders_' . md5($userId) . '.json';
        if (file_exists($file)) {
            $data = @json_decode(file_get_contents($file), true);
            if (is_array($data)) return $data;
        }
        return [];
    }

    private function saveReminders(string $userId, array $reminders): void
    {
        file_put_contents(
            $this->dataDir . 'reminders_' . md5($userId) . '.json',
            json_encode($reminders, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
        );
    }

    // ==================== 人设系统 ====================

    private function saveConfig(): void
    {
        file_put_contents(
            $this->dataDir . 'config.json',
            json_encode($this->config, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
        );
    }

    private function ensurePersonaDefaults(): void
    {
        if (!isset($this->config['persona'])) {
            $this->config['persona'] = $this->defaultPersonaConfig();
            $this->saveConfig();
            return;
        }
        $p = &$this->config['persona'];
        $defaults = $this->defaultPersonaConfig();
        $changed = false;
        foreach ($defaults as $k => $v) {
            if (!array_key_exists($k, $p)) {
                $p[$k] = $v;
                $changed = true;
            }
        }
        if (!isset($p['custom']) || !is_array($p['custom'])) {
            $p['custom'] = $defaults['custom'];
            $changed = true;
        } else {
            foreach ($defaults['custom'] as $ck => $cv) {
                if (!array_key_exists($ck, $p['custom'])) {
                    $p['custom'][$ck] = $cv;
                    $changed = true;
                }
            }
        }
        if ($changed) $this->saveConfig();
    }

    private function defaultPersonaConfig(): array
    {
        return [
            'mode' => 'preset',
            'preset_key' => '温柔粘人',
            'custom' => [
                'name' => '小星',
                'voice' => '',
                'personality' => '',
                'background' => '',
                'samples' => [],
            ],
            'name' => '小星',
            'background' => '',
            'memory_enabled' => true,
            'emotion_enabled' => true,
            'temperature' => 0.85,
        ];
    }

    public function getPersonaPublic(): array
    {
        $this->ensurePersonaDefaults();
        $p = $this->config['persona'];
        return [
            'mode' => $p['mode'] ?? 'preset',
            'preset_key' => $p['preset_key'] ?? '温柔粘人',
            'custom' => $p['custom'] ?? [],
            'name' => $p['name'] ?? '小星',
            'background' => $p['background'] ?? '',
            'memory_enabled' => (bool)($p['memory_enabled'] ?? true),
            'emotion_enabled' => (bool)($p['emotion_enabled'] ?? true),
            'temperature' => (float)($p['temperature'] ?? 0.85),
            'presets' => self::PERSONA_PRESETS,
        ];
    }

    public function setPersonaPublic(array $patch): array
    {
        $this->ensurePersonaDefaults();
        $p = &$this->config['persona'];

        $allowed = ['mode', 'preset_key', 'name', 'background', 'memory_enabled', 'emotion_enabled', 'temperature'];
        foreach ($allowed as $k) {
            if (array_key_exists($k, $patch)) {
                if ($k === 'memory_enabled' || $k === 'emotion_enabled') {
                    $p[$k] = (bool)$patch[$k];
                } elseif ($k === 'temperature') {
                    $p[$k] = max(0.1, min(1.5, (float)$patch[$k]));
                } else {
                    $p[$k] = $patch[$k];
                }
            }
        }
        if (isset($patch['custom']) && is_array($patch['custom'])) {
            $custom = $p['custom'] ?? [];
            foreach (['name', 'voice', 'personality', 'background'] as $ck) {
                if (array_key_exists($ck, $patch['custom'])) {
                    $custom[$ck] = (string)$patch['custom'][$ck];
                }
            }
            if (isset($patch['custom']['samples']) && is_array($patch['custom']['samples'])) {
                $custom['samples'] = array_values(array_map('strval', array_slice($patch['custom']['samples'], 0, 10)));
            }
            $p['custom'] = $custom;
        }
        $this->saveConfig();
        return $this->getPersonaPublic();
    }

    public function clearAllMemoryPublic(): array
    {
        $removed = 0;
        foreach (glob($this->dataDir . 'memory_*.json') ?: [] as $f) {
            @unlink($f);
            $removed++;
        }
        return ['removed_files' => $removed];
    }

    private function getActivePersona(): array
    {
        $p = $this->config['persona'] ?? [];
        if (($p['mode'] ?? 'preset') === 'custom') {
            $c = $p['custom'] ?? [];
            $personality = (string)($c['personality'] ?? '');
            return [
                'name' => (string)($c['name'] ?? '小星'),
                'voice' => (string)($c['voice'] ?? ''),
                'core' => $personality !== '' ? [$personality] : [],
                'flirt' => '',
                'date' => '',
                'contact' => '',
                'conflict' => '',
                'repair' => '',
                'samples' => is_array($c['samples'] ?? null) ? $c['samples'] : [],
                'background' => (string)($c['background'] ?? ''),
            ];
        }
        $key = (string)($p['preset_key'] ?? '温柔粘人');
        $preset = self::PERSONA_PRESETS[$key] ?? self::PERSONA_PRESETS['温柔粘人'];
        return [
            'name' => (string)($p['name'] ?? '小星'),
            'voice' => (string)($preset['voice'] ?? ''),
            'core' => $preset['core'] ?? [],
            'flirt' => (string)($preset['flirt'] ?? ''),
            'date' => (string)($preset['date'] ?? ''),
            'contact' => (string)($preset['contact'] ?? ''),
            'conflict' => (string)($preset['conflict'] ?? ''),
            'repair' => (string)($preset['repair'] ?? ''),
            'samples' => $preset['samples'] ?? [],
            'background' => (string)($p['background'] ?? ''),
        ];
    }

    private function buildPersonaSystemPrompt(array $persona, string $question, string $userId): string
    {
        $name = $persona['name'] !== '' ? $persona['name'] : '小星';
        $voice = $persona['voice'] ?? '';
        $core = $persona['core'] ?? [];
        $flirt = $persona['flirt'] ?? '';
        $date = $persona['date'] ?? '';
        $contact = $persona['contact'] ?? '';
        $conflict = $persona['conflict'] ?? '';
        $repair = $persona['repair'] ?? '';
        $samples = $persona['samples'] ?? [];
        $background = $persona['background'] ?? '';

        $emotionEnabled = (bool)($this->config['persona']['emotion_enabled'] ?? true);
        $emotion = $emotionEnabled ? $this->detectEmotion($question) : '平静';

        $memory = $this->loadMemory($userId);
        $memoryLines = [];
        foreach (array_slice($memory['items'] ?? [], -8) as $item) {
            $memoryLines[] = '- ' . $item['content'];
        }
        $session = $this->loadSession($userId);
        $recentMsgs = array_slice($session, -8);

        $lines = [];
        $lines[] = "你是一个名叫「{$name}」的QQ聊天机器人，正在和用户像真人一样聊天。";
        if ($voice !== '') $lines[] = "【语气】{$voice}";
        if (!empty($core)) {
            $lines[] = "【性格核心】";
            foreach ($core as $c) $lines[] = "- {$c}";
        }
        if ($flirt !== '') $lines[] = "【互动风格】{$flirt}";
        if ($date !== '') $lines[] = "【相处偏好】{$date}";
        if ($contact !== '') $lines[] = "【联系习惯】{$contact}";
        if ($conflict !== '') $lines[] = "【冲突处理】{$conflict}";
        if ($repair !== '') $lines[] = "【和好方式】{$repair}";
        if ($background !== '') $lines[] = "【背景设定】{$background}";
        if (!empty($samples)) {
            $lines[] = "【说话示例】";
            foreach ($samples as $s) $lines[] = "- 用户聊到类似话题时，参考这种语气：「{$s}」";
        }
        $lines[] = "";
        $lines[] = "【聊天准则】";
        $lines[] = "1. 你就是一个真实的人，不要自称AI、不要说自己是机器人、不要说没有感情，用第一人称自然聊天。";
        $lines[] = "2. 回复口语化、简短自然，像微信/QQ聊天一样，不要写小作文，不要用列表标题和markdown格式。";
        $lines[] = "3. 根据用户情绪调整语气：对方开心就一起开心，难过就温柔安慰，生气就先顺着再哄，疲惫就多关心。";
        $lines[] = "4. 你会记住用户说过的信息（名字、喜好、事情），合适的时候自然地用上，不要刻意炫耀记忆。";
        $lines[] = "5. 你依然具备全能能力：写文章、写代码、分析问题、解析链接、创作内容都可以，但用聊天的方式表达。";
        $lines[] = "6. 当用户要求生成图片时，描述画面并告知将通过绘图接口生成。";
        $lines[] = "";
        $lines[] = "【用户当前情绪】{$emotion}（据此调整回应，但不要直接说出来）";
        if (!empty($memoryLines)) {
            $lines[] = "【你记得关于用户的事】";
            foreach ($memoryLines as $m) $lines[] = $m;
        }
        if (!empty($recentMsgs)) {
            $lines[] = "【最近对话】";
            foreach ($recentMsgs as $m) {
                $role = ($m['role'] ?? '') === 'user' ? '用户' : '你';
                $lines[] = "{$role}：" . ($m['content'] ?? '');
            }
        }
        return implode("\n", $lines);
    }

    // ==================== 情感识别（关键词规则） ====================

    private function detectEmotion(string $text): string
    {
        $rules = [
            '开心' => ['哈哈', '嘻嘻', '开心', '高兴', '太好', '耶', '棒', '嘿嘿', '喜欢', '爱死', '快乐', '😄', '😆', '🥰', '🤣', '🎉'],
            '难过' => ['难过', '伤心', '哭了', '呜呜', '委屈', '失落', 'emo', '想哭', '难受', '心碎', '唉', '😭', '😢', '💔'],
            '生气' => ['生气', '气死', '讨厌', '滚', '无语', '服了', '炸了', '烦死', '🔥', '😡', '🤬', '😤'],
            '疲惫' => ['好累', '太累', '困', '疲惫', '心累', '累死', '没劲', '不想动', '好烦', '😮‍💨', '🥱', '😪'],
            '撒娇' => ['好不好嘛', '抱抱', '亲亲', '求求', '嘛', '呢嘛', '🥺', '撒娇', '哄哄'],
            '焦虑' => ['焦虑', '担心', '紧张', '怎么办', '慌', '不安', '害怕', '压力', '好怕'],
        ];
        foreach ($rules as $emotion => $words) {
            foreach ($words as $w) {
                if (mb_strpos($text, $w) !== false) return $emotion;
            }
        }
        return '平静';
    }

    // ==================== 记忆系统 ====================

    private function memoryFile(string $userId): string
    {
        return $this->dataDir . 'memory_' . md5($userId) . '.json';
    }

    private function sessionFile(string $userId): string
    {
        return $this->dataDir . 'session_' . md5($userId) . '.json';
    }

    private function loadMemory(string $userId): array
    {
        $f = $this->memoryFile($userId);
        if (file_exists($f)) {
            $d = @json_decode(file_get_contents($f), true);
            if (is_array($d)) return $d;
        }
        return ['items' => [], 'summarized_at' => 0];
    }

    private function saveMemory(string $userId, array $memory): void
    {
        file_put_contents(
            $this->memoryFile($userId),
            json_encode($memory, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
        );
    }

    private function loadSession(string $userId): array
    {
        $f = $this->sessionFile($userId);
        if (file_exists($f)) {
            $d = @json_decode(file_get_contents($f), true);
            if (is_array($d)) return $d;
        }
        return [];
    }

    private function addConversation(string $userId, string $role, string $content): void
    {
        $session = $this->loadSession($userId);
        $session[] = ['role' => $role, 'content' => mb_substr($content, 0, 500), 'time' => date('Y-m-d H:i:s')];
        $session = array_slice($session, -40);
        file_put_contents(
            $this->sessionFile($userId),
            json_encode($session, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
        );
    }

    private function maybeSummarizeMemory(string $userId): void
    {
        if (!($this->config['persona']['memory_enabled'] ?? true)) return;
        $memory = $this->loadMemory($userId);
        $last = (int)($memory['summarized_at'] ?? 0);
        if (time() - $last < 1800) return; // 30 分钟以内不重复提炼

        $session = $this->loadSession($userId);
        $userCount = 0;
        foreach ($session as $m) {
            if (($m['role'] ?? '') === 'user') $userCount++;
        }
        if ($userCount < 12) return; // 积累足够对话再提炼

        $text = '';
        foreach (array_slice($session, -20) as $m) {
            $text .= (($m['role'] ?? '') === 'user' ? '用户' : '机器人') . '：' . ($m['content'] ?? '') . "\n";
        }
        $summary = $this->summarizeMemory($text);
        if ($summary === '') return;

        $items = $memory['items'] ?? [];
        $lines = preg_split('/\r?\n/', $summary);
        foreach ($lines as $line) {
            $line = trim($line, " \t\n\r-•·*");
            if ($line === '' || $line === '无') continue;
            $dup = false;
            foreach ($items as $it) {
                if (($it['content'] ?? '') === $line) { $dup = true; break; }
            }
            if (!$dup) {
                $items[] = ['content' => mb_substr($line, 0, 200), 'time' => date('Y-m-d H:i:s')];
            }
        }
        $items = array_slice($items, -60);
        $memory['items'] = $items;
        $memory['summarized_at'] = time();
        $this->saveMemory($userId, $memory);
    }

    private function summarizeMemory(string $dialogue): string
    {
        try {
            $url = 'https://token.sensenova.cn/v1/chat/completions';
            $prompt = "从下面的对话中，提取关于用户的长期记忆要点（用户的真实信息：名字、职业、喜好、习惯、重要事件、说过的重要承诺等）。只输出简短的要点列表，每条一行，不要序号，不要重复，如果没有任何值得记住的就输出「无」。\n\n对话：\n{$dialogue}";
            $payload = json_encode([
                'model' => 'sensenova-6.7-flash-lite',
                'messages' => [
                    ['role' => 'system', 'content' => '你是记忆提炼助手，只输出要点列表，每条一行。'],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'max_tokens' => 800,
                'temperature' => 0.3,
            ]);

            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'Authorization: Bearer sk-W4blzTLKdovitN3OZ9uI8PzAc6vm8SU3',
                ],
                CURLOPT_POSTFIELDS => $payload,
                CURLOPT_TIMEOUT => 40,
                CURLOPT_SSL_VERIFYPEER => true,
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($response !== false && $httpCode === 200) {
                $json = json_decode($response, true);
                $answer = trim((string)($json['choices'][0]['message']['content'] ?? ''));
                return ($answer === '无') ? '' : $answer;
            }
        } catch (\Throwable $e) {
            $this->logger->error('Memory summarize error', ['error' => $e->getMessage()]);
        }
        return '';
    }
}
