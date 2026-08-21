<?php

declare(strict_types=1);

/**
 * 管理后台 API
 * 提供插件管理、机器人配置和插件文件编辑的 REST 接口
 */

require_once __DIR__ . '/vendor/autoload.php';

use QQBot\Core\Application;

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];


// ===== Session 鉴权 =====
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$sessionLoggedIn = ($_SESSION['admin_logged_in'] ?? false) === true;

// ===== Token 鉴权（兼容旧方式） =====
// 安全加固：默认禁用 Token 鉴权（占位符不会被判定为有效），仅当设置了环境变量 QQBOT_ADMIN_TOKEN 才启用
$configToken = $_ENV['QQBOT_ADMIN_TOKEN'] ?? '__QQBOT_DISABLED_TOKEN__';
$apiToken = $_SERVER['HTTP_X_API_TOKEN'] ?? $_SERVER['HTTP_AUTHORIZATION'] ?? $_POST['api_token'] ?? '';
// 如果 HTTP_AUTHORIZATION 是 Bearer token，提取 token 部分
if (str_starts_with($apiToken, 'Bearer ')) {
    $apiToken = substr($apiToken, 7);
}
$tokenValid = ($apiToken !== '' && $apiToken === $configToken && $configToken !== '__QQBOT_DISABLED_TOKEN__');

// 综合鉴权：Session 登录 或 有效 Token
// （status 原先免鉴权，会向未登录访客泄露 PHP 版本/机器人与插件数量，已收紧）
if (!$sessionLoggedIn && !$tokenValid) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized: 请先登录或提供有效的 API Token']);
    exit;
}


try {
    $app = new Application(__DIR__ . '/config/bots.php');
    $app->boot();

    match ($action) {
        // 获取所有插件列表
        'plugins' => handlePlugins($app),

        // 切换插件开关
        'toggle_plugin' => handleTogglePlugin($app),

        // 获取所有机器人配置
        'bots' => handleBots($app),

        // 保存机器人配置
        'save_bot' => handleSaveBot($app),

        // 删除机器人
        'delete_bot' => handleDeleteBot($app),

        // 获取系统状态
        'status' => handleStatus($app),

        // ===== 插件文件管理 =====
        // 列出 plugins/ 目录下所有 PHP 文件
        'plugin_files' => handlePluginFiles(),

        // 读取插件文件内容
        'plugin_read' => handlePluginRead(),

        // 保存插件文件内容（带语法检查与自动备份）
        'plugin_save' => handlePluginSave(),

        // 新建插件文件
        'plugin_create' => handlePluginCreate(),

        // 删除插件文件
        'plugin_delete' => handlePluginDelete(),

        // ===== 人设系统 =====
        // 读取人设配置与预设列表
        'persona_get' => handlePersonaGet(),

        // 保存人设配置
        'persona_save' => handlePersonaSave(),

        // 查看记忆/会话文件概况
        'persona_memory' => handlePersonaMemory(),

        // 清空全部记忆
        'persona_memory_clear' => handlePersonaMemoryClear(),

        // ===== 日志中心 =====
        // 列出日志文件
        'log_files' => handleLogFiles(),

        // 读取指定日志文件内容
        'log_read' => handleLogRead(),

        // ===== 命令中心 =====
        // 扫描插件源码提取命令关键词
        'command_scan' => handleCommandScan(),

        // ===== 数据管理 =====
        // 列出 data 目录数据文件
        'data_files' => handleDataFiles(),

        // 读取数据文件内容
        'data_read' => handleDataRead(),

        // ===== Bot 配置 =====
        // 读取 config/bots.php（只读）
        'bot_config' => handleBotConfig(),

        // ===== 回调推送 =====
        // 读取/保存回调配置（GET 读，POST 存）
        'callback_config' => handleCallbackConfig($app),

        // 重置回调 Token
        'callback_reset_token' => handleCallbackResetToken(),

        // 读取最近推送记录
        'callback_logs' => handleCallbackLogs(),

        // 发送测试消息
        'callback_test' => handleCallbackTest($app),

        // ===== AI 对接 =====
        // 读取 AI 配置（GET）
        'ai_config' => handleAiConfig(),

        // 保存 AI 配置（POST）
        'ai_config_save' => handleAiConfigSave(),

        // 测试 AI 连接（POST）
        'ai_config_test' => handleAiConfigTest(),

        // ===== 功能管理 =====
        // 读取功能模块开关（GET）
        'modules_get' => handleModulesGet(),

        // 保存功能模块开关（POST）
        'modules_save' => handleModulesSave(),

        default => (function () {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Unknown action']);
        })(),
    };
} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

/* ================================================================ */

function handlePlugins(Application $app): void
{
    $manager = $app->getPluginManager();
    $registry = $app->getPluginRegistry();

    $plugins = [];
    foreach ($manager->getAllInfos() as $name => $info) {
        $state = $registry->getAllStates()[$name] ?? [];

        // 尝试定位插件主文件：plugins/{name}.php 或 plugins/{className}.php
        $file = '';
        $candidates = [$name . '.php', basename(str_replace('\\', '/', $info->className)) . '.php'];
        foreach ($candidates as $candidate) {
            if (is_file(__DIR__ . '/plugins/' . $candidate)) {
                $file = $candidate;
                break;
            }
        }

        $plugins[] = [
            'name'        => $info->name,
            'displayName' => $info->displayName,
            'version'     => $info->version,
            'description' => $info->description,
            'author'      => $info->author,
            'icon'        => $info->icon,
            'tags'        => $info->tags,
            'className'   => $info->className,
            'file'        => $file,
            'enabled'     => $registry->isEnabled($name),
            'installed'   => $state['installed'] ?? true,
            'installedAt' => $state['installedAt'] ?? '',
        ];
    }

    echo json_encode(['success' => true, 'plugins' => $plugins]);
}

