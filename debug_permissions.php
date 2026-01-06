<?php
/**
 * TEMPORARY DEBUG SCRIPT - DELETE AFTER FIXING PERMISSION ISSUES
 * 
 * This script helps diagnose permission problems on live server.
 * Upload to server root and access via browser.
 */

session_start();
require_once __DIR__ . '/classes/Includes.php';

// Check if user is logged in
if (!isset($_SESSION['id'])) {
    die('<h1>Please login first</h1><p><a href="views/auth/login.php">Go to Login</a></p>');
}

$userId = $_SESSION['id'];
?>
<!DOCTYPE html>
<html>
<head>
    <title>Permission System Debug</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #f5f5f5; }
        .section { background: white; padding: 20px; margin: 20px 0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        h2 { color: #4361ee; border-bottom: 2px solid #4361ee; padding-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 8px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f8f9fa; font-weight: bold; }
        .success { color: #28a745; }
        .error { color: #dc3545; }
        .warning { color: #ffc107; }
        pre { background: #f8f9fa; padding: 10px; border-radius: 4px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>🔍 Permission System Debug Information</h1>
    <p><strong>Logged in as User ID:</strong> <?php echo htmlspecialchars($userId); ?></p>
    
    <!-- Server Variables -->
    <div class="section">
        <h2>1. Server Variables (Path Detection)</h2>
        <table>
            <tr>
                <th>Variable</th>
                <th>Value</th>
            </tr>
            <?php
            $serverVars = [
                'SCRIPT_NAME', 
                'PHP_SELF', 
                'SCRIPT_FILENAME', 
                'REQUEST_URI', 
                'DOCUMENT_ROOT',
                'SERVER_NAME',
                'HTTP_HOST'
            ];
            foreach ($serverVars as $var) {
                $value = $_SERVER[$var] ?? '<span class="error">NOT SET</span>';
                echo "<tr><td><code>$var</code></td><td>" . htmlspecialchars($value) . "</td></tr>";
            }
            ?>
        </table>
    </div>

    <!-- Current Route Detection -->
    <div class="section">
        <h2>2. Route Detection Test</h2>
        <?php
        $currentRoute = UserPermission::getCurrentPageRoute();
        ?>
        <p><strong>Detected Route:</strong> <code class="<?php echo $currentRoute === '/unknown' ? 'error' : 'success'; ?>"><?php echo htmlspecialchars($currentRoute); ?></code></p>
        
        <?php if ($currentRoute === '/unknown'): ?>
            <p class="warning">⚠️ <strong>WARNING:</strong> Route detection failed! This is why permissions aren't working.</p>
        <?php else: ?>
            <p class="success">✓ Route detected successfully</p>
        <?php endif; ?>
    </div>

    <!-- Permission Check -->
    <div class="section">
        <h2>3. Permission Check Results</h2>
        <?php
        $hasViewPermission = UserPermission::checkPagePermission($userId, 'View');
        ?>
        <p><strong>Has "View" Permission:</strong> 
            <span class="<?php echo $hasViewPermission ? 'success' : 'error'; ?>">
                <?php echo $hasViewPermission ? '✓ YES' : '✗ NO'; ?>
            </span>
        </p>
        
        <?php if (!$hasViewPermission && $currentRoute !== '/unknown'): ?>
            <p class="error">⚠️ User lacks permission for route: <?php echo htmlspecialchars($currentRoute); ?></p>
        <?php endif; ?>
    </div>

    <!-- All User Permissions -->
    <div class="section">
        <h2>4. All User Permissions</h2>
       <?php
        $db = new Database();
        $sql = "SELECT p.page_name, p.page_route, perm.permission_name, up.is_granted
                FROM user_permissions up
                JOIN permissions perm ON up.permission_id = perm.id
                JOIN pages p ON perm.page_id = p.id
                WHERE up.user_id = ?
                ORDER BY p.page_route, perm.permission_name";
        
        $stmt = $db->prepareSelect($sql, [$userId]);
        $permissions = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        
        if (empty($permissions)):
        ?>
            <p class="warning">⚠️ No permissions found for this user!</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Page</th>
                        <th>Route</th>
                        <th>Permission</th>
                        <th>Granted</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($permissions as $perm): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($perm['page_name']); ?></td>
                            <td><code><?php echo htmlspecialchars($perm['page_route']); ?></code></td>
                            <td><?php echo htmlspecialchars($perm['permission_name']); ?></td>
                            <td class="<?php echo $perm['is_granted'] ? 'success' : 'error'; ?>">
                                <?php echo $perm['is_granted'] ? '✓ Yes' : '✗ No'; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <!-- Path Parsing Test -->
    <div class="section">
        <h2>5. Manual Route Extraction Test</h2>
        <p>Testing different path extraction methods:</p>
        <pre><?php
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        echo "SCRIPT_NAME: $scriptName\n\n";
        
        // Method 1: explode by /
        $parts = explode('/', trim($scriptName, '/'));
        echo "Path parts: " . print_r($parts, true) . "\n";
        
        // Find views index
        $viewsIndex = array_search('views', $parts);
        echo "Views index: " . ($viewsIndex !== false ? $viewsIndex : "NOT FOUND") . "\n";
        
        if ($viewsIndex !== false && isset($parts[$viewsIndex + 1])) {
            echo "Directory after 'views': " . $parts[$viewsIndex + 1] . "\n";
        }
        ?></pre>
    </div>

    <!-- Recommendations -->
    <div class="section">
        <h2>6. Next Steps</h2>
        <ol>
            <li>Copy ALL the output from this page</li>
            <li>Send it to your developer</li>
            <li><strong>DELETE this file after debugging is complete for security</strong></li>
        </ol>
    </div>
</body>
</html>
