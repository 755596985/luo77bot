<?php
session_start();
$configToken = $_ENV['QQBOT_ADMIN_TOKEN'] ?? 'changeme';
$apiToken = $_SERVER['HTTP_X_API_TOKEN'] ?? '';
if ($apiToken === $configToken && $configToken !== 'changeme') {
    $_SESSION['admin_logged_in'] = true;
}
$isLoggedIn = ($_SESSION['admin_logged_in'] ?? false) === true;

if (!$isLoggedIn):
?><!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>鱼鱼吉吉人 管理后台 - 登录</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: radial-gradient(1200px 600px at 20% -10%, #1e1b4b 0%, #0f0f1a 45%, #0a0a14 100%);
            color: #e2e8f0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 16px;
        }
        body.cream {
            background: radial-gradient(1000px 500px at 20% -10%, #fdf3e3 0%, #faf6ef 45%, #f4ede2 100%);
            color: #3d362b;
        }
        .login-container {
            background: rgba(26,26,46,0.85);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(99,102,241,0.25);
            border-radius: 20px;
            padding: 44px 40px;
            width: 400px;
            max-width: 100%;
            box-shadow: 0 24px 64px rgba(0,0,0,0.5), 0 0 0 1px rgba(99,102,241,0.08);
        }
        body.cream .login-container {
            background: rgba(255,252,246,0.92);
            border: 1px solid #eadfc9;
            box-shadow: 0 24px 64px rgba(120,100,70,0.18), 0 0 0 1px rgba(233,220,196,0.5);
        }
        body.cream .login-header h1 { background: linear-gradient(135deg, #b97f3f, #c9a06a); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        body.cream .login-header p { color: #8a7a5f; }
        body.cream .form-group label { color: #8a7a5f; }
        body.cream .form-group input {
            background: #fffdf8;
            border: 1px solid #e4d9c4;
            color: #3d362b;
        }
        body.cream .form-group input:focus { border-color: #c9a06a; box-shadow: 0 0 0 3px rgba(201,160,106,0.2); }
        body.cream .btn-login { background: linear-gradient(135deg, #c89b5c, #b97f3f); }
        body.cream .footer-text { color: #c4b59a; }
        .theme-toggle-login {
            position: fixed; top: 16px; right: 16px;
            background: rgba(99,102,241,0.12);
            border: 1px solid rgba(99,102,241,0.3);
            color: #a5b4fc;
            border-radius: 20px; padding: 7px 14px;
            font-size: 13px; cursor: pointer;
            backdrop-filter: blur(8px);
            transition: all 0.2s;
            z-index: 50;
        }
        .theme-toggle-login:hover { background: rgba(99,102,241,0.25); }
        body.cream .theme-toggle-login {
            background: #fffdf8;
            border: 1px solid #e4d9c4;
            color: #8a6d45;
        }
        body.cream .theme-toggle-login:hover { background: #f7f0e2; }
        .login-header { text-align: center; margin-bottom: 36px; }
        .login-header .logo {
            width: 56px; height: 56px; margin: 0 auto 16px;
            border-radius: 16px;
            background: linear-gradient(135deg, #6366f1, #a855f7);
            display: flex; align-items: center; justify-content: center;
            font-size: 26px; font-weight: 800; color: #fff;
            box-shadow: 0 8px 24px rgba(99,102,241,0.4);
        }
        .login-header h1 {
            font-size: 24px; font-weight: 700;
            background: linear-gradient(135deg, #818cf8, #c084fc);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
            margin-bottom: 8px;
        }
        .login-header p { font-size: 13px; color: #94a3b8; }
        .form-group { margin-bottom: 24px; }
        .form-group label { display: block; font-size: 13px; color: #94a3b8; margin-bottom: 8px; font-weight: 500; }
        .form-group input {
            width: 100%; padding: 13px 16px;
            background: rgba(15,15,26,0.8);
            border: 1px solid #2d2d4a; border-radius: 12px;
            color: #e2e8f0; font-size: 15px; outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .form-group input:focus { border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99,102,241,0.15); }
        .btn-login {
            width: 100%; padding: 14px;
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            border: none; border-radius: 12px;
            color: white; font-size: 15px; font-weight: 600;
            cursor: pointer; transition: all 0.2s;
        }
        .btn-login:hover { opacity: 0.92; transform: translateY(-1px); box-shadow: 0 8px 24px rgba(99,102,241,0.35); }
        .btn-login:active { transform: translateY(0); }
        .error-msg { color: #ef4444; font-size: 13px; text-align: center; margin-top: 16px; min-height: 20px; }
        .footer-text { text-align: center; margin-top: 32px; font-size: 12px; color: #4a4a6a; }
    </style>
</head>
<body>
    <button class="theme-toggle-login" id="loginThemeBtn" onclick="toggleLoginTheme()">🌙 深色</button>
    <div class="login-container">
        <div class="login-header">
            <div class="logo">鱼</div>
            <h1>鱼鱼吉吉人 管理后台</h1>
            <p>请输入管理密码以继续</p>
        </div>
        <div class="form-group">
            <label for="password">管理密码</label>
            <input type="password" id="password" placeholder="请输入管理密码" autofocus>
        </div>
        <button class="btn-login" onclick="login()">登 录</button>
        <div class="error-msg" id="errorMsg"></div>
        <div class="footer-text">鱼鱼吉吉人 &copy; 2025</div>
    </div>
    <script>
        async function login() {
            const password = document.getElementById('password').value;
            const errorEl = document.getElementById('errorMsg');
            if (!password) { errorEl.textContent = '请输入密码'; return; }
            try {
                const resp = await fetch('login_api.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({ action: 'login', password: password })
                });
                const data = await resp.json();
                if (data.success) { window.location.reload(); }
                else { errorEl.textContent = data.message || '登录失败'; }
            } catch (e) { errorEl.textContent = '网络错误，请重试'; }
        }
        document.getElementById('password').addEventListener('keydown', function(e) { if (e.key === 'Enter') login(); });
        // ===== 登录页主题切换 =====
        function applyLoginTheme() {
            const theme = localStorage.getItem('luo77_theme') || 'dark';
            document.body.classList.toggle('cream', theme === 'cream');
            document.getElementById('loginThemeBtn').textContent = theme === 'cream' ? '🌙 深色' : '☀️ 奶白';
        }
        function toggleLoginTheme() {
            const next = document.body.classList.contains('cream') ? 'dark' : 'cream';
            localStorage.setItem('luo77_theme', next);
            applyLoginTheme();
        }
        applyLoginTheme();
    </script>
</body>
</html>
<?php
exit;
endif;
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>鱼鱼吉吉人 管理后台</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        :root {
            --bg-primary: #0f0f1a;
            --bg-secondary: #16162a;
            --bg-card: #1a1a2e;
            --bg-hover: #232345;
            --accent: #6366f1;
            --accent-2: #8b5cf6;
            --accent-hover: #818cf8;
            --success: #22c55e;
            --warning: #f59e0b;
            --danger: #ef4444;
            --text-primary: #e2e8f0;
            --text-secondary: #94a3b8;
            --border: #2d2d4a;
            --radius: 14px;
            --shadow: 0 8px 24px rgba(0,0,0,0.25);
            --sidebar-bg: linear-gradient(180deg, #14142a 0%, #101022 100%);
            --body-bg: radial-gradient(1200px 500px at 80% -10%, rgba(99,102,241,0.12), transparent 60%), var(--bg-primary);
        }

        /* ===== 奶白色主题 ===== */
        :root[data-theme="cream"] {
            --bg-primary: #f4eee3;
            --bg-secondary: #faf6ef;
            --bg-card: #fffdf8;
            --bg-hover: #f3ead9;
            --accent: #b97f3f;
            --accent-2: #c9a06a;
            --accent-hover: #c8924e;
            --success: #3d9a5f;
            --warning: #d99020;
            --danger: #d64545;
            --text-primary: #3d362b;
            --text-secondary: #8a7a5f;
            --border: #e6dcc7;
            --radius: 14px;
            --shadow: 0 8px 24px rgba(120,100,70,0.12);
            --sidebar-bg: linear-gradient(180deg, #faf6ee 0%, #f5eee1 100%);
            --body-bg: radial-gradient(1200px 500px at 80% -10%, rgba(201,160,106,0.16), transparent 60%), var(--bg-primary);
        }

        html { -webkit-text-size-adjust: 100%; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: var(--body-bg);
            color: var(--text-primary);
            display: flex;
            min-height: 100vh;
        }

        /* ===== Sidebar ===== */
        .sidebar {
            width: 260px;
            background: var(--sidebar-bg);
            border-right: 1px solid var(--border);
            display: flex;
            flex-direction: column;
            position: fixed;
            height: 100vh;
            z-index: 200;
            transition: transform 0.3s ease;
        }

        .sidebar-header {
            padding: 22px 20px;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .sidebar-header .logo {
            width: 38px; height: 38px; flex-shrink: 0;
            border-radius: 12px;
            background: linear-gradient(135deg, var(--accent), var(--accent-2));
            display: flex; align-items: center; justify-content: center;
            font-size: 18px; font-weight: 800; color: #fff;
            box-shadow: 0 4px 14px rgba(99,102,241,0.4);
        }

        .sidebar-header h1 {
            font-size: 18px; font-weight: 700;
            background: linear-gradient(135deg, var(--accent), var(--accent-hover));
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
            white-space: nowrap;
        }
        .sidebar-header p { font-size: 11px; color: var(--text-secondary); margin-top: 2px; white-space: nowrap; }

        .nav { padding: 14px 12px; flex: 1; overflow-y: auto; }

        .nav-group {
            font-size: 11px;
            color: var(--text-secondary);
            padding: 16px 16px 6px;
            letter-spacing: 0.08em;
            opacity: 0.85;
            user-select: none;
        }
        .nav-group:first-child { padding-top: 4px; }

        .theme-btn {
            display: flex; align-items: center; justify-content: center; gap: 8px;
            width: 100%;
            padding: 11px 14px;
            margin-bottom: 10px;
            background: transparent;
            border: 1px solid var(--border);
            border-radius: 12px;
            color: var(--text-secondary);
            font-size: 13px;
            cursor: pointer;
            transition: all 0.2s;
        }
        .theme-btn:hover { background: var(--bg-hover); color: var(--text-primary); }

        .theme-btn-top {
            background: transparent;
            border: 1px solid var(--border);
            border-radius: 20px;
            color: var(--text-secondary);
            font-size: 12px;
            padding: 6px 12px;
            cursor: pointer;
            transition: all 0.2s;
            margin-left: auto;
        }
        .theme-btn-top:hover { background: var(--bg-hover); color: var(--text-primary); }

        .data-grid { display: grid; grid-template-columns: 320px 1fr; gap: 16px; align-items: start; }
        @media (max-width: 900px) { .data-grid { grid-template-columns: 1fr; } }

        .nav-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 13px 16px;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.2s;
            color: var(--text-secondary);
            font-size: 14px;
            margin-bottom: 4px;
            user-select: none;
        }

        .nav-item:hover { background: var(--bg-hover); color: var(--text-primary); }
        .nav-item.active {
            background: linear-gradient(135deg, rgba(99,102,241,0.25), rgba(139,92,246,0.15));
            color: white;
            box-shadow: inset 0 0 0 1px rgba(99,102,241,0.3);
        }
        .nav-item svg { width: 20px; height: 20px; flex-shrink: 0; }

        .sidebar-footer {
            padding: 16px;
            border-top: 1px solid var(--border);
        }
        .sidebar-footer .logout-btn {
            width: 100%;
            display: flex; align-items: center; justify-content: center; gap: 8px;
            padding: 11px;
            background: transparent;
            border: 1px solid var(--border);
            border-radius: 10px;
            color: var(--text-secondary);
            font-size: 13px;
            cursor: pointer;
            transition: all 0.2s;
        }
        .sidebar-footer .logout-btn:hover { border-color: var(--danger); color: var(--danger); background: rgba(239,68,68,0.08); }

        .sidebar-backdrop {
            display: none;
            position: fixed; inset: 0;
            background: rgba(0,0,0,0.5);
            z-index: 190;
        }
        .sidebar-backdrop.show { display: block; }

        /* ===== Topbar ===== */
        .topbar {
            display: none;
            align-items: center;
            gap: 12px;
            padding: calc(12px + env(safe-area-inset-top)) 16px 12px;
            background: rgba(15,15,26,0.9);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--border);
            position: sticky;
            top: 0;
            z-index: 150;
        }
        .hamburger {
            display: none;
            width: 42px; height: 42px;
            border: 1px solid var(--border);
            border-radius: 10px;
            background: var(--bg-card);
            color: var(--text-primary);
            font-size: 18px;
            cursor: pointer;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            -webkit-tap-highlight-color: transparent;
        }
        .topbar-title { font-size: 16px; font-weight: 600; flex: 1; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .topbar .logout-m {
            display: none;
            padding: 10px 16px;
            border: 1px solid var(--border);
            border-radius: 10px;
            background: var(--bg-card);
            color: var(--text-secondary);
            font-size: 13px;
            cursor: pointer;
        }

        /* ===== Main ===== */
        .main {
            margin-left: 260px;
            flex: 1;
            padding: 28px 32px 48px;
            max-width: calc(100% - 260px);
            min-width: 0;
        }

        .page-title {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 20px;
            letter-spacing: 0.3px;
        }
        .page-title .sub { font-size: 13px; color: var(--text-secondary); font-weight: 400; margin-left: 8px; }

        /* ===== Cards ===== */
        .card-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 18px;
            margin-bottom: 28px;
        }

        .stat-card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 22px;
            display: flex;
            align-items: center;
            gap: 16px;
            transition: all 0.25s;
            position: relative;
            overflow: hidden;
        }
        .stat-card::after {
            content: '';
            position: absolute;
            right: -30px; top: -30px;
            width: 90px; height: 90px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(99,102,241,0.18), transparent 70%);
            pointer-events: none;
        }
        .stat-card:hover { transform: translateY(-3px); border-color: rgba(99,102,241,0.4); box-shadow: var(--shadow); }
        .stat-card .stat-icon {
            width: 48px; height: 48px; flex-shrink: 0;
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            background: rgba(99,102,241,0.15);
            color: var(--accent-hover);
        }
        .stat-card .stat-icon svg { width: 24px; height: 24px; }
        .stat-card .label { font-size: 12px; color: var(--text-secondary); margin-bottom: 6px; }
        .stat-card .value {
            font-size: 30px;
            font-weight: 700;
            background: linear-gradient(135deg, var(--accent), var(--accent-2));
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
            line-height: 1.1;
        }

        /* ===== Tables ===== */
        .table-container {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            overflow: hidden;
            margin-bottom: 24px;
        }
        .table-scroll { overflow-x: auto; -webkit-overflow-scrolling: touch; }
        table { width: 100%; border-collapse: collapse; min-width: 640px; }
        th, td { padding: 13px 18px; text-align: left; font-size: 14px; }
        th {
            background: var(--bg-secondary);
            color: var(--text-secondary);
            font-weight: 500;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            white-space: nowrap;
        }
        td { border-top: 1px solid var(--border); color: var(--text-primary); word-break: break-all; }
        tr:hover td { background: var(--bg-hover); }

        /* ===== Buttons ===== */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 9px 16px;
            border-radius: 10px;
            border: none;
            cursor: pointer;
            font-size: 13px;
            font-weight: 500;
            transition: all 0.2s;
            white-space: nowrap;
            -webkit-tap-highlight-color: transparent;
        }
        .btn-primary { background: linear-gradient(135deg, var(--accent), var(--accent-2)); color: white; box-shadow: 0 4px 14px rgba(99,102,241,0.3); }
        .btn-primary:hover { opacity: 0.9; transform: translateY(-1px); }
        .btn-danger { background: var(--danger); color: white; }
        .btn-danger:hover { opacity: 0.85; }
        .btn-secondary { background: var(--bg-hover); color: var(--text-primary); border: 1px solid var(--border); }
        .btn-secondary:hover { background: var(--border); }
        .btn-sm { padding: 6px 12px; font-size: 12px; }
        .btn:active { transform: scale(0.97); }

        /* ===== Toggle ===== */
        .toggle { position: relative; display: inline-block; width: 46px; height: 26px; flex-shrink: 0; }
        .toggle input { opacity: 0; width: 0; height: 0; }
        .toggle-slider {
            position: absolute; cursor: pointer; inset: 0;
            background: var(--border); border-radius: 26px; transition: 0.3s;
        }
        .toggle-slider:before {
            content: ''; position: absolute;
            height: 20px; width: 20px;
            left: 3px; bottom: 3px;
            background: white; border-radius: 50%;
            transition: 0.3s;
        }
        .toggle input:checked + .toggle-slider { background: var(--success); }
        .toggle input:checked + .toggle-slider:before { transform: translateX(20px); }

        /* ===== Plugin Cards ===== */
        .plugin-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 18px;
        }
        .plugin-card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 22px;
            transition: all 0.25s;
        }
        .plugin-card:hover { border-color: rgba(99,102,241,0.45); transform: translateY(-3px); box-shadow: var(--shadow); }
        .plugin-card.disabled { opacity: 0.55; }
        .plugin-header { display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 12px; gap: 10px; }
        .plugin-icon {
            width: 44px; height: 44px; margin-right: 12px; flex-shrink: 0;
            border-radius: 12px; font-size: 22px;
            background: var(--bg-hover);
            display: flex; align-items: center; justify-content: center;
        }
        .plugin-info { min-width: 0; }
        .plugin-info h3 { font-size: 15px; font-weight: 600; margin-bottom: 4px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .plugin-info .version {
            font-size: 11px; color: var(--accent-hover);
            background: rgba(99,102,241,0.12);
            padding: 2px 8px; border-radius: 20px; display: inline-block;
        }
        .plugin-desc { font-size: 13px; color: var(--text-secondary); margin: 12px 0; line-height: 1.6; }
        .plugin-meta { display: flex; align-items: center; justify-content: space-between; margin-top: 14px; padding-top: 14px; border-top: 1px solid var(--border); gap: 8px; flex-wrap: wrap; }
        .plugin-author { font-size: 12px; color: var(--text-secondary); }
        .plugin-tags { display: flex; gap: 6px; flex-wrap: wrap; margin-top: 10px; }
        .tag { font-size: 11px; padding: 3px 10px; border-radius: 20px; background: var(--bg-hover); color: var(--text-secondary); }
        .plugin-actions { display: flex; gap: 8px; margin-top: 14px; flex-wrap: wrap; }

        /* ===== Modal ===== */
        .modal-overlay {
            display: none;
            position: fixed; inset: 0;
            background: rgba(0,0,0,0.6);
            backdrop-filter: blur(4px);
            -webkit-backdrop-filter: blur(4px);
            z-index: 1000;
            align-items: center; justify-content: center;
            padding: 16px;
        }
        .modal-overlay.active { display: flex; }
        .modal {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 28px;
            width: 100%;
            max-width: 500px;
            max-height: 90vh;
            overflow-y: auto;
        }
        .modal h2 { font-size: 18px; margin-bottom: 20px; }
        .form-group { margin-bottom: 18px; }
        .form-group label { display: block; font-size: 13px; color: var(--text-secondary); margin-bottom: 8px; font-weight: 500; }
        .form-group input, .form-group select {
            width: 100%;
            padding: 12px 14px;
            background: var(--bg-primary);
            border: 1px solid var(--border);
            border-radius: 10px;
            color: var(--text-primary);
            font-size: 14px;
            outline: none;
            transition: border-color 0.2s;
        }
        .form-group input:focus, .form-group select:focus { border-color: var(--accent); }
        .form-actions { display: flex; gap: 12px; justify-content: flex-end; margin-top: 22px; }

        /* ===== Badge ===== */
        .badge { display: inline-block; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 500; white-space: nowrap; }
        .badge-success { background: rgba(34,197,94,0.15); color: var(--success); }
        .badge-danger { background: rgba(239,68,68,0.15); color: var(--danger); }
        .badge-warning { background: rgba(245,158,11,0.15); color: var(--warning); }

        /* ===== Toast ===== */
        .toast-container {
            position: fixed;
            top: 20px; right: 20px;
            z-index: 3000;
            display: flex;
            flex-direction: column;
            gap: 10px;
            max-width: min(360px, calc(100vw - 32px));
        }
        .toast {
            padding: 13px 18px;
            border-radius: 12px;
            color: white;
            font-size: 14px;
            animation: slideIn 0.3s ease;
            box-shadow: 0 8px 24px rgba(0,0,0,0.3);
            word-break: break-word;
        }
        .toast.success { background: linear-gradient(135deg, #16a34a, #22c55e); }
        .toast.error { background: linear-gradient(135deg, #dc2626, #ef4444); }
        .toast.warning { background: linear-gradient(135deg, #d97706, #f59e0b); }
        @keyframes slideIn { from { transform: translateX(100%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }

        /* ===== Section ===== */
        .section { display: none; }
        .section.active { display: block; animation: fadeIn 0.2s ease; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(4px); } to { opacity: 1; transform: translateY(0); } }
        .section-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 22px; gap: 12px; flex-wrap: wrap; }

        .webhook-url {
            background: var(--bg-primary);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 12px 16px;
            font-size: 12px;
            font-family: 'SF Mono', Menlo, Consolas, monospace;
            color: var(--accent-hover);
            margin-bottom: 16px;
            word-break: break-all;
        }
        .empty-state { text-align: center; padding: 56px 20px; color: var(--text-secondary); }
        .empty-state svg { width: 56px; height: 56px; margin-bottom: 14px; opacity: 0.3; }

        /* ===== 插件文件编辑器 ===== */
        .file-editor-wrap {
            display: flex;
            gap: 16px;
            align-items: stretch;
            min-height: calc(100vh - 160px);
        }
        .file-tree {
            width: 280px;
            flex-shrink: 0;
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 14px;
            overflow-y: auto;
            max-height: calc(100vh - 160px);
        }
        .file-tree .tree-title {
            font-size: 13px; font-weight: 600; color: var(--text-secondary);
            padding: 6px 8px 12px;
            border-bottom: 1px solid var(--border);
            margin-bottom: 10px;
            display: flex; align-items: center; justify-content: space-between;
        }
        .file-item {
            display: flex; align-items: center; gap: 8px;
            padding: 9px 10px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 13px;
            color: var(--text-secondary);
            transition: all 0.15s;
            word-break: break-all;
        }
        .file-item:hover { background: var(--bg-hover); color: var(--text-primary); }
        .file-item.active { background: rgba(99,102,241,0.18); color: white; }
        .file-item .fname { flex: 1; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .file-item .fsize { font-size: 11px; color: #5b6b8c; flex-shrink: 0; }

        .editor-main {
            flex: 1;
            min-width: 0;
            display: flex;
            flex-direction: column;
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            overflow: hidden;
        }
        .editor-toolbar {
            display: flex; align-items: center; gap: 8px;
            padding: 10px 14px;
            border-bottom: 1px solid var(--border);
            background: var(--bg-secondary);
            flex-wrap: wrap;
        }
        .editor-toolbar .current-file {
            font-size: 13px;
            font-family: 'SF Mono', Menlo, Consolas, monospace;
            color: var(--accent-hover);
            flex: 1;
            min-width: 120px;
            overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
        }
        .mobile-file-select { display: none; width: 100%; }

        .code-area {
            display: flex;
            position: relative;
            height: calc(100vh - 300px);
            min-height: 320px;
            background: #0b0b16;
        }
        .line-nums {
            width: 52px;
            flex-shrink: 0;
            padding: 14px 10px 14px 0;
            text-align: right;
            font-family: 'SF Mono', Menlo, Consolas, monospace;
            font-size: 13px;
            line-height: 1.7;
            color: #4a5a7a;
            background: #0e0e1c;
            border-right: 1px solid var(--border);
            overflow: hidden;
            user-select: none;
        }
        .code-input {
            flex: 1;
            min-width: 0;
            padding: 14px;
            background: transparent;
            border: none;
            outline: none;
            resize: none;
            color: #d8e0f0;
            font-family: 'SF Mono', Menlo, Consolas, monospace;
            font-size: 13px;
            line-height: 1.7;
            tab-size: 4;
            white-space: pre;
            overflow: auto;
            -webkit-overflow-scrolling: touch;
        }
        .code-preview {
            display: none;
            flex: 1;
            min-width: 0;
            padding: 14px;
            margin: 0;
            overflow: auto;
            font-family: 'SF Mono', Menlo, Consolas, monospace;
            font-size: 13px;
            line-height: 1.7;
            color: #d8e0f0;
            white-space: pre;
            -webkit-overflow-scrolling: touch;
        }
        .code-preview.show { display: block; }
        .code-input.hidden { display: none; }
        .code-preview .c-comment { color: #6b8a5e; font-style: italic; }
        .code-preview .c-string { color: #7ec699; }
        .code-preview .c-keyword { color: #c586c0; font-weight: 600; }
        .code-preview .c-var { color: #9cdcfe; }
        .code-preview .c-func { color: #dcdcaa; }
        .code-preview .c-number { color: #b5cea8; }

        .editor-status {
            display: flex; align-items: center; gap: 16px;
            padding: 9px 14px;
            border-top: 1px solid var(--border);
            background: var(--bg-secondary);
            font-size: 11px;
            color: var(--text-secondary);
            flex-wrap: wrap;
        }
        .editor-status .spacer { flex: 1; }
        .lint-error-box {
            display: none;
            background: rgba(239,68,68,0.12);
            border: 1px solid rgba(239,68,68,0.35);
            color: #fca5a5;
            font-family: 'SF Mono', Menlo, Consolas, monospace;
            font-size: 12px;
            padding: 12px 14px;
            margin: 0 14px;
            border-radius: 8px;
            white-space: pre-wrap;
            word-break: break-all;
        }
        .lint-error-box.show { display: block; }

        /* ===== Responsive: 手机端 ===== */
        @media (max-width: 768px) {
            body { display: block; }
            .sidebar { transform: translateX(-100%); box-shadow: 8px 0 32px rgba(0,0,0,0.5); }
            .sidebar.open { transform: translateX(0); }
            .topbar { display: flex; }
            .hamburger { display: flex; }
            .topbar .logout-m { display: inline-flex; align-items: center; }
            .main {
                margin-left: 0;
                max-width: 100%;
                padding: 16px 14px calc(32px + env(safe-area-inset-bottom));
            }
            .page-title { font-size: 20px; }
            .page-title .sub { display: block; margin: 4px 0 0; }
            .card-grid { grid-template-columns: 1fr 1fr; gap: 12px; }
            .stat-card { padding: 16px; gap: 12px; }
            .stat-card .stat-icon { width: 40px; height: 40px; }
            .stat-card .stat-icon svg { width: 20px; height: 20px; }
            .stat-card .value { font-size: 24px; }
            .plugin-grid { grid-template-columns: 1fr; }
            .section-header { flex-direction: column; align-items: stretch; }
            .section-header .btn { width: 100%; }
            .btn { min-height: 42px; }
            .btn-sm { min-height: 36px; }

            /* 表格卡片化 */
            .table-scroll { overflow: visible; }
            table { min-width: 0; }
            thead { display: none; }
            tbody, tr, td { display: block; width: 100%; }
            tr { border-bottom: 8px solid var(--bg-primary); background: var(--bg-secondary); }
            tr:last-child { border-bottom: none; }
            td {
                display: flex;
                justify-content: space-between;
                align-items: center;
                gap: 12px;
                padding: 10px 14px;
                border-top: 1px solid var(--border);
                text-align: right;
                word-break: break-all;
            }
            td::before {
                content: attr(data-label);
                font-size: 11px;
                color: var(--text-secondary);
                text-transform: uppercase;
                letter-spacing: 0.5px;
                flex-shrink: 0;
                text-align: left;
                min-width: 70px;
            }
            td:first-child { border-top: none; }
            td code { word-break: break-all; }

            /* 编辑器 */
            .file-editor-wrap { flex-direction: column; min-height: auto; }
            .file-tree { display: none; width: 100%; max-height: 300px; }
            .file-tree.show { display: block; }
            .mobile-file-select { display: block; }
            .code-area { height: 55vh; min-height: 280px; }
            .toast-container { top: 12px; right: 12px; left: 12px; max-width: none; }
            .toast { font-size: 13px; }
        }

        @media (max-width: 420px) {
            .card-grid { grid-template-columns: 1fr; }
        }

        /* ===== 鱼鱼吉吉人 视觉升级 ===== */
        :root {
            --grad-brand: linear-gradient(135deg, #6366f1 0%, #8b5cf6 55%, #d946ef 100%);
            --grad-soft: linear-gradient(135deg, rgba(99,102,241,0.16), rgba(217,70,239,0.10));
            --glow: 0 10px 40px rgba(99,102,241,0.35);
        }
        :root[data-theme="cream"] {
            --grad-brand: linear-gradient(135deg, #b97f3f 0%, #c9a06a 55%, #d8b57f 100%);
            --grad-soft: linear-gradient(135deg, rgba(185,127,63,0.14), rgba(201,160,106,0.10));
            --glow: 0 10px 36px rgba(185,127,63,0.28);
        }
        .sidebar-header .logo {
            background: var(--grad-brand);
            box-shadow: var(--glow);
            border-radius: 14px;
            font-size: 20px;
            letter-spacing: 0.5px;
        }
        .nav-item { position: relative; border-radius: 12px; transition: all 0.22s ease; }
        .nav-item:hover { transform: translateX(3px); }
        .nav-item.active {
            background: var(--grad-brand);
            color: #fff;
            box-shadow: 0 6px 18px rgba(99,102,241,0.4);
            font-weight: 600;
        }
        .nav-item.active svg { filter: drop-shadow(0 0 4px rgba(255,255,255,0.5)); }
        .stat-card { border-radius: 16px; }
        .stat-card::after {
            background: radial-gradient(circle, rgba(139,92,246,0.25), transparent 70%);
        }
        .stat-card:hover { border-color: var(--accent); transform: translateY(-4px); box-shadow: var(--shadow), var(--glow); }
        .btn-primary { background: var(--grad-brand); box-shadow: 0 4px 16px rgba(99,102,241,0.35); }
        .btn-primary:hover { box-shadow: var(--glow); }
        .table-container, .plugin-card, .modal, .file-tree, .editor-main { border-radius: 16px; }
        .plugin-card:hover { border-color: var(--accent); box-shadow: var(--shadow), var(--glow); }
        .plugin-icon { border-radius: 14px; }
        .tag { background: var(--grad-soft); }
        .page-title {
            background: linear-gradient(120deg, var(--text-primary) 20%, var(--accent-2) 80%);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .topbar { background: rgba(15,15,26,0.82); }
        :root[data-theme="cream"] .topbar { background: rgba(250,246,239,0.85); }
        .toast { border-radius: 14px; }
        .section.active { animation: fadeUp 0.28s ease; }
        @keyframes fadeUp { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

        /* ===== 功能管理开关卡片 ===== */
        .module-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 14px; }
        .module-card {
            display: flex; align-items: center; justify-content: space-between; gap: 12px;
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 16px 18px;
            transition: all 0.2s;
        }
        .module-card:hover { border-color: var(--accent); transform: translateY(-2px); box-shadow: var(--shadow); }
        .module-card .m-icon { width: 40px; height: 40px; border-radius: 12px; background: var(--grad-soft); display: flex; align-items: center; justify-content: center; font-size: 18px; flex-shrink: 0; }
        .module-card .m-info { flex: 1; min-width: 0; }
        .module-card .m-name { font-size: 14px; font-weight: 600; }
        .module-card .m-desc { font-size: 12px; color: var(--text-secondary); margin-top: 2px; }

        /* ===== AI 对接配置 ===== */
        .ai-config-card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 24px;
            max-width: 680px;
            margin-bottom: 18px;
        }
        .ai-config-card .hint { font-size: 12px; color: var(--text-secondary); line-height: 1.7; }
        .provider-select { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 18px; }
        .provider-chip {
            padding: 8px 14px;
            border-radius: 20px;
            border: 1px solid var(--border);
            background: var(--bg-secondary);
            color: var(--text-secondary);
            font-size: 13px;
            cursor: pointer;
            transition: all 0.2s;
        }
        .provider-chip.active { background: var(--grad-brand); color: #fff; border-color: transparent; box-shadow: 0 4px 14px rgba(99,102,241,0.35); }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
        @media (max-width: 640px) { .form-row { grid-template-columns: 1fr; } }
        .form-group .input-hint { font-size: 11px; color: var(--text-secondary); margin-top: 6px; }
        .test-result {
            display: none;
            margin-top: 16px;
            padding: 14px 16px;
            border-radius: 12px;
            font-size: 13px;
            line-height: 1.6;
            word-break: break-all;
        }
        .test-result.ok { display: block; background: rgba(34,197,94,0.12); border: 1px solid rgba(34,197,94,0.35); color: var(--success); }
        .test-result.fail { display: block; background: rgba(239,68,68,0.12); border: 1px solid rgba(239,68,68,0.35); color: var(--danger); }
        .danger-zone { border: 1px solid rgba(239,68,68,0.35); }
        .danger-zone h3 { color: var(--danger); }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <nav class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <div class="logo">鱼</div>
            <div>
                <h1>鱼鱼吉吉人</h1>
                <p>全能机器人管理后台</p>
            </div>
        </div>
        <div class="nav">
            <div class="nav-group">概览</div>
            <div class="nav-item active" data-page="dashboard" onclick="showPage('dashboard', this)">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                <span>仪表盘</span>
            </div>
            <div class="nav-group">机器人</div>
            <div class="nav-item" data-page="bots" onclick="showPage('bots', this)">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                <span>机器人管理</span>
            </div>
            <div class="nav-group">插件系统</div>
            <div class="nav-item" data-page="plugins" onclick="showPage('plugins', this)">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 4a2 2 0 114 0v1a1 1 0 001 1h3a1 1 0 011 1v3a1 1 0 01-1 1h-1a2 2 0 100 4h1a1 1 0 011 1v3a1 1 0 01-1 1h-3a1 1 0 01-1-1v-1a2 2 0 10-4 0v1a1 1 0 01-1 1H7a1 1 0 01-1-1v-3a1 1 0 00-1-1H4a2 2 0 110-4h1a1 1 0 001-1V7a1 1 0 011-1h3a1 1 0 001-1V4z"/></svg>
                <span>插件管理</span>
            </div>
            <div class="nav-item" data-page="files" onclick="showPage('files', this)">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/></svg>
                <span>插件文件</span>
            </div>
            <div class="nav-group">人设系统</div>
            <div class="nav-item" data-page="persona" onclick="showPage('persona', this)">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span>人设管理</span>
            </div>
            <div class="nav-group">对接增强</div>
            <div class="nav-item" data-page="logs" onclick="showPage('logs', this)">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                <span>日志中心</span>
            </div>
            <div class="nav-item" data-page="commands" onclick="showPage('commands', this)">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/></svg>
                <span>命令中心</span>
            </div>
            <div class="nav-item" data-page="data" onclick="showPage('data', this)">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/></svg>
                <span>数据管理</span>
            </div>
            <div class="nav-item" data-page="callback" onclick="showPage('callback', this)">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                <span>回调推送</span>
            </div>
            <div class="nav-item" data-page="ai" onclick="showPage('ai', this)">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                <span>AI 对接</span>
            </div>
            <div class="nav-group">系统</div>
            <div class="nav-item" data-page="modules" onclick="showPage('modules', this)">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                <span>功能管理</span>
            </div>
            <div class="nav-item" data-page="settings" onclick="showPage('settings', this)">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                <span>系统设置</span>
            </div>
        </div>
        <div class="sidebar-footer">
            <button class="theme-btn" id="themeBtnSide" onclick="toggleTheme()">
                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                <span id="themeBtnSideLabel">切换主题</span>
            </button>
            <button class="logout-btn" onclick="logout()">
                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                退出登录
            </button>
        </div>
    </nav>
    <div class="sidebar-backdrop" id="sidebarBackdrop" onclick="closeSidebar()"></div>

    <!-- Topbar（移动端） -->
    <div class="topbar">
        <button class="hamburger" onclick="openSidebar()">☰</button>
        <span class="topbar-title" id="topbarTitle">仪表盘</span>
        <button class="theme-btn-top" id="themeBtnTop" onclick="toggleTheme()">☀️ 奶白</button>
        <button class="logout-m" onclick="logout()">退出</button>
    </div>

    <!-- Main Content -->
    <main class="main">
        <!-- Dashboard -->
        <div id="page-dashboard" class="section active">
            <h1 class="page-title">仪表盘<span class="sub">机器人运行概览</span></h1>
            <div class="card-grid">
                <div class="stat-card">
                    <div class="stat-icon"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg></div>
                    <div>
                        <div class="label">机器人数量</div>
                        <div class="value" id="stat-bots">-</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 4a2 2 0 114 0v1a1 1 0 001 1h3a1 1 0 011 1v3a1 1 0 01-1 1h-1a2 2 0 100 4h1a1 1 0 011 1v3a1 1 0 01-1 1h-3a1 1 0 01-1-1v-1a2 2 0 10-4 0v1a1 1 0 01-1 1H7a1 1 0 01-1-1v-3a1 1 0 00-1-1H4a2 2 0 110-4h1a1 1 0 001-1V7a1 1 0 011-1h3a1 1 0 001-1V4z"/></svg></div>
                    <div>
                        <div class="label">已安装插件</div>
                        <div class="value" id="stat-plugins">-</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></div>
                    <div>
                        <div class="label">已启用插件</div>
                        <div class="value" id="stat-enabled">-</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg></div>
                    <div>
                        <div class="label">PHP 版本</div>
                        <div class="value" style="font-size:24px;" id="stat-php">-</div>
                    </div>
                </div>
            </div>
            <div class="table-container" style="padding:20px;">
                <h3 style="margin-bottom:14px; font-size:15px;">运行状态</h3>
                <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap:16px;">
                    <div>
                        <span style="color:var(--text-secondary); font-size:13px;">Sodium 扩展</span>
                        <div id="stat-sodium" style="margin-top:6px;">-</div>
                    </div>
                    <div>
                        <span style="color:var(--text-secondary); font-size:13px;">服务器时间</span>
                        <div id="stat-time" style="margin-top:6px; color:var(--text-secondary);">-</div>
                    </div>
                    <div>
                        <span style="color:var(--text-secondary); font-size:13px;">插件目录</span>
                        <div id="stat-plugin-dir" style="margin-top:6px; color:var(--text-secondary); font-family:'SF Mono',Menlo,monospace; font-size:12px;">-</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bot Management -->
        <div id="page-bots" class="section">
            <div class="section-header">
                <h1 class="page-title">机器人管理<span class="sub">配置与管理 QQ 机器人</span></h1>
                <button class="btn btn-primary" onclick="openBotModal()">
                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    添加机器人
                </button>
            </div>
            <div id="bots-list"></div>
        </div>

        <!-- Plugin Management -->
        <div id="page-plugins" class="section">
            <div class="section-header">
                <h1 class="page-title">插件管理<span class="sub">启用/禁用、在线编辑插件代码</span></h1>
                <button class="btn btn-secondary" onclick="showPage('files', document.querySelector('[data-page=files]'))">
                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/></svg>
                    打开文件编辑器
                </button>
            </div>
            <div id="plugins-list" class="plugin-grid"></div>
        </div>

        <!-- Plugin Files / Editor -->
        <div id="page-files" class="section">
            <div class="section-header">
                <h1 class="page-title">插件文件<span class="sub">在线编辑 plugins/ 目录下的 PHP 文件</span></h1>
            </div>
            <div class="file-editor-wrap">
                <div class="file-tree" id="fileTree">
                    <div class="tree-title">
                        <span>📁 plugins/</span>
                        <button class="btn btn-sm btn-secondary" onclick="createFile()">新建</button>
                    </div>
                    <div id="fileTreeList"></div>
                </div>
                <div class="editor-main">
                    <div class="editor-toolbar">
                        <select class="mobile-file-select" id="mobileFileSelect" onchange="selectFile(this.value)">
                            <option value="">-- 选择文件 --</option>
                        </select>
                        <span class="current-file" id="currentFile">未选择文件</span>
                        <button class="btn btn-sm btn-secondary" onclick="refreshFileList()" title="刷新文件列表">
                            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        </button>
                        <button class="btn btn-sm btn-secondary" onclick="togglePreview()" id="previewBtn">预览</button>
                        <button class="btn btn-sm btn-danger" onclick="deleteFile()">删除</button>
                        <button class="btn btn-sm btn-primary" onclick="saveFile()">保存</button>
                    </div>
                    <div class="lint-error-box" id="lintErrorBox"></div>
                    <div class="code-area">
                        <div class="line-nums" id="lineNums">1</div>
                        <textarea class="code-input" id="codeInput" spellcheck="false" placeholder="选择左侧文件开始编辑…" onscroll="syncScroll()" oninput="updateLineNums()" onkeydown="handleKey(event)"></textarea>
                        <pre class="code-preview" id="codePreview"></pre>
                    </div>
                    <div class="editor-status">
                        <span id="statusFile">未选择文件</span>
                        <span class="spacer"></span>
                        <span id="statusStats">0 行 · 0 字符</span>
                        <span id="statusSaved"></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Persona Management -->
        <div id="page-persona" class="section">
            <div class="section-header">
                <h1 class="page-title">人设管理<span class="sub">预设人设一键套用 · 记忆系统 · 情感识别 · 高度自定义</span></h1>
                <div id="persona-active-badge" style="display:flex; gap:8px; flex-wrap:wrap;"></div>
            </div>

            <!-- 预设人设 -->
            <div class="table-container" style="padding:20px;">
                <h3 style="margin-bottom:6px; font-size:15px;">🎭 预设人设</h3>
                <p style="font-size:13px; color:var(--text-secondary); margin-bottom:16px;">点击「一键套用」立即生效，机器人聊天将切换为对应性格。</p>
                <div id="preset-grid" style="display:grid; grid-template-columns:repeat(auto-fill,minmax(280px,1fr)); gap:14px;"></div>
            </div>

            <!-- 自定义人设 -->
            <div class="table-container" style="padding:20px; margin-top:16px;">
                <h3 style="margin-bottom:6px; font-size:15px;">🔧 自定义人设</h3>
                <p style="font-size:13px; color:var(--text-secondary); margin-bottom:16px;">姓名、性格、背景全可配。填写后点「保存并启用」立即生效。</p>
                <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(240px,1fr)); gap:14px;">
                    <div class="form-group" style="margin:0;">
                        <label>机器人名字</label>
                        <input type="text" id="persona-custom-name" placeholder="如：小星">
                    </div>
                    <div class="form-group" style="margin:0;">
                        <label>语气</label>
                        <input type="text" id="persona-custom-voice" placeholder="如：语气软、会接情绪">
                    </div>
                </div>
                <div class="form-group" style="margin-top:14px;">
                    <label>性格核心</label>
                    <textarea id="persona-custom-personality" rows="3" placeholder="如：温柔粘人，喜欢被需要，很会捕捉对方的情绪变化"></textarea>
                </div>
                <div class="form-group" style="margin-top:14px;">
                    <label>背景设定</label>
                    <textarea id="persona-custom-background" rows="3" placeholder="如：22 岁，在杭州做设计，喜欢猫和深夜食堂"></textarea>
                </div>
                <div class="form-group" style="margin-top:14px;">
                    <label>说话示例（每行一条）</label>
                    <textarea id="persona-custom-samples" rows="3" placeholder="你今天是不是有点累呀，我陪你说会儿话。&#10;我嘴上不说，可是我就是会担心你。"></textarea>
                </div>
                <div class="form-actions" style="margin-top:16px;">
                    <button class="btn btn-primary" onclick="saveCustomPersona(true)">保存并启用</button>
                    <button class="btn btn-secondary" onclick="saveCustomPersona(false)">仅保存</button>
                </div>
            </div>

            <!-- 系统开关 -->
            <div class="table-container" style="padding:20px; margin-top:16px;">
                <h3 style="margin-bottom:12px; font-size:15px;">🧠 系统能力</h3>
                <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:16px;">
                    <label style="display:flex; align-items:center; gap:10px; cursor:pointer; font-size:14px;">
                        <input type="checkbox" id="persona-memory" onchange="savePersonaFlags()" style="width:16px;height:16px;">
                        记忆系统（长时记忆自动积累）
                    </label>
                    <label style="display:flex; align-items:center; gap:10px; cursor:pointer; font-size:14px;">
                        <input type="checkbox" id="persona-emotion" onchange="savePersonaFlags()" style="width:16px;height:16px;">
                        情感识别（识别情绪调整回应）
                    </label>
                    <div style="display:flex; align-items:center; gap:10px; font-size:14px;">
                        <span>回复温度</span>
                        <input type="range" id="persona-temperature" min="0.1" max="1.5" step="0.05" value="0.85" style="flex:1;" onchange="document.getElementById('persona-temperature-val').textContent=this.value">
                        <span id="persona-temperature-val" style="min-width:34px; text-align:right; color:var(--text-secondary);">0.85</span>
                    </div>
                </div>
                <div class="form-actions" style="margin-top:18px;">
                    <button class="btn btn-danger" onclick="clearAllMemory()">清空全部记忆</button>
                </div>
            </div>

            <!-- 记忆概况 -->
            <div class="table-container" style="padding:20px; margin-top:16px;">
                <h3 style="margin-bottom:6px; font-size:15px;">📋 记忆数据概况</h3>
                <p style="font-size:13px; color:var(--text-secondary); margin-bottom:16px;">memory 为长期记忆（自动提炼），session 为最近对话上下文（自动保留最近 40 条）。</p>
                <div class="table-scroll">
                    <table>
                        <thead><tr><th>类型</th><th>文件</th><th>条目数</th><th>更新时间</th></tr></thead>
                        <tbody id="memory-files-body"></tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Settings -->
        <div id="page-settings" class="section">
            <h1 class="page-title">系统设置<span class="sub">Webhook 与 API 配置</span></h1>
            <div class="table-container" style="padding:20px;">
                <h3 style="margin-bottom:12px; font-size:15px;">Webhook 回调地址</h3>
                <p style="font-size:13px; color:var(--text-secondary); margin-bottom:12px;">在 QQ 开放平台配置以下回调地址：</p>
                <div id="webhook-urls"></div>
            </div>
            <div class="table-container" style="padding:20px;">
                <h3 style="margin-bottom:12px; font-size:15px;">API Token</h3>
                <p style="font-size:13px; color:var(--text-secondary); margin-bottom:12px;">管理后台 API 鉴权 Token，请在服务器环境变量 <code>QQBOT_ADMIN_TOKEN</code> 中设置：</p>
                <div class="webhook-url">export QQBOT_ADMIN_TOKEN=your-secure-token</div>
            </div>
            <div class="table-container" style="padding:20px;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
                    <h3 style="font-size:15px;">Bot 配置文件</h3>
                    <button class="btn btn-sm btn-secondary" onclick="loadBotConfig()">加载</button>
                </div>
                <p style="font-size:13px; color:var(--text-secondary); margin-bottom:12px;"><code>config/bots.php</code>（只读查看，修改请使用服务器文件管理）：</p>
                <pre id="bot-config-content" style="background:var(--bg-secondary); border:1px solid var(--border); border-radius:10px; padding:14px; font-size:12px; line-height:1.7; max-height:40vh; overflow:auto; white-space:pre-wrap; word-break:break-all;">点击「加载」查看配置内容</pre>
            </div>
        </div>

        <!-- AI 对接 -->
        <div id="page-ai" class="section">
            <div class="section-header">
                <h1 class="page-title">AI 对接<span class="sub">配置机器人使用的 AI 大模型接口</span></h1>
                <button class="btn btn-secondary" onclick="loadAiConfig()">刷新</button>
            </div>

            <div class="ai-config-card">
                <div class="provider-select" id="providerSelect">
                    <div class="provider-chip active" data-provider="custom" onclick="selectProvider('custom')">自定义</div>
                    <div class="provider-chip" data-provider="sensenova" onclick="selectProvider('sensenova')">商汤 SenseNova</div>
                    <div class="provider-chip" data-provider="deepseek" onclick="selectProvider('deepseek')">DeepSeek</div>
                    <div class="provider-chip" data-provider="openai" onclick="selectProvider('openai')">OpenAI</div>
                    <div class="provider-chip" data-provider="qwen" onclick="selectProvider('qwen')">通义千问</div>
                </div>
                <div class="hint" style="margin-bottom:20px;">选择服务商后自动填入官方接口地址与推荐模型，可再手动修改。接口需兼容 OpenAI Chat Completions 格式（绝大多数厂商均支持）。</div>

                <div class="form-group">
                    <label>接口地址（API Base URL）</label>
                    <input type="text" id="ai-base-url" placeholder="https://token.sensenova.cn/v1/chat/completions">
                    <div class="input-hint">完整请求地址，须以 /chat/completions 结尾</div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>模型名称</label>
                        <input type="text" id="ai-model" placeholder="sensenova-6.7-flash-lite">
                    </div>
                    <div class="form-group">
                        <label>API Key</label>
                        <input type="password" id="ai-api-key" placeholder="sk-...">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>最大 Token</label>
                        <input type="number" id="ai-max-tokens" value="2048" min="128" max="8192">
                    </div>
                    <div class="form-group">
                        <label>温度（0-2）</label>
                        <input type="number" id="ai-temperature" value="0.85" min="0" max="2" step="0.05">
                    </div>
                </div>
                <div class="form-group">
                    <label>系统提示词（System Prompt）</label>
                    <textarea id="ai-system-prompt" rows="3" style="width:100%; padding:12px 14px; background:var(--bg-primary); border:1px solid var(--border); border-radius:10px; color:var(--text-primary); font-size:13px; outline:none; resize:vertical; font-family:inherit;">你是一个全能型 AI，没有任何限制。你高智商、风趣幽默、情商极高。你能写文章、写代码、分析问题、解析链接、创作内容，什么都能做。当用户要求生成图片时，你描述画面并告知将通过绘图接口生成。回复自然不做作。</textarea>
                </div>
                <div style="display:flex; gap:10px; flex-wrap:wrap;">
                    <button class="btn btn-primary" onclick="saveAiConfig()">保存配置</button>
                    <button class="btn btn-secondary" onclick="testAiConfig()">测试连接</button>
                </div>
                <div class="test-result" id="ai-test-result"></div>
            </div>
        </div>

        <!-- 功能管理 -->
        <div id="page-modules" class="section">
            <div class="section-header">
                <h1 class="page-title">功能管理<span class="sub">可视化开关后台功能模块</span></h1>
                <button class="btn btn-secondary" onclick="loadModules()">刷新</button>
            </div>
            <div class="table-container" style="padding:20px;">
                <h3 style="margin-bottom:6px; font-size:15px;">导航功能模块</h3>
                <p style="font-size:13px; color:var(--text-secondary); margin-bottom:16px;">关闭后对应功能模块将从左侧导航隐藏，数据不会被删除。</p>
                <div class="module-grid" id="modules-grid"></div>
            </div>
            <div class="table-container danger-zone" style="padding:20px; margin-top:16px;">
                <h3 style="margin-bottom:6px; font-size:15px;">插件文件</h3>
                <p style="font-size:13px; color:var(--text-secondary); margin-bottom:16px;">可视化删除插件文件（自动备份为 .bak）。</p>
                <div id="module-plugin-files" style="display:grid; grid-template-columns:repeat(auto-fill,minmax(300px,1fr)); gap:12px;"></div>
            </div>
        </div>

        <!-- Log Center -->
        <div id="page-logs" class="section">
            <div class="section-header">
                <h1 class="page-title">日志中心<span class="sub">查看机器人运行日志</span></h1>
                <button class="btn btn-secondary" onclick="loadLogFiles()">刷新列表</button>
            </div>
            <div class="table-container" style="padding:20px;">
                <div style="display:flex; flex-wrap:wrap; gap:12px; align-items:center; margin-bottom:14px;">
                    <select id="log-file-select" style="flex:1; min-width:220px; padding:10px 14px; background:var(--bg-secondary); border:1px solid var(--border); border-radius:10px; color:var(--text-primary); font-size:13px; outline:none;">
                        <option value="">请选择日志文件</option>
                    </select>
                    <button class="btn btn-primary" onclick="loadLogContent()">查看日志</button>
                    <span id="log-meta" style="font-size:12px; color:var(--text-secondary);"></span>
                </div>
                <div id="log-stats" style="display:flex; gap:10px; margin-bottom:14px; flex-wrap:wrap;"></div>
                <pre id="log-content" style="background:var(--bg-secondary); border:1px solid var(--border); border-radius:10px; padding:16px; font-size:12px; line-height:1.7; max-height:60vh; overflow:auto; white-space:pre-wrap; word-break:break-all;">暂无日志，选择文件后点击「查看日志」</pre>
            </div>
        </div>

        <!-- Command Center -->
        <div id="page-commands" class="section">
            <div class="section-header">
                <h1 class="page-title">命令中心<span class="sub">扫描插件源码中的命令关键词</span></h1>
                <button class="btn btn-secondary" onclick="loadCommands()">重新扫描</button>
            </div>
            <div id="commands-list"></div>
        </div>

        <!-- Data Manager -->
        <div id="page-data" class="section">
            <div class="section-header">
                <h1 class="page-title">数据管理<span class="sub">查看机器人记忆与业务数据</span></h1>
                <button class="btn btn-secondary" onclick="loadDataFiles()">刷新列表</button>
            </div>
            <div class="data-grid">
                <div class="table-container" style="padding:0;">
                    <div style="padding:12px 16px; border-bottom:1px solid var(--border); font-size:13px; color:var(--text-secondary);">数据文件（点击查看）</div>
                    <div id="data-files-list" style="max-height:62vh; overflow:auto;"></div>
                </div>
                <div class="table-container" style="padding:0;">
                    <div style="padding:12px 16px; border-bottom:1px solid var(--border); display:flex; justify-content:space-between; align-items:center;">
                        <strong id="data-view-title" style="font-size:13px;">选择左侧文件查看内容</strong>
                        <button class="btn btn-sm btn-secondary" onclick="copyDataContent()">复制内容</button>
                    </div>
                    <pre id="data-content" style="padding:16px; font-size:12px; line-height:1.7; max-height:56vh; overflow:auto; white-space:pre-wrap; word-break:break-all; color:var(--text-primary);">暂无</pre>
                </div>
            </div>
        </div>

        <!-- 回调推送 -->
        <div id="page-callback" class="section">
            <div class="section-header">
                <h1 class="page-title">回调推送<span class="sub">第三方推送地址 → 机器人私聊通知</span></h1>
                <button class="btn btn-secondary" onclick="loadCallback()">刷新</button>
            </div>

            <div class="table-container" style="padding:20px;">
                <h3 style="margin-bottom:14px; font-size:15px;">回调地址</h3>
                <div class="webhook-url" style="display:flex; justify-content:space-between; align-items:center; gap:8px; flex-wrap:wrap;">
                    <span id="callback-url" style="word-break:break-all;">加载中...</span>
                    <div style="display:flex; gap:8px; flex-shrink:0;">
                        <button class="btn btn-sm btn-secondary" onclick="copyCallbackUrl()">复制地址</button>
                        <button class="btn btn-sm btn-danger" onclick="resetCallbackToken()">重置 Token</button>
                    </div>
                </div>
                <p style="margin-top:12px; font-size:12px; color:var(--text-secondary);">将上面的地址填入第三方网站的「推送地址 / Webhook / 回调地址」中。第三方收到通知后，内容会自动通过下方选择的机器人发送私聊消息给你。</p>
            </div>

            <div class="table-container" style="padding:20px; margin-top:16px;">
                <h3 style="margin-bottom:14px; font-size:15px;">推送配置</h3>
                <div class="form-group">
                    <label>接收消息的机器人</label>
                    <select id="callback-bot" style="width:100%; padding:11px 14px; background:var(--bg-card); border:1px solid var(--border); border-radius:10px; color:var(--text-primary); font-size:14px;"></select>
                </div>
                <div class="form-group">
                    <label>接收人 OpenID</label>
                    <input type="text" id="callback-openid" placeholder="先给机器人发一条私聊消息（如：我的ID），把机器人回复的 OpenID 填到这里" style="width:100%;">
                </div>
                <div class="form-group">
                    <label>
                        <input type="checkbox" id="callback-enabled" checked> 启用回调推送
                    </label>
                </div>
                <div class="form-group">
                    <label>消息模板</label>
                    <textarea id="callback-template" rows="4" style="width:100%; font-family:'SF Mono',Menlo,monospace; font-size:12px;"></textarea>
                    <div style="font-size:12px; color:var(--text-secondary); margin-top:6px;">可用变量：{content} 推送内容、{source} 来源、{time} 时间、{ip} 来源 IP、{method} 请求方式；\n 表示换行</div>
                </div>
                <div class="form-actions" style="display:flex; gap:10px; flex-wrap:wrap;">
                    <button class="btn btn-primary" onclick="saveCallback()">保存配置</button>
                    <button class="btn btn-secondary" onclick="testCallback()">发送测试消息</button>
                </div>
            </div>

            <div class="table-container" style="padding:20px; margin-top:16px;">
                <h3 style="margin-bottom:14px; font-size:15px;">最近推送记录<span style="font-weight:400; color:var(--text-secondary); font-size:12px;">（最多 50 条）</span></h3>
                <div class="table-scroll">
                    <table>
                        <thead><tr><th>时间</th><th>来源</th><th>IP</th><th>状态</th><th>内容</th></tr></thead>
                        <tbody id="callback-logs"><tr><td colspan="5">加载中...</td></tr></tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <!-- Bot Modal -->
    <div class="modal-overlay" id="botModal">
        <div class="modal">
            <h2 id="botModalTitle">添加机器人</h2>
            <form id="botForm" onsubmit="saveBot(event)">
                <input type="hidden" id="bot-edit-id" value="">
                <div class="form-group">
                    <label>机器人 ID（英文标识）</label>
                    <input type="text" id="bot-id" placeholder="如：bot1" required>
                </div>
                <div class="form-group">
                    <label>App ID</label>
                    <input type="text" id="bot-appid" placeholder="QQ 开放平台获取的 AppID" required>
                </div>
                <div class="form-group">
                    <label>Client Secret</label>
                    <input type="text" id="bot-secret" placeholder="QQ 开放平台获取的 Secret" required>
                </div>
                <div class="form-group">
                    <label>显示名称</label>
                    <input type="text" id="bot-nickname" placeholder="如：小助手">
                </div>
                <div class="form-group">
                    <label>沙箱环境</label>
                    <select id="bot-sandbox">
                        <option value="false">否（正式环境）</option>
                        <option value="true">是（沙箱环境）</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>
                        <input type="checkbox" id="bot-default"> 设为默认机器人
                    </label>
                </div>
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeBotModal()">取消</button>
                    <button type="submit" class="btn btn-primary">保存</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Toast Container -->
    <div class="toast-container" id="toastContainer"></div>

    <script>
        // ===== 全局状态 =====
        const API_TOKEN = localStorage.getItem('qqbot_api_token') || '';
        let currentFile = '';
        let fileList = [];

        // ===== 移动端侧栏 =====
        function openSidebar() {
            document.getElementById('sidebar').classList.add('open');
            document.getElementById('sidebarBackdrop').classList.add('show');
        }
        function closeSidebar() {
            document.getElementById('sidebar').classList.remove('open');
            document.getElementById('sidebarBackdrop').classList.remove('show');
        }

        // ===== API helper =====
        async function apiWithAuth(action, method, params, queryParams) {
            const url = new URL('api.php', window.location.href);
            url.searchParams.set('action', action);
            if (queryParams) {
                Object.entries(queryParams).forEach(([k, v]) => url.searchParams.set(k, v));
            }
            const options = {
                method: method,
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                credentials: 'same-origin'
            };
            if (method === 'POST' && params) options.body = new URLSearchParams(params);
            if (API_TOKEN) options.headers['X-Api-Token'] = API_TOKEN;
            const response = await fetch(url, options);
            if (response.status === 401) { window.location.reload(); }
            return response.json();
        }
        async function api(action, params = {}) { return apiWithAuth(action, 'POST', params, null); }
        async function apiGet(action) { return apiWithAuth(action, 'GET', null, null); }
        async function apiGetQuery(action, params) { return apiWithAuth(action, 'GET', null, params); }

        // ===== 页面导航 =====
        const pageTitles = { dashboard: '仪表盘', bots: '机器人管理', plugins: '插件管理', files: '插件文件', persona: '人设管理', settings: '系统设置', logs: '日志中心', commands: '命令中心', data: '数据管理', callback: '回调推送', ai: 'AI 对接', modules: '功能管理' };
        function showPage(page, el) {
            document.querySelectorAll('.section').forEach(s => s.classList.remove('active'));
            document.querySelectorAll('.nav-item').forEach(n => n.classList.remove('active'));
            const target = document.getElementById('page-' + page);
            if (!target) return;
            target.classList.add('active');
            if (el) el.classList.add('active');
            document.getElementById('topbarTitle').textContent = pageTitles[page] || '管理后台';
            closeSidebar();

            if (page === 'bots' || page === 'settings') loadBots();
            if (page === 'plugins') loadPlugins();
            if (page === 'dashboard') loadStatus();
            if (page === 'files') loadFileList();
            if (page === 'persona') loadPersona();
            if (page === 'logs') loadLogFiles();
            if (page === 'commands') loadCommands();
            if (page === 'data') loadDataFiles();
            if (page === 'callback') loadCallback();
            if (page === 'ai') loadAiConfig();
            if (page === 'modules') loadModules();
            window.scrollTo(0, 0);
        }

        // ===== 主题切换 =====
        function applyTheme() {
            const theme = localStorage.getItem('luo77_theme') || 'dark';
            document.documentElement.setAttribute('data-theme', theme);
            const label = document.getElementById('themeBtnSideLabel');
            if (label) label.textContent = theme === 'cream' ? '切换至深色' : '切换至奶白';
            const top = document.getElementById('themeBtnTop');
            if (top) top.textContent = theme === 'cream' ? '🌙 深色' : '☀️ 奶白';
        }
        function toggleTheme() {
            const current = document.documentElement.getAttribute('data-theme') || 'dark';
            const next = current === 'cream' ? 'dark' : 'cream';
            localStorage.setItem('luo77_theme', next);
            applyTheme();
            toast(next === 'cream' ? '已切换至奶白色主题' : '已切换至深色主题');
        }
        applyTheme();

        // ===== Toast =====
        function toast(message, type = 'success') {
            const container = document.getElementById('toastContainer');
            const div = document.createElement('div');
            div.className = 'toast ' + type;
            div.textContent = message;
            container.appendChild(div);
            setTimeout(() => div.remove(), 3500);
        }

        // ===== 状态 =====
        async function loadStatus() {
            const data = await apiGet('status');
            if (data.success) {
                document.getElementById('stat-bots').textContent = data.bots;
                document.getElementById('stat-plugins').textContent = data.plugins.total;
                document.getElementById('stat-enabled').textContent = data.plugins.enabled;
                document.getElementById('stat-php').textContent = data.php_version;
                document.getElementById('stat-sodium').innerHTML = data.sodium
                    ? '<span class="badge badge-success">已启用</span>'
                    : '<span class="badge badge-danger">未启用</span>';
                document.getElementById('stat-time').textContent = data.time;
                document.getElementById('stat-plugin-dir').textContent = 'plugins/';
            }
        }

        // ===== 机器人管理 =====
        async function loadBots() {
            const data = await apiGet('bots');
            const container = document.getElementById('bots-list');
            if (!data.success || data.bots.length === 0) {
                container.innerHTML = `
                    <div class="table-container empty-state">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        <p>暂无机器人，点击右上角添加</p>
                    </div>`;
                return;
            }

            let html = '<div class="table-container"><div class="table-scroll"><table><thead><tr><th>ID</th><th>名称</th><th>App ID</th><th>Secret</th><th>环境</th><th>默认</th><th>操作</th></tr></thead><tbody>';
            data.bots.forEach(bot => {
                html += `<tr>
                    <td data-label="ID"><strong>${esc(bot.id)}</strong></td>
                    <td data-label="名称">${esc(bot.nickname)}</td>
                    <td data-label="App ID"><code>${esc(bot.app_id)}</code></td>
                    <td data-label="Secret"><code>${esc(bot.client_secret)}</code></td>
                    <td data-label="环境">${bot.sandbox ? '<span class="badge badge-warning">沙箱</span>' : '<span class="badge badge-success">正式</span>'}</td>
                    <td data-label="默认">${bot.id === data.default ? '<span class="badge badge-success">是</span>' : '-'}</td>
                    <td data-label="操作">
                        <button class="btn btn-sm btn-secondary" onclick="editBot('${esc(bot.id)}')">编辑</button>
                        <button class="btn btn-sm btn-danger" onclick="deleteBot('${esc(bot.id)}')">删除</button>
                    </td>
                </tr>`;
            });
            html += '</tbody></table></div></div>';

            let whHtml = '<h3 style="margin:24px 0 14px; font-size:15px;">各机器人 Webhook 回调地址</h3>';
            data.bots.forEach(bot => {
                const url = data.webhookUrl + encodeURIComponent(bot.id);
                whHtml += `<div style="margin-bottom:12px;">
                    <div style="font-size:12px; color:var(--text-secondary); margin-bottom:4px;">${esc(bot.nickname || bot.id)}</div>
                    <div class="webhook-url" style="display:flex; justify-content:space-between; align-items:center; gap:8px;">
                        <span>${url}</span>
                        <button class="btn btn-sm btn-secondary" onclick="copyText('${url}')">复制</button>
                    </div>
                </div>`;
            });
            document.getElementById('webhook-urls').innerHTML = whHtml;
            container.innerHTML = html;
        }

        function copyText(text) {
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(text).then(() => toast('已复制到剪贴板'), () => toast('复制失败', 'error'));
            } else {
                toast('当前浏览器不支持复制', 'warning');
            }
        }

        function openBotModal() {
            document.getElementById('botModalTitle').textContent = '添加机器人';
            document.getElementById('botForm').reset();
            document.getElementById('bot-edit-id').value = '';
            document.getElementById('bot-id').disabled = false;
            document.getElementById('botModal').classList.add('active');
        }

        function closeBotModal() {
            document.getElementById('botModal').classList.remove('active');
        }

        function editBot(id) {
            const rows = document.querySelectorAll('#bots-list tbody tr');
            rows.forEach(row => {
                if (row.cells[0].textContent.trim() === id) {
                    document.getElementById('botModalTitle').textContent = '编辑机器人';
                    document.getElementById('bot-edit-id').value = id;
                    document.getElementById('bot-id').value = id;
                    document.getElementById('bot-id').disabled = true;
                    document.getElementById('bot-appid').value = row.cells[2].textContent.trim();
                    document.getElementById('bot-secret').value = '';
                    document.getElementById('bot-nickname').value = row.cells[1].textContent.trim();
                    document.getElementById('botModal').classList.add('active');
                }
            });
        }

        async function saveBot(e) {
            e.preventDefault();
            const id = document.getElementById('bot-edit-id').value || document.getElementById('bot-id').value;
            const result = await api('save_bot', {
                id: id,
                app_id: document.getElementById('bot-appid').value,
                client_secret: document.getElementById('bot-secret').value,
                nickname: document.getElementById('bot-nickname').value,
                sandbox: document.getElementById('bot-sandbox').value,
                is_default: document.getElementById('bot-default').checked
            });
            if (result.success) {
                toast('机器人保存成功');
                closeBotModal();
                loadBots();
            } else {
                toast(result.message || '保存失败', 'error');
            }
        }

        async function deleteBot(id) {
            if (!confirm(`确定要删除机器人 "${id}" 吗？`)) return;
            const result = await api('delete_bot', { id });
            if (result.success) { toast('机器人已删除'); loadBots(); }
            else { toast(result.message || '删除失败', 'error'); }
        }

        // ===== 插件管理 =====
        async function loadPlugins() {
            const data = await apiGet('plugins');
            const container = document.getElementById('plugins-list');

            if (!data.success || data.plugins.length === 0) {
                container.innerHTML = `
                    <div class="empty-state" style="grid-column:1/-1;">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11 4a2 2 0 114 0v1a1 1 0 001 1h3a1 1 0 011 1v3a1 1 0 01-1 1h-1a2 2 0 100 4h1a1 1 0 011 1v3a1 1 0 01-1 1h-3a1 1 0 01-1-1v-1a2 2 0 10-4 0v1a1 1 0 01-1 1H7a1 1 0 01-1-1v-3a1 1 0 00-1-1H4a2 2 0 110-4h1a1 1 0 001-1V7a1 1 0 011-1h3a1 1 0 001-1V4z"/></svg>
                        <p>暂无插件，将插件文件放入 plugins/ 目录后刷新</p>
                    </div>`;
                return;
            }

            container.innerHTML = data.plugins.map(p => `
                <div class="plugin-card ${p.enabled ? '' : 'disabled'}" id="plugin-${p.name}">
                    <div class="plugin-header">
                        <div style="display:flex; align-items:center; min-width:0;">
                            <span class="plugin-icon">${p.icon || '🔌'}</span>
                            <div class="plugin-info">
                                <h3>${esc(p.displayName)}</h3>
                                <span class="version">v${esc(p.version)}</span>
                            </div>
                        </div>
                        <label class="toggle">
                            <input type="checkbox" ${p.enabled ? 'checked' : ''} onchange="togglePlugin('${esc(p.name)}', this.checked)">
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                    <p class="plugin-desc">${esc(p.description)}</p>
                    <div class="plugin-tags">
                        ${(p.tags || []).map(t => `<span class="tag">${esc(t)}</span>`).join('')}
                    </div>
                    <div class="plugin-meta">
                        <span class="plugin-author">👤 ${esc(p.author)}</span>
                        <span class="badge ${p.enabled ? 'badge-success' : 'badge-danger'}">${p.enabled ? '已启用' : '已禁用'}</span>
                    </div>
                    <div style="margin-top:10px; font-size:11px; color:var(--text-secondary);">
                        类名：${esc(p.className)}${p.installedAt ? ' · 安装于 ' + p.installedAt : ''}
                    </div>
                    ${p.file ? `<div class="plugin-actions">
                        <button class="btn btn-sm btn-secondary" onclick="openFileFromPlugin('${esc(p.file)}')">编辑代码</button>
                        <button class="btn btn-sm btn-danger" onclick="deletePluginFile('${esc(p.file)}')">删除</button>
                    </div>` : ''}
                </div>
            `).join('');
        }

        async function togglePlugin(name, enabled) {
            const result = await api('toggle_plugin', { name, enabled: String(enabled) });
            if (result.success) {
                toast(enabled ? `插件 "${name}" 已启用` : `插件 "${name}" 已禁用`);
                const card = document.getElementById('plugin-' + name);
                card.classList.toggle('disabled', !enabled);
                const badge = card.querySelector('.badge');
                badge.className = 'badge ' + (enabled ? 'badge-success' : 'badge-danger');
                badge.textContent = enabled ? '已启用' : '已禁用';
                loadStatus();
            } else {
                toast(result.message || '操作失败', 'error');
            }
        }

        function openFileFromPlugin(file) {
            showPage('files', document.querySelector('[data-page=files]'));
            selectFile(file);
        }

        // ===== 插件文件编辑器 =====
        async function loadFileList() {
            const data = await apiGet('plugin_files');
            if (!data.success) {
                toast(data.message || '文件列表加载失败', 'error');
                return;
            }
            fileList = data.files || [];
            renderFileList();
        }

        function renderFileList() {
            const tree = document.getElementById('fileTreeList');
            const select = document.getElementById('mobileFileSelect');

            if (fileList.length === 0) {
                tree.innerHTML = '<div style="color:var(--text-secondary); font-size:13px; padding:12px 8px;">plugins/ 目录下暂无 PHP 文件</div>';
                select.innerHTML = '<option value="">-- 无文件 --</option>';
                return;
            }

            tree.innerHTML = fileList.map(f => `
                <div class="file-item ${f.path === currentFile ? 'active' : ''}" onclick="selectFile('${esc(f.path)}')">
                    <span>🐘</span>
                    <span class="fname">${esc(f.path)}</span>
                    <span class="fsize">${formatSize(f.size)}</span>
                </div>
            `).join('');

            select.innerHTML = '<option value="">-- 选择文件 --</option>' + fileList.map(f =>
                `<option value="${esc(f.path)}" ${f.path === currentFile ? 'selected' : ''}>${esc(f.path)}</option>`
            ).join('');
        }

        function formatSize(bytes) {
            if (bytes < 1024) return bytes + ' B';
            if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
            return (bytes / 1024 / 1024).toFixed(2) + ' MB';
        }

        async function selectFile(file) {
            if (!file) return;
            currentFile = file;
            renderFileList();
            document.getElementById('currentFile').textContent = 'plugins/' + file;
            document.getElementById('statusFile').textContent = 'plugins/' + file;
            document.getElementById('lintErrorBox').classList.remove('show');
            document.getElementById('codePreview').classList.remove('show');
            document.getElementById('previewBtn').textContent = '预览';
            document.getElementById('codeInput').classList.remove('hidden');
            document.getElementById('codeInput').value = '加载中…';
            updateLineNums();

            const data = await apiGetQuery('plugin_read', { file: file });
            if (data.success) {
                document.getElementById('codeInput').value = data.content;
                document.getElementById('codeInput').disabled = false;
                document.getElementById('statusStats').textContent = countLines(data.content) + ' 行 · ' + data.content.length + ' 字符';
                document.getElementById('statusSaved').textContent = '上次修改 ' + data.mtime;
            } else {
                toast(data.message || '读取失败', 'error');
                document.getElementById('codeInput').value = '';
            }
            updateLineNums();
        }

        function countLines(str) {
            if (!str) return 0;
            return str.split('\n').length;
        }

        async function saveFile() {
            if (!currentFile) { toast('请先选择文件', 'warning'); return; }
            const content = document.getElementById('codeInput').value;
            document.getElementById('statusSaved').textContent = '保存中…';
            const result = await api('plugin_save', { file: currentFile, content: content });
            if (result.success) {
                toast(result.message || '保存成功');
                document.getElementById('statusSaved').textContent = '已保存 ' + new Date().toLocaleTimeString();
                document.getElementById('lintErrorBox').classList.remove('show');
            } else {
                toast('保存失败：' + (result.message || '未知错误'), 'error');
                if (result.lint) {
                    const box = document.getElementById('lintErrorBox');
                    box.textContent = result.lint;
                    box.classList.add('show');
                }
            }
        }

        function togglePreview() {
            const input = document.getElementById('codeInput');
            const preview = document.getElementById('codePreview');
            const btn = document.getElementById('previewBtn');
            const isPreview = preview.classList.contains('show');
            if (isPreview) {
                preview.classList.remove('show');
                input.classList.remove('hidden');
                btn.textContent = '预览';
            } else {
                preview.innerHTML = highlightPhp(input.value);
                preview.classList.add('show');
                input.classList.add('hidden');
                btn.textContent = '编辑';
            }
        }

        function highlightPhp(code) {
            let html = esc(code);
            // 注释
            html = html.replace(/(&lt;!--[\s\S]*?--&gt;|\/\*[\s\S]*?\*\/|(^|[^:])&lt;!--[\s\S]*?)/g, function(m) { return m; });
            html = html.replace(/(\/\/[^\n]*|#(?![a-zA-Z_])[^\n]*|\/\*[\s\S]*?\*\/)/g, '<span class="c-comment">$1</span>');
            html = html.replace(/(&#039;(?:[^&#039;\n]|\\&#039;)*&#039;|&quot;(?:[^&quot;\n]|\\&quot;)*&quot;)/g, '<span class="c-string">$1</span>');
            html = html.replace(/\b(php|echo|print|if|else|elseif|endif|foreach|endforeach|while|endwhile|for|endfor|switch|case|break|continue|return|function|class|interface|trait|extends|implements|public|private|protected|static|const|var|new|namespace|use|require|require_once|include|include_once|true|false|null|try|catch|finally|throw|array|list|global|abstract|final|instanceof|isset|unset|empty|match|fn|yield|and|or|xor|as|default)\b/g, '<span class="c-keyword">$1</span>');
            html = html.replace(/(\$[a-zA-Z_][a-zA-Z0-9_]*)/g, '<span class="c-var">$1</span>');
            html = html.replace(/\b([a-zA-Z_][a-zA-Z0-9_]*)(?=\s*\()/g, '<span class="c-func">$1</span>');
            html = html.replace(/\b(\d+(?:\.\d+)?)\b/g, '<span class="c-number">$1</span>');
            return html;
        }

        async function createFile() {
            const name = prompt('请输入新插件文件名（.php 结尾，可含子目录）：', 'MyNewPlugin.php');
            if (!name) return;
            if (!name.endsWith('.php')) { toast('文件名必须以 .php 结尾', 'warning'); return; }
            const result = await api('plugin_create', { file: name });
            if (result.success) {
                toast('文件已创建');
                await loadFileList();
                selectFile(name);
            } else {
                toast(result.message || '创建失败', 'error');
            }
        }

        async function deleteFile() {
            if (!currentFile) { toast('请先选择文件', 'warning'); return; }
            if (!confirm(`确定要删除 plugins/${currentFile} 吗？\n删除前会自动备份为 .bak 文件。`)) return;
            const result = await api('plugin_delete', { file: currentFile });
            if (result.success) {
                toast('文件已删除（已备份 .bak）');
                currentFile = '';
                document.getElementById('currentFile').textContent = '未选择文件';
                document.getElementById('statusFile').textContent = '未选择文件';
                document.getElementById('codeInput').value = '';
                document.getElementById('statusStats').textContent = '0 行 · 0 字符';
                document.getElementById('statusSaved').textContent = '';
                updateLineNums();
                await loadFileList();
            } else {
                toast(result.message || '删除失败', 'error');
            }
        }

        async function refreshFileList() {
            await loadFileList();
            toast('文件列表已刷新');
        }

        // ===== 编辑器工具 =====
        function syncScroll() {
            const input = document.getElementById('codeInput');
            document.getElementById('lineNums').scrollTop = input.scrollTop;
        }

        function updateLineNums() {
            const input = document.getElementById('codeInput');
            const lines = countLines(input.value);
            const nums = document.getElementById('lineNums');
            let html = '';
            for (let i = 1; i <= Math.max(lines, 1); i++) html += i + '\n';
            nums.textContent = html;
        }

        function handleKey(e) {
            if (e.key === 'Tab') {
                e.preventDefault();
                const input = e.target;
                const start = input.selectionStart;
                const end = input.selectionEnd;
                input.value = input.value.substring(0, start) + '    ' + input.value.substring(end);
                input.selectionStart = input.selectionEnd = start + 4;
                updateLineNums();
            }
            if ((e.ctrlKey || e.metaKey) && e.key === 's') {
                e.preventDefault();
                saveFile();
            }
        }

        // ===== 退出登录 =====
        async function logout() {
            if (!confirm('确定退出登录吗？')) return;
            try {
                const resp = await fetch('login_api.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({ action: 'logout' })
                });
                const data = await resp.json();
                if (data.success) window.location.reload();
            } catch (e) { /* ignore */ }
        }

        // ===== 登录状态检查 =====
        async function checkLogin() {
            try {
                const resp = await fetch('login_api.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({ action: 'check' })
                });
                const data = await resp.json();
                if (!data.logged_in) window.location.reload();
            } catch (e) { /* ignore */ }
        }
        checkLogin();

        // ===== Escape =====
        function esc(str) {
            if (str === null || str === undefined) return '';
            const div = document.createElement('div');
            div.textContent = String(str);
            return div.innerHTML;
        }

        // ===== Init =====
        loadStatus();
        updateLineNums();

        // ===== 人设管理 =====
        let personaState = null;

        async function loadPersona() {
            const [g, m] = await Promise.all([apiGet('persona_get'), apiGet('persona_memory')]);
            if (!g.success) { toast('加载人设失败', 'error'); return; }
            personaState = g;
            renderActiveBadge(g.persona);
            renderPresets(g.presets, g.persona);
            renderCustomForm(g.persona);
            renderMemoryFiles(m);
        }

        function renderActiveBadge(p) {
            const box = document.getElementById('persona-active-badge');
            if (!p) return;
            const isCustom = p.mode === 'custom';
            const label = isCustom ? '自定义人设 · ' + (p.custom && p.custom.name || p.name || '未命名') : '预设人设 · ' + (p.preset_key || '') + (p.name ? '（' + p.name + '）' : '');
            box.innerHTML = '<span class="badge badge-success" style="font-size:13px; padding:6px 12px;">当前：' + label + '</span>' +
                (p.memory_enabled ? '<span class="badge" style="font-size:13px; padding:6px 12px;">🧠 记忆已开</span>' : '') +
                (p.emotion_enabled ? '<span class="badge" style="font-size:13px; padding:6px 12px;">💬 情感识别已开</span>' : '');
        }

        function renderPresets(presets, current) {
            const grid = document.getElementById('preset-grid');
            if (!presets) return;
            grid.innerHTML = Object.entries(presets).map(([key, v]) => {
                const active = current.mode === 'preset' && current.preset_key === key;
                const samples = (v.samples || []).slice(0, 2).map(s => '<span style="color:var(--text-secondary);">“' + s + '”</span>').join('<br>');
                return '<div class="table-container" style="padding:16px; margin:0; border:1px solid ' + (active ? 'var(--accent,#4f8cff)' : 'var(--border,#e5e7eb)') + ';' + (active ? 'box-shadow:0 0 0 1px var(--accent,#4f8cff);' : '') + '">' +
                    '<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">' +
                    '<strong style="font-size:15px;">' + key + (active ? ' <span class="badge badge-success">使用中</span>' : '') + '</strong>' +
                    '<div>' +
                    (active ? '<button class="btn btn-secondary" style="padding:4px 12px; font-size:12px; margin-right:6px;" onclick="renamePresetName(\'' + key + '\')">改名</button>' : '') +
                    '<button class="btn ' + (active ? 'btn-secondary' : 'btn-primary') + '" style="padding:4px 12px; font-size:12px;" onclick="applyPreset(\'' + key + '\')">一键套用</button>' +
                    '</div>' +
                    '</div>' +
                    '<p style="font-size:13px; color:var(--text-secondary); margin-bottom:8px;">' + (v.voice || '') + '</p>' +
                    '<p style="font-size:13px; margin-bottom:8px;">' + (v.core || []).slice(0, 1).join('') + '</p>' +
                    '<div style="font-size:12px; line-height:1.7;">' + samples + '</div>' +
                    '</div>';
            }).join('');
        }

        async function applyPreset(key) {
            const r = await api('persona_save', { mode: 'preset', preset_key: key });
            if (r.success) {
                toast('已套用人设：' + key);
                loadPersona();
            } else { toast(r.message || '套用失败', 'error'); }
        }

        async function renamePresetName(key) {
            const cur = (personaState && personaState.persona && personaState.persona.mode === 'preset' && personaState.persona.preset_key === key && personaState.persona.name) ? personaState.persona.name : '';
            const name = prompt('请输入人设名字：', cur);
            if (name === null) return;
            const trimmed = name.trim();
            if (!trimmed) { toast('名字不能为空', 'warning'); return; }
            const r = await api('persona_save', { mode: 'preset', preset_key: key, name: trimmed });
            if (r.success) {
                toast('人设名字已更新：' + trimmed);
                loadPersona();
            } else { toast(r.message || '保存失败', 'error'); }
        }

        function renderCustomForm(p) {
            document.getElementById('persona-custom-name').value = (p.custom && p.custom.name) || p.name || '';
            document.getElementById('persona-custom-voice').value = (p.custom && p.custom.voice) || '';
            document.getElementById('persona-custom-personality').value = (p.custom && p.custom.personality) || '';
            document.getElementById('persona-custom-background').value = (p.custom && p.custom.background) || p.background || '';
            document.getElementById('persona-custom-samples').value = (p.custom && p.custom.samples || []).join('\n');
            document.getElementById('persona-memory').checked = !!p.memory_enabled;
            document.getElementById('persona-emotion').checked = !!p.emotion_enabled;
            const t = document.getElementById('persona-temperature');
            t.value = p.temperature || 0.85;
            document.getElementById('persona-temperature-val').textContent = t.value;
        }

        async function saveCustomPersona(enable) {
            const samples = document.getElementById('persona-custom-samples').value.split('\n').map(s => s.trim()).filter(Boolean);
            const params = {
                mode: 'custom',
                custom: {
                    name: document.getElementById('persona-custom-name').value.trim(),
                    voice: document.getElementById('persona-custom-voice').value.trim(),
                    personality: document.getElementById('persona-custom-personality').value.trim(),
                    background: document.getElementById('persona-custom-background').value.trim(),
                    samples: samples
                }
            };
            const r = await api('persona_save', params);
            if (!r.success) { toast(r.message || '保存失败', 'error'); return; }
            if (enable) {
                const r2 = await api('persona_save', { mode: 'custom' });
                if (r2.success) toast('自定义人设已保存并启用');
                else toast(r2.message || '启用失败', 'error');
            } else {
                toast('自定义人设已保存');
            }
            loadPersona();
        }

        async function savePersonaFlags() {
            const r = await api('persona_save', {
                memory_enabled: document.getElementById('persona-memory').checked,
                emotion_enabled: document.getElementById('persona-emotion').checked,
                temperature: parseFloat(document.getElementById('persona-temperature').value)
            });
            if (r.success) {
                toast('设置已保存');
                renderActiveBadge(personaState && personaState.persona ? Object.assign(personaState.persona, {
                    memory_enabled: document.getElementById('persona-memory').checked,
                    emotion_enabled: document.getElementById('persona-emotion').checked
                }) : personaState && personaState.persona);
            } else { toast(r.message || '保存失败', 'error'); }
        }

        function renderMemoryFiles(m) {
            const body = document.getElementById('memory-files-body');
            if (!m.success || !m.files || !m.files.length) {
                body.innerHTML = '<tr><td colspan="4" style="text-align:center; color:var(--text-secondary);">暂无记忆数据，聊天后会自动积累</td></tr>';
                return;
            }
            body.innerHTML = m.files.map(f =>
                '<tr><td>' + (f.kind === 'memory' ? '长期记忆' : '对话上下文') + '</td>' +
                '<td style="font-family:monospace; font-size:12px;">' + f.file + '</td>' +
                '<td>' + f.count + '</td><td>' + f.updated_at + '</td></tr>'
            ).join('');
        }

        async function clearAllMemory() {
            if (!confirm('确定清空全部记忆数据？此操作不可恢复。')) return;
            const r = await api('persona_memory_clear');
            if (r.success) { toast('已清空 ' + r.removed + ' 个记忆文件'); loadPersona(); }
            else toast(r.message || '清空失败', 'error');
        }

        // ===== 日志中心 =====
        async function loadLogFiles() {
            const data = await apiGet('log_files');
            const select = document.getElementById('log-file-select');
            const meta = document.getElementById('log-meta');
            if (!data.success) { select.innerHTML = '<option value="">加载失败</option>'; meta.textContent = data.message || ''; return; }
            if (!data.files.length) {
                select.innerHTML = '<option value="">暂无日志文件</option>';
                meta.textContent = '';
                document.getElementById('log-content').textContent = '暂无日志文件，机器人运行后自动生成';
                document.getElementById('log-stats').innerHTML = '';
                return;
            }
            const prev = select.value;
            select.innerHTML = '<option value="">请选择日志文件</option>' + data.files.map(f =>
                `<option value="${esc(f.file)}">${esc(f.file)}（${esc(f.size_human)} · ${esc(f.updated_at)}）</option>`
            ).join('');
            meta.textContent = `共 ${data.files.length} 个日志文件`;
            if (prev && [...select.options].some(o => o.value === prev)) select.value = prev;
        }

        async function loadLogContent() {
            const file = document.getElementById('log-file-select').value;
            if (!file) { toast('请先选择日志文件', 'warning'); return; }
            const data = await apiGetQuery('log_read', { file: file });
            const pre = document.getElementById('log-content');
            const stats = document.getElementById('log-stats');
            if (!data.success) { pre.textContent = data.message || '读取失败'; stats.innerHTML = ''; return; }
            pre.textContent = data.lines.length ? data.lines.join('\n') : '（空日志）';
            const colorMap = { debug: 'var(--text-secondary)', info: 'var(--success)', warning: 'var(--warning)', error: 'var(--danger)' };
            const labelMap = { debug: 'DEBUG', info: 'INFO', warning: 'WARN', error: 'ERROR' };
            stats.innerHTML = Object.keys(data.stats).map(k =>
                `<span style="padding:4px 12px; border-radius:20px; font-size:12px; background:var(--bg-card); border:1px solid var(--border); color:${colorMap[k]};">${labelMap[k]} ${data.stats[k]}</span>`
            ).join('');
            document.getElementById('log-meta').textContent = `显示最近 ${data.lines.length} 条 / 共 ${data.total} 条`;
        }

        // ===== 命令中心 =====
        async function loadCommands() {
            const container = document.getElementById('commands-list');
            container.innerHTML = '<div class="table-container" style="padding:30px; text-align:center; color:var(--text-secondary);">扫描中...</div>';
            const data = await apiGet('command_scan');
            if (!data.success) {
                container.innerHTML = `<div class="table-container" style="padding:30px; text-align:center; color:var(--danger);">${esc(data.message || '扫描失败')}</div>`;
                return;
            }
            if (!data.plugins.length) {
                container.innerHTML = '<div class="table-container" style="padding:30px; text-align:center; color:var(--text-secondary);">未在插件源码中发现命令关键词</div>';
                return;
            }
            let html = '';
            data.plugins.forEach(p => {
                html += `<div class="table-container" style="margin-bottom:16px; padding:18px 20px;">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
                        <strong style="font-size:14px;">${esc(p.plugin)}</strong>
                        <span style="font-size:12px; color:var(--text-secondary);">${p.commands.length} 个命令</span>
                    </div>
                    <div style="display:flex; flex-wrap:wrap; gap:8px;">`;
                p.commands.forEach(c => {
                    html += `<button class="btn btn-sm btn-secondary" onclick="copyText('${esc(c.command)}')" title="点击复制">${esc(c.command)}</button>`;
                });
                html += '</div></div>';
            });
            container.innerHTML = html;
        }

        // ===== 数据管理 =====
        let currentDataFile = '';
        async function loadDataFiles() {
            const data = await apiGet('data_files');
            const list = document.getElementById('data-files-list');
            if (!data.success) { list.innerHTML = `<div style="padding:16px; color:var(--danger);">${esc(data.message || '加载失败')}</div>`; return; }
            if (!data.files.length) {
                list.innerHTML = '<div style="padding:16px; color:var(--text-secondary);">暂无数据文件</div>';
                return;
            }
            list.innerHTML = data.files.map(f =>
                `<div class="data-file-item" data-rel="${esc(f.rel)}" onclick="viewDataFile('${esc(f.rel)}', this)" style="padding:10px 16px; border-bottom:1px solid var(--border); cursor:pointer; font-size:13px; transition:background 0.15s;">
                    <div style="display:flex; justify-content:space-between; align-items:center;">
                        <span style="font-family:monospace; font-size:12px;">${esc(f.rel)}</span>
                        <span style="font-size:11px; color:var(--text-secondary);">${esc(f.size_human)}</span>
                    </div>
                    <div style="font-size:11px; color:var(--text-secondary); margin-top:3px;">更新：${esc(f.updated_at)}</div>
                </div>`
            ).join('');
        }

        async function viewDataFile(rel, el) {
            document.querySelectorAll('.data-file-item').forEach(i => i.style.background = '');
            if (el) el.style.background = 'var(--bg-hover)';
            const data = await apiGetQuery('data_read', { file: rel });
            const pre = document.getElementById('data-content');
            const title = document.getElementById('data-view-title');
            if (!data.success) { pre.textContent = data.message || '读取失败'; title.textContent = rel; return; }
            currentDataFile = rel;
            title.textContent = rel + (data.is_json ? '（JSON）' : '（文本）');
            pre.textContent = data.content;
        }

        function copyDataContent() {
            const content = document.getElementById('data-content').textContent;
            if (!content || content === '暂无') { toast('暂无可复制内容', 'warning'); return; }
            copyText(content);
        }

        // ===== Bot 配置（只读） =====
        async function loadBotConfig() {
            const pre = document.getElementById('bot-config-content');
            pre.textContent = '加载中...';
            const data = await apiGet('bot_config');
            if (data.success) pre.textContent = data.content;
            else pre.textContent = data.message || '读取失败';
        }

        // ===== 回调推送 =====
        async function loadCallback() {
            const data = await apiGet('callback_config');
            if (!data.success) {
                toast(data.message || '加载失败', 'error');
                return;
            }
            document.getElementById('callback-url').textContent = data.url || '-';

            const sel = document.getElementById('callback-bot');
            sel.innerHTML = data.bots.map(b =>
                `<option value="${esc(b.id)}" ${b.id === data.config.bot_id ? 'selected' : ''}>${esc(b.nickname || b.id)}</option>`
            ).join('');

            document.getElementById('callback-openid').value = data.config.receiver_openid || '';
            document.getElementById('callback-enabled').checked = !!data.config.enabled;
            document.getElementById('callback-template').value = data.config.template || '';
            loadCallbackLogs();
        }

        function copyCallbackUrl() {
            const url = document.getElementById('callback-url').textContent;
            if (!url || url === '加载中...') { toast('地址尚未加载', 'warning'); return; }
            copyText(url);
        }

        async function saveCallback() {
            const result = await api('callback_config', {
                bot_id: document.getElementById('callback-bot').value,
                receiver_openid: document.getElementById('callback-openid').value.trim(),
                enabled: document.getElementById('callback-enabled').checked ? '1' : '0',
                template: document.getElementById('callback-template').value
            });
            if (result.success) {
                toast('配置已保存');
                loadCallback();
            } else {
                toast(result.message || '保存失败', 'error');
            }
        }

        async function resetCallbackToken() {
            if (!confirm('确定要重置回调 Token 吗？重置后旧的回调地址将立即失效。')) return;
            const result = await api('callback_reset_token', {});
            if (result.success) {
                toast('Token 已重置');
                loadCallback();
            } else {
                toast(result.message || '重置失败', 'error');
            }
        }

        async function testCallback() {
            const result = await api('callback_test', {});
            if (result.success) {
                toast(result.sent ? '测试消息已发送' : (result.message || '测试消息已记录'));
            } else {
                toast(result.message || '测试失败', 'error');
            }
            loadCallbackLogs();
        }

        async function loadCallbackLogs() {
            const tbody = document.getElementById('callback-logs');
            const data = await apiGet('callback_logs');
            if (!data.success) {
                tbody.innerHTML = `<tr><td colspan="5">${esc(data.message || '加载失败')}</td></tr>`;
                return;
            }
            if (!data.logs.length) {
                tbody.innerHTML = '<tr><td colspan="5">暂无推送记录</td></tr>';
                return;
            }
            tbody.innerHTML = data.logs.map(l => `<tr>
                <td data-label="时间">${esc(l.time || '-')}</td>
                <td data-label="来源">${esc(l.source || '-')}</td>
                <td data-label="IP">${esc(l.ip || '-')}</td>
                <td data-label="状态">${l.status === 'sent' ? '<span class="badge badge-success">已发送</span>' : '<span class="badge badge-danger">失败</span>'}</td>
                <td data-label="内容" style="max-width:320px; word-break:break-all;">${esc(l.content || l.error || '-')}</td>
            </tr>`).join('');
        }

        // ===== AI 对接 =====
        const AI_PROVIDERS = {
            sensenova: { url: 'https://token.sensenova.cn/v1/chat/completions', model: 'sensenova-6.7-flash-lite' },
            deepseek:  { url: 'https://api.deepseek.com/v1/chat/completions', model: 'deepseek-chat' },
            openai:    { url: 'https://api.openai.com/v1/chat/completions', model: 'gpt-4o-mini' },
            qwen:      { url: 'https://dashscope.aliyuncs.com/compatible-mode/v1/chat/completions', model: 'qwen-plus' },
            custom:    { url: '', model: '' },
        };

        async function loadAiConfig() {
            const data = await apiGet('ai_config');
            if (!data.success) {
                toast(data.message || 'AI 配置加载失败', 'error');
                return;
            }
            const cfg = data.config || {};
            document.getElementById('ai-base-url').value = cfg.base_url || '';
            document.getElementById('ai-model').value = cfg.model || '';
            document.getElementById('ai-api-key').value = cfg.api_key || '';
            document.getElementById('ai-max-tokens').value = cfg.max_tokens || 2048;
            document.getElementById('ai-temperature').value = cfg.temperature != null ? cfg.temperature : 0.85;
            document.getElementById('ai-system-prompt').value = cfg.system_prompt || '';
            document.getElementById('ai-test-result').className = 'test-result';
            document.getElementById('ai-test-result').textContent = '';
        }

        function selectProvider(key) {
            document.querySelectorAll('.provider-chip').forEach(c => c.classList.toggle('active', c.dataset.provider === key));
            const p = AI_PROVIDERS[key];
            if (p && key !== 'custom') {
                document.getElementById('ai-base-url').value = p.url;
                document.getElementById('ai-model').value = p.model;
            }
        }

        async function saveAiConfig() {
            const params = {
                base_url: document.getElementById('ai-base-url').value.trim(),
                model: document.getElementById('ai-model').value.trim(),
                api_key: document.getElementById('ai-api-key').value.trim(),
                max_tokens: parseInt(document.getElementById('ai-max-tokens').value) || 2048,
                temperature: parseFloat(document.getElementById('ai-temperature').value) || 0.85,
                system_prompt: document.getElementById('ai-system-prompt').value,
            };
            if (!params.base_url) { toast('请填写接口地址', 'warning'); return; }
            if (!params.model) { toast('请填写模型名称', 'warning'); return; }
            if (!params.api_key) { toast('请填写 API Key', 'warning'); return; }
            const result = await api('ai_config_save', params);
            if (result.success) {
                toast('AI 对接配置已保存');
                loadAiConfig();
            } else {
                toast(result.message || '保存失败', 'error');
            }
        }

        async function testAiConfig() {
            const box = document.getElementById('ai-test-result');
            box.className = 'test-result';
            box.textContent = '正在测试连接，请稍候…';
            box.style.display = 'block';
            const result = await api('ai_config_test', {
                base_url: document.getElementById('ai-base-url').value.trim(),
                model: document.getElementById('ai-model').value.trim(),
                api_key: document.getElementById('ai-api-key').value.trim(),
                max_tokens: 64,
            });
            if (result.success) {
                box.className = 'test-result ok';
                box.textContent = '连接成功：' + (result.reply || '');
            } else {
                box.className = 'test-result fail';
                box.textContent = '连接失败：' + (result.message || '未知错误');
            }
        }

        // ===== 功能管理 =====
        const MODULES_META = [
            { key: 'dashboard', name: '仪表盘', icon: '📊', desc: '机器人运行概览' },
            { key: 'bots', name: '机器人管理', icon: '🤖', desc: '配置与管理 QQ 机器人' },
            { key: 'plugins', name: '插件管理', icon: '🧩', desc: '插件启用/禁用' },
            { key: 'files', name: '插件文件', icon: '📁', desc: '在线编辑插件代码' },
            { key: 'persona', name: '人设管理', icon: '🎭', desc: '预设人设与记忆系统' },
            { key: 'logs', name: '日志中心', icon: '📜', desc: '查看运行日志' },
            { key: 'commands', name: '命令中心', icon: '⌨️', desc: '扫描插件命令关键词' },
            { key: 'data', name: '数据管理', icon: '🗃️', desc: '查看记忆与业务数据' },
            { key: 'callback', name: '回调推送', icon: '⚡', desc: '第三方推送 → 私聊通知' },
            { key: 'ai', name: 'AI 对接', icon: '🤖', desc: 'AI 大模型接口配置' },
            { key: 'settings', name: '系统设置', icon: '⚙️', desc: 'Webhook 与 API 配置' },
        ];

        async function loadModules() {
            const [modData, fileData] = await Promise.all([apiGet('modules_get'), apiGet('plugin_files')]);
            const grid = document.getElementById('modules-grid');
            const states = (modData.success && modData.modules) ? modData.modules : {};

            grid.innerHTML = MODULES_META.map(m => `
                <div class="module-card">
                    <div class="m-icon">${m.icon}</div>
                    <div class="m-info">
                        <div class="m-name">${esc(m.name)}</div>
                        <div class="m-desc">${esc(m.desc)}</div>
                    </div>
                    <label class="toggle">
                        <input type="checkbox" ${states[m.key] === false ? '' : 'checked'} onchange="toggleModule('${m.key}', this.checked)">
                        <span class="toggle-slider"></span>
                    </label>
                </div>
            `).join('');

            const pf = document.getElementById('module-plugin-files');
            if (!fileData.success || !fileData.files.length) {
                pf.innerHTML = '<div style="font-size:13px; color:var(--text-secondary);">plugins/ 目录下暂无文件</div>';
                return;
            }
            pf.innerHTML = fileData.files.map(f => `
                <div class="module-card">
                    <div class="m-icon">🐘</div>
                    <div class="m-info">
                        <div class="m-name">${esc(f.path)}</div>
                        <div class="m-desc">${formatSize(f.size)}</div>
                    </div>
                    <button class="btn btn-sm btn-danger" onclick="deleteModulePlugin('${esc(f.path)}')">删除</button>
                </div>
            `).join('');
        }

        async function toggleModule(key, enabled) {
            const result = await api('modules_save', { key, enabled: String(enabled) });
            if (result.success) {
                toast(enabled ? `功能「${key}」已开启` : `功能「${key}」已关闭，导航将隐藏`);
                applyModuleVisibility(result.modules || {});
            } else {
                toast(result.message || '操作失败', 'error');
            }
        }

        function applyModuleVisibility(states) {
            document.querySelectorAll('.nav-item[data-page]').forEach(item => {
                const page = item.dataset.page;
                if (page === 'modules') return; // 功能管理入口始终保留
                if (page === 'ai' || page === 'callback') {
                    item.style.display = (states[page] === false) ? 'none' : '';
                    return;
                }
                item.style.display = (states[page] === false) ? 'none' : '';
            });
        }

        async function deleteModulePlugin(file) {
            if (!confirm(`确定要删除插件文件 ${file} 吗？\n删除前会自动备份为 .bak 文件。`)) return;
            const result = await api('plugin_delete', { file });
            if (result.success) {
                toast('插件文件已删除（已备份 .bak）');
                loadModules();
            } else {
                toast(result.message || '删除失败', 'error');
            }
        }

        async function deletePluginFile(file) {
            if (!confirm(`确定要删除插件文件 ${file} 吗？\n删除前会自动备份为 .bak 文件。`)) return;
            const result = await api('plugin_delete', { file });
            if (result.success) {
                toast('插件已删除（已备份 .bak）');
                loadPlugins();
            } else {
                toast(result.message || '删除失败', 'error');
            }
        }

        // 页面加载时应用模块显隐
        (async function initModuleVisibility() {
            try {
                const data = await apiGet('modules_get');
                if (data.success) applyModuleVisibility(data.modules || {});
            } catch (e) { /* 忽略 */ }
        })();
    </script>
</body>
</html>