function handleTogglePlugin(Application $app): void
{
    $name = $_POST['name'] ?? '';
    $enabled = filter_var($_POST['enabled'] ?? 'false', FILTER_VALIDATE_BOOLEAN);

    if (empty($name)) {
        echo json_encode(['success' => false, 'message' => 'Plugin name required']);
        return;
    }

    if ($enabled) {
        $app->getPluginManager()->enable($name);
    } else {
        $app->getPluginManager()->disable($name);
    }

    echo json_encode(['success' => true, 'enabled' => $enabled]);
}

function handleBots(Application $app): void
{
    $config = $app->getConfig();
    $bots = [];

    foreach ($config->getBotsConfig() as $botId => $botConfig) {
        $bots[] = [
            'id'            => $botId,
            'app_id'        => $botConfig['app_id'] ?? '',
            'client_secret' => maskSecret($botConfig['client_secret'] ?? ''),
            'nickname'      => $botConfig['nickname'] ?? $botId,
            'sandbox'       => $botConfig['sandbox'] ?? false,
            'intents'       => $botConfig['intents'] ?? (1 << 25),
        ];
    }

    echo json_encode([
        'success'   => true,
        'bots'      => $bots,
        'default'   => $config->getDefaultBotId(),
        'webhookUrl'=> 'https://' . ($_SERVER['HTTP_HOST'] ?? 'your-domain.com') . '/webhook.php?bot=',
    ]);
}

function handleSaveBot(Application $app): void
{
    $botId = $_POST['id'] ?? '';
    $appId = $_POST['app_id'] ?? '';
    $secret = $_POST['client_secret'] ?? '';
    $nickname = $_POST['nickname'] ?? '';
    $sandbox = filter_var($_POST['sandbox'] ?? 'false', FILTER_VALIDATE_BOOLEAN);
    $isDefault = filter_var($_POST['is_default'] ?? 'false', FILTER_VALIDATE_BOOLEAN);

    if (empty($botId) || empty($appId)) {
        echo json_encode(['success' => false, 'message' => 'Bot ID and App ID are required']);
        return;
    }

    // 读取现有配置
    $configPath = __DIR__ . '/config/bots.php';
    $config = require $configPath;

    // 更新或创建机器人配置
    $config['bots'][$botId] = [
        'app_id'        => $appId,
        'client_secret' => $secret,
        'nickname'      => $nickname,
        'sandbox'       => $sandbox,
        'intents'       => 1 << 25,
    ];

    if ($isDefault || empty($config['default'])) {
        $config['default'] = $botId;
    }

    // 写回配置文件
    $phpCode = "<?php\n\nreturn " . arrayToPhp($config) . ";\n";
    file_put_contents($configPath, $phpCode, LOCK_EX);

    echo json_encode(['success' => true]);
}

function handleDeleteBot(Application $app): void
{
    $botId = $_POST['id'] ?? '';

    if (empty($botId)) {
        echo json_encode(['success' => false, 'message' => 'Bot ID required']);
        return;
    }

    $configPath = __DIR__ . '/config/bots.php';
    $config = require $configPath;

    unset($config['bots'][$botId]);

    // 如果删除的是默认机器人，重新设置默认
    if (($config['default'] ?? '') === $botId) {
        $config['default'] = array_key_first($config['bots'] ?? []);
    }

    $phpCode = "<?php\n\nreturn " . arrayToPhp($config) . ";\n";
    file_put_contents($configPath, $phpCode, LOCK_EX);

    echo json_encode(['success' => true]);
}

function handleStatus(Application $app): void
{
    $botCount = count($app->getBotManager()->getAllBots());
    $pluginCount = count($app->getPluginManager()->getAllPlugins());
    $enabledPlugins = 0;
    foreach ($app->getPluginManager()->getAllInfos() as $info) {
        if ($info->enabled) $enabledPlugins++;
    }

    echo json_encode([
        'success' => true,
        'bots' => $botCount,
        'plugins' => ['total' => $pluginCount, 'enabled' => $enabledPlugins],
        'php_version' => PHP_VERSION,
        'sodium' => extension_loaded('sodium'),
        'time' => date('Y-m-d H:i:s'),
    ]);
}

/* ================================================================
 * 插件文件管理
 * 安全约束：
 *  - 仅允许操作 plugins/ 目录内的 .php 文件
 *  - 禁止路径穿越（..）、绝对路径、符号链接逃逸
 *  - 保存前自动备份为 .bak，并执行 php -l 语法检查（可用时）
 * ================================================================ */

/**
 * 解析并校验插件文件相对路径，返回安全的绝对路径；非法时返回 null
 */
