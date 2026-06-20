<?php
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>cPanel Git Deployment Debugger</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; padding: 20px; background: #f8f9fa; color: #333; }
        h1, h2 { color: #1d3557; }
        pre { background: #e9ecef; padding: 15px; border-radius: 5px; overflow-x: auto; font-size: 14px; border: 1px solid #dee2e6; }
        .success { color: #2a9d8f; font-weight: bold; }
        .error { color: #e63946; font-weight: bold; }
        .box { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); margin-bottom: 20px; border: 1px solid #e2e8f0; }
        .btn { background: #1d3557; color: white; border: none; padding: 10px 15px; border-radius: 4px; cursor: pointer; text-decoration: none; display: inline-block; font-size: 14px; }
        .btn:hover { background: #457b9d; }
    </style>
</head>
<body>
    <h1>cPanel Git Deployment Debugger</h1>
    <div class="box">
        <a href="?action=reset" class="btn">Force Git Reset & Clean</a>
        <a href="?action=status" class="btn" style="background:#457b9d;">Refresh Status</a>
    </div>

    <?php
    $action = $_GET['action'] ?? 'status';

    if ($action === 'reset') {
        echo "<div class='box'>";
        echo "<h2>Running Git Reset & Clean...</h2>";
        $reset = shell_exec('/usr/local/cpanel/3rdparty/bin/git reset --hard HEAD 2>&1');
        $clean = shell_exec('/usr/local/cpanel/3rdparty/bin/git clean -fd 2>&1');
        echo "<h3>Reset Output:</h3><pre>" . htmlspecialchars($reset) . "</pre>";
        echo "<h3>Clean Output:</h3><pre>" . htmlspecialchars($clean) . "</pre>";
        echo "</div>";
    }
    ?>

    <div class="box">
        <h2>Git Current Branch & Status</h2>
        <pre><?php
            $status = shell_exec('/usr/local/cpanel/3rdparty/bin/git status 2>&1');
            echo htmlspecialchars($status);
        ?></pre>
    </div>

    <div class="box">
        <h2>Last 5 Commits</h2>
        <pre><?php
            $log = shell_exec('/usr/local/cpanel/3rdparty/bin/git log -n 5 --oneline 2>&1');
            echo htmlspecialchars($log);
        ?></pre>
    </div>

    <div class="box">
        <h2>Latest cPanel Git Deployment Logs</h2>
        <pre><?php
            $log_files = glob('/home/oatgwnis/.cpanel/logs/vc_*_git_deploy.log');
            if ($log_files) {
                // Sort by modification time
                array_multisort(array_map('filemtime', $log_files), SORT_DESC, $log_files);
                
                foreach (array_slice($log_files, 0, 3) as $file) {
                    echo "<h3>File: " . htmlspecialchars(basename($file)) . " (Modified: " . date("Y-m-d H:i:s", filemtime($file)) . ")</h3>";
                    echo htmlspecialchars(file_get_contents($file));
                    echo "\n" . str_repeat('=', 50) . "\n";
                }
            } else {
                echo "No deploy logs found in ~/.cpanel/logs/";
            }
        ?></pre>
    </div>
</body>
</html>
