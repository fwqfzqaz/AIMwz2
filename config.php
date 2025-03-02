<?php
// config.php - 配置文件
define('TCP_HOST', '103.85.86.51');
define('TCP_PORT', 29703);
define('WHITELIST_PATH', '/path/to/local/whitelist.json'); // 网页服务器本地的白名单文件路径

// index.php - 主页面
require_once 'config.php';

// 处理表单提交
$response = '';
$error = '';
$whitelist = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 输入验证
    if (!isset($_POST['player_name']) || empty(trim($_POST['player_name']))) {
        $error = "请输入玩家名称";
    } else {
        $playerName = trim($_POST['player_name']);
        
        // 安全过滤
        $playerName = preg_replace('/[^a-zA-Z0-9_]/', '', $playerName);
        
        if (strlen($playerName) < 3 || strlen($playerName) > 16) {
            $error = "玩家名称长度需为3-16个字符";
        } else {
            try {
                // 建立TCP连接
                $socket = fsockopen(TCP_HOST, TCP_PORT, $errno, $errstr, 10);
                
                if (!$socket) {
                    throw new Exception("连接白名单服务失败: $errstr");
                }
                
                // 设置超时时间
                stream_set_timeout($socket, 15);
                
                // 发送玩家名称
                fwrite($socket, $playerName . "\n");
                
                // 获取响应
                $response = fgets($socket, 1024);
                
                if ($response === false) {
                    throw new Exception("服务端未响应");
                }
                
                fclose($socket);
            } catch (Exception $e) {
                $error = "操作失败: " . $e->getMessage();
            }
        }
    }
}

// 读取本地白名单缓存
try {
    if (file_exists(WHITELIST_PATH)) {
        $whitelistData = json_decode(file_get_contents(WHITELIST_PATH), true);
        foreach ($whitelistData as $entry) {
            $whitelist[] = htmlspecialchars($entry['name']);
        }
    }
} catch (Exception $e) {
    $error = "白名单列表暂不可用";
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIMPFUN 白名单系统</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { padding: 20px; background: #f0f2f5; }
        .container { max-width: 800px; margin-top: 40px; }
        .status-badge { font-size: 0.8em; padding: 3px 8px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="card shadow">
            <div class="card-header bg-primary text-white">
                <h3 class="mb-0">SIMPFUN 白名单管理系统</h3>
            </div>
            
            <div class="card-body">
                <!-- 添加表单 -->
                <form method="post" class="mb-4">
                    <div class="input-group">
                        <input type="text" class="form-control" 
                               name="player_name" 
                               placeholder="输入Minecraft游戏ID"
                               pattern="[A-Za-z0-9_]{3,16}"
                               required>
                        <button type="submit" class="btn btn-success">
                            添加白名单
                        </button>
                    </div>
                    <?php if($error): ?>
                        <div class="text-danger mt-2"><?= $error ?></div>
                    <?php elseif($response): ?>
                        <div class="text-success mt-2"><?= htmlspecialchars($response) ?></div>
                    <?php endif; ?>
                </form>

                <!-- 白名单列表 -->
                <h5>已授权玩家（<?= count($whitelist) ?>人）</h5>
                <div class="border rounded p-3 bg-light">
                    <?php if(!empty($whitelist)): ?>
                        <div class="row">
                            <?php foreach($whitelist as $name): ?>
                                <div class="col-md-4 mb-2">
                                    <span class="badge bg-success status-badge">✓</span>
                                    <?= $name ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-muted">暂无白名单记录</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>