function resolvePluginPath(string $rel): ?string
{
    $pluginsDir = realpath(__DIR__ . '/plugins');
    if ($pluginsDir === false) {
        return null;
    }

    $rel = str_replace('\\', '/', trim($rel));
    // 拒绝空路径、绝对路径、路径穿越
    if ($rel === '' || str_starts_with($rel, '/') || preg_match('#(^|/)\.\.(/|$)#', $rel)) {
        return null;
    }

    $full = realpath($pluginsDir . '/' . $rel);
    if ($full === false) {
        return null;
    }
    // 必须在 plugins 目录内
    if (!str_starts_with($full, $pluginsDir . DIRECTORY_SEPARATOR)) {
        return null;
    }
    if (!is_file($full) || strtolower(pathinfo($full, PATHINFO_EXTENSION)) !== 'php') {
        return null;
    }

    return $full;
}

/**
 * 递归扫描 plugins/ 目录下的所有 PHP 文件
 */
function scanPluginFiles(string $dir, string $base = ''): array
{
    $result = [];
    $items = @scandir($dir);
    if ($items === false) {
        return $result;
    }

    foreach ($items as $item) {
        if ($item === '.' || $item === '..' || $item[0] === '.') {
            continue;
        }
        $full = $dir . '/' . $item;
        $rel = $base === '' ? $item : $base . '/' . $item;

        if (is_dir($full)) {
            $result = array_merge($result, scanPluginFiles($full, $rel));
        } elseif (strtolower(pathinfo($item, PATHINFO_EXTENSION)) === 'php') {
            $result[] = [
                'path'  => $rel,
                'size'  => filesize($full),
                'mtime' => date('Y-m-d H:i:s', filemtime($full)),
            ];
        }
    }

    return $result;
}

function handlePluginFiles(): void
{
    $pluginsDir = realpath(__DIR__ . '/plugins');
    if ($pluginsDir === false) {
        echo json_encode(['success' => false, 'message' => 'plugins 目录不存在']);
        return;
    }

    echo json_encode([
        'success' => true,
        'dir'     => 'plugins',
        'files'   => scanPluginFiles($pluginsDir),
    ]);
}

function handlePluginRead(): void
{
    $rel = $_GET['file'] ?? $_POST['file'] ?? '';
    $path = resolvePluginPath($rel);
    if ($path === null) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => '非法文件路径']);
        return;
    }

    $content = @file_get_contents($path);
    if ($content === false) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => '文件读取失败']);
        return;
    }

    echo json_encode([
        'success' => true,
        'file'    => $rel,
        'size'    => strlen($content),
        'mtime'   => date('Y-m-d H:i:s', filemtime($path)),
        'content' => $content,
    ]);
}

function handlePluginSave(): void
{
    // 安全闸门：在线文件编辑器默认关闭，需设置环境变量 QQBOT_ENABLE_EDITOR=1 才允许写入
    if (($_ENV['QQBOT_ENABLE_EDITOR'] ?? '0') !== '1') {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => '在线文件编辑器已禁用（设置 QQBOT_ENABLE_EDITOR=1 可启用）']);
        return;
    }

    $rel = $_POST['file'] ?? '';
    $content = $_POST['content'] ?? '';

    $path = resolvePluginPath($rel);
    if ($path === null) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => '非法文件路径']);
        return;
    }

    // 文件大小限制（2MB）
    if (strlen($content) > 2 * 1024 * 1024) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => '文件过大（超过 2MB）']);
        return;
    }

    // 语法检查（php -l 可用时）
    $lintError = phpLint($content);
    if ($lintError !== null) {
        echo json_encode(['success' => false, 'message' => 'PHP 语法错误，未保存', 'lint' => $lintError]);
        return;
    }

    // 自动备份当前文件
    @copy($path, $path . '.bak');

    if (@file_put_contents($path, $content, LOCK_EX) === false) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => '文件写入失败，请检查目录权限']);
        return;
    }

    echo json_encode([
        'success' => true,
        'message' => '保存成功（已备份原文件为 .bak）',
        'mtime'   => date('Y-m-d H:i:s'),
    ]);
}

function handlePluginCreate(): void
{
    // 安全闸门：在线文件编辑器默认关闭，需设置环境变量 QQBOT_ENABLE_EDITOR=1 才允许创建文件
    if (($_ENV['QQBOT_ENABLE_EDITOR'] ?? '0') !== '1') {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => '在线文件编辑器已禁用（设置 QQBOT_ENABLE_EDITOR=1 可启用）']);
        return;
    }

    $rel = $_POST['file'] ?? '';

    $pluginsDir = realpath(__DIR__ . '/plugins');
    if ($pluginsDir === false) {
        echo json_encode(['success' => false, 'message' => 'plugins 目录不存在']);
        return;
    }

    $rel = str_replace('\\', '/', trim($rel));
    if ($rel === '' || str_starts_with($rel, '/') || preg_match('#(^|/)\.\.(/|$)#', $rel)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => '非法文件路径']);
        return;
    }
    if (strtolower(pathinfo($rel, PATHINFO_EXTENSION)) !== 'php') {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => '仅支持创建 .php 文件']);
        return;
    }

    $full = $pluginsDir . '/' . $rel;
    // 父目录必须存在且位于 plugins 内
    $realDir = realpath(dirname($full));
    if ($realDir === false || !str_starts_with($realDir, $pluginsDir . DIRECTORY_SEPARATOR)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => '目标目录不存在或非法']);
        return;
    }
    if (file_exists($full)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => '文件已存在']);
        return;
    }

    $skeleton = "<?php\n\n// " . basename($rel) . " - 新插件\n";
    if (@file_put_contents($full, $skeleton, LOCK_EX) === false) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => '创建失败，请检查目录权限']);
        return;
    }

    echo json_encode(['success' => true, 'message' => '文件已创建', 'file' => $rel]);
}

