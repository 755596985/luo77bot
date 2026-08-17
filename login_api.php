<?php
declare(strict_types=1);

/**
 * 管理后台 Session 登录 API
 * 验证管理密码，设置 PHP Session
 */

// 管理密码（可通过环境变量 QQBOT_ADMIN_PASSWORD 覆盖）
$ADMIN_PASSWORD = $_ENV['QQBOT_ADMIN_PASSWORD'] ?? 'admin123';

header('Content-Type: application/json');

// 允许跨域（与 admin.php 同域时可省略，保留用于开发调试）
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$action = $_POST['action'] ?? '';

switch ($action) {
    case 'login':
        handleLogin($ADMIN_PASSWORD);
        break;
    case 'logout':
        handleLogout();
        break;
    case 'check':
        handleCheck();
        break;
    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Unknown action']);
}

/**
 * 登录：验证密码，设置 Session
 */
function handleLogin(string $correctPassword): void
{
    $password = $_POST['password'] ?? '';

    if (empty($password)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => '密码不能为空']);
        return;
    }

    if ($password !== $correctPassword) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => '密码错误']);
        return;
    }

    // 登录成功，设置 Session
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION['admin_logged_in'] = true;
    $_SESSION['admin_login_time'] = time();

    echo json_encode(['success' => true, 'message' => '登录成功']);
}

/**
 * 登出：清除 Session
 */
function handleLogout(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params['path'], $params['domain'],
            $params['secure'], $params['httponly']
        );
    }
    session_destroy();

    echo json_encode(['success' => true, 'message' => '已退出登录']);
}

/**
 * 检查登录状态
 */
function handleCheck(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $loggedIn = ($_SESSION['admin_logged_in'] ?? false) === true;

    echo json_encode([
        'success' => true,
        'logged_in' => $loggedIn,
    ]);
}