function handlePluginDelete(): void
{
    // 安全闸门：在线文件编辑器默认关闭，需设置环境变量 QQBOT_ENABLE_EDITOR=1 才允许删除文件
    if (($_ENV['QQBOT_ENABLE_EDITOR'] ?? '0') !== '1') {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => '在线文件编辑器已禁用（设置 QQBOT_ENABLE_EDITOR=1 可启用）']);
        return;
    }

    $rel = $_POST['file'] ?? '';

    $path = resolvePluginPath($rel);
    if ($path === null) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => '非法文件路径']);
        return;
    }

    // 先备份再删除，便于恢复
    @copy($path, $path . '.bak');
    if (!@unlink($path)) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => '删除失败，请检查目录权限']);
        return;
    }

    echo json_encode(['success' => true, 'message' => '文件已删除（已备份为 .bak）']);
}

/**
 * 使用 php -l 检查代码语法；返回错误信息或 null（通过）
 */
function phpLint(string $code): ?string
{
    $tmpFile = @tempnam(sys_get_temp_dir(), 'lint');
    if ($tmpFile === false) {
        return null; // 无法创建临时文件则跳过检查
    }

    if (@file_put_contents($tmpFile, $code) === false) {
        @unlink($tmpFile);
        return null;
    }

    $output = [];
    $exitCode = 0;
    exec('php -l ' . escapeshellarg($tmpFile) . ' 2>&1', $output, $exitCode);
    @unlink($tmpFile);

    if ($exitCode !== 0) {
        return implode("\n", $output);
    }

    return null;
}

/* ================================================================ */

function maskSecret(string $secret): string
{
    if (strlen($secret) <= 8) return '****';
    return substr($secret, 0, 4) . str_repeat('*', strlen($secret) - 8) . substr($secret, -4);
}

/**
 * 将数组转为 PHP 代码字符串
 */
function arrayToPhp(array $array, int $indent = 0): string
{
    $spaces = str_repeat('    ', $indent);
    $inner = str_repeat('    ', $indent + 1);

    if (empty($array)) {
        return '[]';
    }

    $parts = [];
    foreach ($array as $key => $value) {
        $keyStr = is_string($key) ? "'" . addslashes($key) . "'" : $key;

        if (is_array($value)) {
            $parts[] = "{$inner}{$keyStr} => " . arrayToPhp($value, $indent + 1);
        } elseif (is_bool($value)) {
            $parts[] = "{$inner}{$keyStr} => " . ($value ? 'true' : 'false');
        } elseif (is_int($value) || is_float($value)) {
            $parts[] = "{$inner}{$keyStr} => {$value}";
        } elseif ($value === null) {
            $parts[] = "{$inner}{$keyStr} => null";
        } else {
            $parts[] = "{$inner}{$keyStr} => '" . addslashes((string)$value) . "'";
        }
    }

    return "[\n" . implode(",\n", $parts) . ",\n{$spaces}]";
}

/* ================================================================
 * 人设系统（数据与 SuperPlugin 共用 data/super/ 目录）
 * ================================================================ */

/**
 * 预设人设库（与 SuperPlugin.php 中 PERSONA_PRESETS 保持一致）
 */
function personaPresets(): array
{
    return [
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
}

function personaConfigPath(): string
{
    return __DIR__ . '/data/super/config.json';
}

function personaDefaultConfig(): array
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

function handlePersonaGet(): void
{
    $path = personaConfigPath();
    $config = [];
    if (file_exists($path)) {
        $data = @json_decode(file_get_contents($path), true);
        if (is_array($data) && isset($data['persona']) && is_array($data['persona'])) {
            $config = $data['persona'];
        }
    }
    $config = array_merge(personaDefaultConfig(), $config);
    if (!isset($config['custom']) || !is_array($config['custom'])) {
        $config['custom'] = personaDefaultConfig()['custom'];
    } else {
        $config['custom'] = array_merge(personaDefaultConfig()['custom'], $config['custom']);
    }

    echo json_encode([
        'success' => true,
        'persona' => $config,
        'presets' => personaPresets(),
    ], JSON_UNESCAPED_UNICODE);
}

function handlePersonaSave(): void
{
    $raw = file_get_contents('php://input');
    $patch = json_decode($raw, true);
    if (!is_array($patch)) {
        $patch = $_POST;
    }
    if (empty($patch)) {
        echo json_encode(['success' => false, 'message' => '无提交数据']);
        return;
    }

    $path = personaConfigPath();
    $config = [];
    if (file_exists($path)) {
        $data = @json_decode(file_get_contents($path), true);
        if (is_array($data)) $config = $data;
    }
    if (!isset($config['persona']) || !is_array($config['persona'])) {
        $config['persona'] = personaDefaultConfig();
    }
    $p = &$config['persona'];

    $allowed = ['mode', 'preset_key', 'name', 'background', 'memory_enabled', 'emotion_enabled', 'temperature'];
    foreach ($allowed as $k) {
        if (array_key_exists($k, $patch)) {
            if ($k === 'memory_enabled' || $k === 'emotion_enabled') {
                $p[$k] = (bool)$patch[$k];
            } elseif ($k === 'temperature') {
                $p[$k] = max(0.1, min(1.5, (float)$patch[$k]));
            } elseif ($k === 'preset_key' && !isset(personaPresets()[$patch[$k]])) {
                continue; // 非法预设名忽略
            } else {
                $p[$k] = (string)$patch[$k];
            }
        }
    }
    if (isset($patch['custom']) && is_array($patch['custom'])) {
        $custom = $p['custom'] ?? personaDefaultConfig()['custom'];
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

    if (!is_dir(dirname($path))) {
        @mkdir(dirname($path), 0755, true);
    }
    if (@file_put_contents($path, json_encode($config, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), LOCK_EX) === false) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => '写入配置失败，请检查目录权限']);
        return;
    }

    echo json_encode(['success' => true, 'message' => '人设已保存']);
}

function handlePersonaMemory(): void
{
    $dir = __DIR__ . '/data/super/';
    $files = [];
    foreach (['memory' => 'memory_*.json', 'session' => 'session_*.json'] as $kind => $pattern) {
        foreach (glob($dir . $pattern) ?: [] as $f) {
            $data = @json_decode(file_get_contents($f), true);
            $count = 0;
            if (is_array($data)) {
                if ($kind === 'memory') {
                    $count = count($data['items'] ?? []);
                } else {
                    $count = count($data);
                }
            }
            $files[] = [
                'kind' => $kind,
                'file' => basename($f),
                'count' => $count,
                'updated_at' => date('Y-m-d H:i:s', filemtime($f)),
            ];
        }
    }
    usort($files, fn($a, $b) => strcmp($b['updated_at'], $a['updated_at']));

    echo json_encode(['success' => true, 'files' => $files]);
}

function handlePersonaMemoryClear(): void
{
    $dir = __DIR__ . '/data/super/';
    $removed = 0;
    foreach (['memory_*.json', 'session_*.json'] as $pattern) {
        foreach (glob($dir . $pattern) ?: [] as $f) {
            if (@unlink($f)) $removed++;
        }
    }
    echo json_encode(['success' => true, 'removed' => $removed]);
}

// =====================================================================
// 日志中心
// =====================================================================
function handleLogFiles(): void
{
    $dir = __DIR__ . '/logs';
    $files = [];
    if (is_dir($dir)) {
        foreach (glob($dir . '/qqbot-*.log') ?: [] as $f) {
            $files[] = [
                'file'       => basename($f),
                'size'       => @filesize($f),
                'size_human' => formatBytes(@filesize($f)),
                'updated_at' => date('Y-m-d H:i:s', @filemtime($f)),
            ];
        }
    }
    usort($files, fn($a, $b) => strcmp($b['file'], $a['file']));
    echo json_encode(['success' => true, 'files' => $files, 'dir' => $dir]);
}

function handleLogRead(): void
{
    $file = basename($_GET['file'] ?? '');
    if (!preg_match('/^[A-Za-z0-9._-]+\.log$/', $file)) {
        echo json_encode(['success' => false, 'message' => '非法文件名']);
        return;
    }
    $path = __DIR__ . '/logs/' . $file;
    if (!is_file($path)) {
        echo json_encode(['success' => false, 'message' => '日志文件不存在']);
        return;
    }
    $content = @file_get_contents($path);
    if ($content === false) {
        echo json_encode(['success' => false, 'message' => '读取失败']);
        return;
    }
    $lines = array_values(array_filter(explode("\n", $content), fn($l) => trim($l) !== ''));
    $total = count($lines);
    $last  = array_slice($lines, -500);
    $stats = ['debug' => 0, 'info' => 0, 'warning' => 0, 'error' => 0];
    foreach ($last as $l) {
        foreach (array_keys($stats) as $k) {
            if (str_contains($l, "[{$k}]")) { $stats[$k]++; break; }
        }
    }
    echo json_encode(['success' => true, 'file' => $file, 'total' => $total, 'lines' => array_reverse($last), 'stats' => $stats]);
}

// =====================================================================
// 命令中心：扫描插件源码提取命令关键词
// =====================================================================
function handleCommandScan(): void
{
    $dir = __DIR__ . '/plugins';
    $result = [];
    foreach (glob($dir . '/*.php') ?: [] as $f) {
        $src = @file_get_contents($f);
        if ($src === false) continue;
        $commands = [];

        // 1) 中文/英文命令：$cmd === 'X' 或 $cmd == 'X'（含单双引号）
        if (preg_match_all('/\$cmd\s*(?:===|==)\s*([\'"])([^\'"]+)\1/u', $src, $m)) {
            foreach ($m[2] as $c) {
                if (trim($c) === '' || mb_strlen($c) > 30) continue;
                $commands[$c] = ($commands[$c] ?? 0) + 1;
            }
        }
        // 2) in_array($cmd, ['A','B', ...])
        if (preg_match_all('/in_array\(\s*\$cmd\s*,\s*\[([^\]]*)\]/u', $src, $m)) {
            foreach ($m[1] as $list) {
                if (preg_match_all('/([\'"])([^\'"]+)\1/u', $list, $m2)) {
                    foreach ($m2[2] as $c) {
                        if (trim($c) === '' || mb_strlen($c) > 30) continue;
                        $commands[$c] = ($commands[$c] ?? 0) + 1;
                    }
                }
            }
        }
        // 3) str_starts_with($cmd, 'X') / str_contains / preg 前缀命令
        if (preg_match_all('/str_starts_with\(\s*\$cmd\s*,\s*([\'"])([^\'"]+)\1/u', $src, $m)) {
            foreach ($m[2] as $c) {
                if (trim($c) === '' || mb_strlen($c) > 30) continue;
                $commands[$c] = ($commands[$c] ?? 0) + 1;
            }
        }
        // 4) 传统 / 前缀或 # 前缀命令（兼容英文 slash 命令，排除文件路径）
        if (preg_match_all('/([\'"])(\/[A-Za-z0-9_!?.\-]{1,30})\1/u', $src, $m)) {
            foreach ($m[2] as $c) {
                if (str_contains($c, '.') || str_contains($c, '/')) continue;
                $commands[$c] = ($commands[$c] ?? 0) + 1;
            }
        }

        if (!empty($commands)) {
            arsort($commands);
            $items = [];
            foreach (array_slice($commands, 0, 40) as $cmd => $cnt) {
                $items[] = ['command' => $cmd, 'count' => $cnt];
            }
            $result[] = ['plugin' => basename($f), 'commands' => $items];
        }
    }
    echo json_encode(['success' => true, 'plugins' => $result]);
}

// =====================================================================
// 数据管理
// =====================================================================
function handleDataFiles(): void
{
    $dir = __DIR__ . '/data';
    $files = [];
    if (is_dir($dir)) {
        foreach (glob($dir . '/*.json') ?: [] as $f) { addDataFile($files, $f, ''); }
        foreach (glob($dir . '/*.txt') ?: [] as $f) { addDataFile($files, $f, ''); }
        foreach (glob($dir . '/super/*.json') ?: [] as $f) { addDataFile($files, $f, 'super/'); }
        foreach (glob($dir . '/super/*.txt') ?: [] as $f) { addDataFile($files, $f, 'super/'); }
    }
    usort($files, fn($a, $b) => strcmp($b['updated_at'], $a['updated_at']));
    echo json_encode(['success' => true, 'files' => $files, 'dir' => $dir]);
}

function addDataFile(array &$files, string $path, string $prefix): void
{
    $files[] = [
        'rel'        => $prefix . basename($path),
        'size'       => @filesize($path),
        'size_human' => formatBytes(@filesize($path)),
        'updated_at' => date('Y-m-d H:i:s', @filemtime($path)),
    ];
}

function handleDataRead(): void
{
    $file = (string)($_GET['file'] ?? '');
    $rel  = str_replace(['..', '\\'], '', $file); // 防目录穿越
    $path = __DIR__ . '/data/' . $rel;
    if (!is_file($path)) {
        echo json_encode(['success' => false, 'message' => '文件不存在']);
        return;
    }
    $realBase = realpath(__DIR__ . '/data');
    $realPath = realpath($path);
    if ($realPath === false || $realBase === false || !str_starts_with($realPath, $realBase . DIRECTORY_SEPARATOR)) {
        echo json_encode(['success' => false, 'message' => '非法路径']);
        return;
    }
    $content = @file_get_contents($path);
    if ($content === false) {
        echo json_encode(['success' => false, 'message' => '读取失败']);
        return;
    }
    $json = json_decode($content, true);
    $pretty = ($json !== null) ? json_encode($json, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : $content;
    echo json_encode(['success' => true, 'file' => $rel, 'content' => $pretty, 'is_json' => ($json !== null)]);
}

// =====================================================================
// Bot 配置（只读）
// =====================================================================
function handleBotConfig(): void
{
    $path = __DIR__ . '/config/bots.php';
    if (!is_file($path)) {
        echo json_encode(['success' => false, 'message' => '配置文件不存在']);
        return;
    }
    $content = @file_get_contents($path);
    if ($content === false) {
        echo json_encode(['success' => false, 'message' => '读取失败']);
        return;
    }
    echo json_encode(['success' => true, 'content' => $content]);
}

// =====================================================================
// 回调推送
// 配置存储于 data/callback.json，推送记录存储于 data/callbacks/YYYYMMDD.jsonl
// =====================================================================

function callbackConfigFile(): string
{
    return __DIR__ . '/data/callback.json';
}

/**
 * 读取回调配置；不存在时自动生成默认配置
 */
function loadCallbackConfig(): array
{
    $file = callbackConfigFile();
    $config = is_file($file) ? (json_decode((string)file_get_contents($file), true) ?: []) : [];

    $changed = false;
    if (empty($config['token'])) {
        $config['token'] = bin2hex(random_bytes(16));
        $changed = true;
    }
    if (!isset($config['bot_id'])) {
        $config['bot_id'] = 'bot2';
        $changed = true;
    }
    if (!isset($config['receiver_openid'])) {
        $config['receiver_openid'] = '';
        $changed = true;
    }
    if (!isset($config['enabled'])) {
        $config['enabled'] = true;
        $changed = true;
    }
    if (empty($config['template'])) {
        $config['template'] = '来新订单了\n{content}\n时间: {time}';
        $changed = true;
    }
    if (!isset($config['copy_enabled'])) {
        $config['copy_enabled'] = false;   // 默认关闭：需在后台配置供货商地址与 Token 后才启用
        $changed = true;
    }
    if (empty($config['copy_url'])) {
        $config['copy_url'] = '';          // 留空则不调用供货商拉取接口
        $changed = true;
    }
    if (empty($config['copy_token'])) {
        $config['copy_token'] = '';        // 留空则不调用供货商拉取接口
        $changed = true;
    }
    if (empty($config['copy_param'])) {
        $config['copy_param'] = 'orderSn';
        $changed = true;
    }
    if (empty($config['copy_method'])) {
        $config['copy_method'] = 'POST';
        $changed = true;
    }
    if (!isset($config['created_at'])) {
        $config['created_at'] = date('Y-m-d H:i:s');
        $changed = true;
    }

    if ($changed) {
        saveCallbackConfig($config);
    }
    return $config;
}

function saveCallbackConfig(array $config): void
{
    @file_put_contents(callbackConfigFile(), json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX);
}

function callbackBaseUrl(): string
{
    return 'https://' . ($_SERVER['HTTP_HOST'] ?? 'your-domain.com') . '/callback.php';
}

function handleCallbackConfig(Application $app): void
{
    $config = loadCallbackConfig();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $botId   = (string)($_POST['bot_id'] ?? $config['bot_id'] ?? 'bot2');
        $openid  = trim((string)($_POST['receiver_openid'] ?? $config['receiver_openid'] ?? ''));
        $enabled = filter_var($_POST['enabled'] ?? '0', FILTER_VALIDATE_BOOLEAN);
        $template = (string)($_POST['template'] ?? $config['template'] ?? '');
        if ($template === '') {
            $template = '来新订单了\n{content}\n时间: {time}';
        }

        $config['bot_id']          = $botId;
        $config['receiver_openid'] = $openid;
        $config['enabled']         = $enabled;
        $config['template']        = $template;
        $config['copy_enabled']    = filter_var($_POST['copy_enabled'] ?? ($config['copy_enabled'] ?? true), FILTER_VALIDATE_BOOLEAN);
        $config['copy_url']        = trim((string)($_POST['copy_url'] ?? $config['copy_url'] ?? ''));
        $config['copy_token']      = trim((string)($_POST['copy_token'] ?? $config['copy_token'] ?? ''));
        $config['copy_param']      = trim((string)($_POST['copy_param'] ?? $config['copy_param'] ?? 'orderSn'));
        $config['copy_method']     = strtoupper(trim((string)($_POST['copy_method'] ?? $config['copy_method'] ?? 'GET'))) === 'POST' ? 'POST' : 'GET';
        saveCallbackConfig($config);
    }

    $bots = [];
    foreach ($app->getConfig()->getBotsConfig() as $id => $bc) {
        $bots[] = ['id' => $id, 'nickname' => $bc['nickname'] ?? $id];
    }

    echo json_encode([
        'success' => true,
        'config'  => $config,
        'bots'    => $bots,
        'url'     => callbackBaseUrl() . '?token=' . $config['token'],
    ]);
}

function handleCallbackResetToken(): void
{
    $config = loadCallbackConfig();
    $config['token'] = bin2hex(random_bytes(16));
    saveCallbackConfig($config);

    echo json_encode([
        'success' => true,
        'token'   => $config['token'],
        'url'     => callbackBaseUrl() . '?token=' . $config['token'],
    ]);
}

function handleCallbackLogs(): void
{
    $dir = __DIR__ . '/data/callbacks';
    $logs = [];
    if (is_dir($dir)) {
        $files = glob($dir . '/*.jsonl') ?: [];
        rsort($files);
        foreach ($files as $f) {
            $lines = file($f, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            if ($lines === false) {
                continue;
            }
            foreach (array_reverse($lines) as $line) {
                $item = json_decode((string)$line, true);
                if (is_array($item)) {
                    $logs[] = $item;
                }
                if (count($logs) >= 50) {
                    break 2;
                }
            }
        }
    }

    echo json_encode(['success' => true, 'logs' => array_slice($logs, 0, 50)]);
}

function handleCallbackTest(Application $app): void
{
    $config = loadCallbackConfig();
    $openid = trim((string)($config['receiver_openid'] ?? ''));
    if ($openid === '') {
        echo json_encode(['success' => true, 'sent' => false, 'message' => '接收人 OpenID 未配置，请先配置后再测试']);
        return;
    }

    $botId  = (string)($config['bot_id'] ?? 'bot2');
    $template = (string)($config['template'] ?? '来新订单了\n{content}\n时间: {time}');
    $text = str_replace(
        ['{content}', '{source}', '{time}', '{ip}', '{method}'],
        ['这是一条回调测试消息', '测试', date('Y-m-d H:i:s'), '', 'POST'],
        $template
    );
    $text = str_replace('\\n', "\n", $text);

    try {
        $bot = $app->getBotManager()->getBot($botId);
        if ($bot === null) {
            echo json_encode(['success' => false, 'message' => '机器人不存在: ' . $botId]);
            return;
        }
        $bot->getClient()->sendC2CMessage($openid, ['content' => $text]);
        echo json_encode(['success' => true, 'sent' => true, 'message' => '测试消息已发送']);
    } catch (\Throwable $e) {
        echo json_encode(['success' => false, 'message' => '发送失败: ' . $e->getMessage()]);
    }
}

function formatBytes(int $bytes): string
{
    if ($bytes >= 1048576) return round($bytes / 1048576, 1) . ' MB';
    if ($bytes >= 1024) return round($bytes / 1024, 1) . ' KB';
    return $bytes . ' B';
}

/* ================================================================ */
/* ================================================================ */
/* ===== AI 对接（多供应商 / 故障转移） ===== */

require_once __DIR__ . '/src/Service/AiClient.php';

function handleAiConfig(): void
{
    $cfg = \QQBot\Service\AiClient::loadConfig();
    $providers = [];
    foreach ($cfg['providers'] as $p) {
        $pp = $p;
        if (($pp['api_key'] ?? '') !== '') {
            $pp['api_key'] = maskSecret((string)$pp['api_key']);
        }
        $providers[] = $pp;
    }
    echo json_encode([
        'success' => true,
        'config'  => [
            'providers'     => $providers,
            'strategy'      => $cfg['strategy'],
            'max_tokens'    => $cfg['max_tokens'],
            'temperature'   => $cfg['temperature'],
            'system_prompt' => $cfg['system_prompt'],
        ],
    ]);
}

function handleAiConfigSave(): void
{
    // 兼容两种来源：FormData（URLSearchParams）或 JSON 请求体
    $raw = $_POST;
    if (empty($raw)) {
        $decoded = json_decode((string)file_get_contents('php://input'), true);
        if (is_array($decoded)) {
            $raw = $decoded;
        }
    }

    $providers = $raw['providers'] ?? null;

    // 兼容旧前端：仅传单条 base_url / model / api_key
    if (!is_array($providers)) {
        $baseUrl = trim((string)($raw['base_url'] ?? ''));
        $model   = trim((string)($raw['model'] ?? ''));
        $apiKey  = trim((string)($raw['api_key'] ?? ''));
        if ($baseUrl === '' || $model === '') {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => '接口地址与模型名称不能为空']);
            return;
        }
        if ($apiKey === '' || str_contains($apiKey, '*')) {   // 含 * 即视为脱敏回显值
            $apiKey = (string)(\QQBot\Service\AiClient::loadConfig()['providers'][0]['api_key'] ?? '');
        }
        $providers = [[
            'name' => '默认', 'base_url' => $baseUrl, 'model' => $model,
            'api_key' => $apiKey, 'weight' => 1, 'enabled' => true,
        ]];
    }

    // 回退被脱敏(****)或未填写的 API Key 到已保存的真实值
    $old = \QQBot\Service\AiClient::loadConfig();
    $oldByName = [];
    foreach ($old['providers'] as $op) {
        $oldByName[$op['name']] = $op;
    }
    foreach ($providers as $i => &$p) {
        if (!is_array($p)) {
            continue;
        }
        if ((string)($p['api_key'] ?? '') !== '' && str_contains((string)$p['api_key'], '*')) {
            // 回显的脱敏值（maskSecret 前4+*+后4）：回退到已保存的真实 key，避免覆盖丢失
            $real = $old['providers'][$i]['api_key'] ?? ($oldByName[($p['name'] ?? '')]['api_key'] ?? '');
            $p['api_key'] = $real;
        }
    }
    unset($p);

    $input = [
        'providers'     => $providers,
        'strategy'      => (string)($raw['strategy'] ?? 'failover'),
        'max_tokens'    => (int)($raw['max_tokens'] ?? 2048),
        'temperature'   => (float)($raw['temperature'] ?? 0.85),
        'system_prompt' => (string)($raw['system_prompt'] ?? ''),
    ];

    \QQBot\Service\AiClient::saveConfig($input);
    echo json_encode(['success' => true, 'message' => 'AI 对接配置已保存']);
}

function handleAiConfigTest(): void
{
    $cfg = \QQBot\Service\AiClient::loadConfig();
    if (empty($cfg['providers'])) {
        echo json_encode(['success' => false, 'message' => '尚未配置任何供应商，请先在「保存配置」中填写至少一个。']);
        return;
    }
    $results = \QQBot\Service\AiClient::testAll();
    $total   = count($results);
    $okCount = 0;
    foreach ($results as $r) {
        if (!empty($r['enabled']) && $r['ok']) {
            $okCount++;
        }
    }
    echo json_encode([
        'success' => $okCount > 0,
        'results' => $results,
        'message' => "共 {$okCount}/{$total} 个供应商连接正常",
    ]);
}

/* ===== 功能管理 ===== */

function modulesConfigFile(): string
{
    return __DIR__ . '/data/modules.json';
}

function loadModulesConfig(): array
{
    $file = modulesConfigFile();
    if (!is_file($file)) {
        return [];
    }
    $json = json_decode((string)@file_get_contents($file), true);
    return is_array($json) ? $json : [];
}

function saveModulesConfig(array $modules): void
{
    @file_put_contents(modulesConfigFile(), json_encode($modules, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
}

function handleModulesGet(): void
{
    echo json_encode(['success' => true, 'modules' => loadModulesConfig()]);
}

function handleModulesSave(): void
{
    $key = (string)($_POST['key'] ?? '');
    $enabled = filter_var($_POST['enabled'] ?? 'true', FILTER_VALIDATE_BOOLEAN);

    $allowed = [
        'dashboard', 'bots', 'plugins', 'files', 'persona', 'logs',
        'commands', 'data', 'callback', 'ai', 'settings',
    ];
    if (!in_array($key, $allowed, true)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => '未知功能模块: ' . $key]);
        return;
    }

    $modules = loadModulesConfig();
    if ($enabled) {
        unset($modules[$key]);
    } else {
        $modules[$key] = false;
    }
    saveModulesConfig($modules);

    echo json_encode(['success' => true, 'message' => '已更新', 'modules' => $modules]);
}
