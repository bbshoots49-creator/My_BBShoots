<?php
// ============================================
// BBShoots Debug Test File
// Upload to your /api/ folder and visit in browser
// Delete after debugging!
// ============================================

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<html><head><title>BBShoots Debug</title>";
echo "<style>
body { font-family: Arial, sans-serif; padding: 20px; background: #111; color: #ddd; }
h1 { color: #dc2626; } h2 { color: #f1f5f9; margin-top: 30px; }
.success { color: #22c55e; font-weight: bold; }
.error { color: #ef4444; font-weight: bold; }
.box { background: #1e293b; padding: 15px; border-radius: 8px; margin: 10px 0; }
table { border-collapse: collapse; width: 100%; }
td, th { padding: 8px; text-align: left; border-bottom: 1px solid #374151; }
th { color: #94a3b8; }
hr { border: none; border-top: 1px solid #374151; margin: 20px 0; }
</style></head><body>";

echo "<h1>🎬 BBShoots Debug Test</h1>";

// Test 1: PHP Info
echo "<div class='box'>";
echo "<h2>1. PHP Environment</h2>";
echo "<table>";
echo "<tr><th>PHP Version</th><td>" . phpversion() . "</td></tr>";
echo "<tr><th>PDO Extension</th><td>" . (extension_loaded('pdo') ? '<span class="success">✓ Available</span>' : '<span class="error">✗ Missing</span>') . "</td></tr>";
echo "<tr><th>PDO MySQL</th><td>" . (extension_loaded('pdo_mysql') ? '<span class="success">✓ Available</span>' : '<span class="error">✗ Missing</span>') . "</td></tr>";
echo "<tr><th>Session Support</th><td>" . (function_exists('session_start') ? '<span class="success">✓ Available</span>' : '<span class="error">✗ Missing</span>') . "</td></tr>";
echo "<tr><th>JSON Support</th><td>" . (function_exists('json_encode') ? '<span class="success">✓ Available</span>' : '<span class="error">✗ Missing</span>') . "</td></tr>";
echo "</table>";
echo "</div>";

// Test 2: Database Connection
echo "<div class='box'>";
echo "<h2>2. Database Connection Test</h2>";

// Read config values
define('DB_HOST', 'sql102.byetcluster.com');
define('DB_PORT', 3306);
define('DB_NAME', 'if0_41323896_bbshoots');
define('DB_USER', 'if0_41323896');
define('DB_PASS', 'bbshoots2026');

echo "<table>";
echo "<tr><th>DB Host</th><td>" . DB_HOST . "</td></tr>";
echo "<tr><th>DB Port</th><td>" . DB_PORT . "</td></tr>";
echo "<tr><th>DB Name</th><td>" . DB_NAME . "</td></tr>";
echo "<tr><th>DB User</th><td>" . DB_USER . "</td></tr>";
echo "</table>";

try {
    $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    echo "<p class='success'>✓ Database connection successful!</p>";
    
    // Test 3: Check Tables
    echo "<hr>";
    echo "<h2>3. Database Tables</h2>";
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($tables)) {
        echo "<p class='error'>✗ No tables found! You need to import database.sql</p>";
    } else {
        echo "<p class='success'>✓ Tables found: " . implode(', ', $tables) . "</p>";
        
        // Check each required table
        $required = ['clients', 'bookings', 'projects', 'notifications', 'contact_messages'];
        echo "<table>";
        foreach ($required as $t) {
            $exists = in_array($t, $tables);
            echo "<tr><th>$t</th><td>" . ($exists ? '<span class="success">✓ Exists</span>' : '<span class="error">✗ Missing</span>') . "</td></tr>";
        }
        echo "</table>";
        
        // Count records
        echo "<hr>";
        echo "<h2>4. Record Counts</h2>";
        echo "<table>";
        foreach ($required as $t) {
            if (in_array($t, $tables)) {
                $count = $pdo->query("SELECT COUNT(*) FROM $t")->fetchColumn();
                echo "<tr><th>$t</th><td>$count records</td></tr>";
            }
        }
        echo "</table>";
    }
    
} catch (PDOException $e) {
    echo "<p class='error'>✗ Database connection failed!</p>";
    echo "<p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    
    // Common error hints
    $err = $e->getMessage();
    if (strpos($err, 'Access denied') !== false) {
        echo "<p><strong>Possible fix:</strong> Check your DB username and password in config.php</p>";
    } elseif (strpos($err, 'Unknown database') !== false) {
        echo "<p><strong>Possible fix:</strong> Create the database or import database.sql in phpMyAdmin</p>";
    } elseif (strpos($err, 'Connection refused') !== false || strpos($err, 'No connection') !== false) {
        echo "<p><strong>Possible fix:</strong> Check DB_HOST is correct (try 'localhost' or the host from your hosting panel)</p>";
    }
}
echo "</div>";

// Test 5: Session
echo "<div class='box'>";
echo "<h2>5. Session Test</h2>";
session_start();
$_SESSION['test'] = 'working';
echo "<p>Session ID: " . session_id() . "</p>";
echo "<p>Session test: " . (isset($_SESSION['test']) ? '<span class="success">✓ Sessions working</span>' : '<span class="error">✗ Sessions not working</span>') . "</p>";
echo "</div>";

// Test 6: API Endpoint Test
echo "<div class='box'>";
echo "<h2>6. API Endpoint Test</h2>";
echo "<p>Testing: <code>?action=check_session</code></p>";

// Get current URL
$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
$apiUrl = $protocol . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/index.php?action=check_session';

echo "<p>API URL: <code>$apiUrl</code></p>";

$ch = curl_init($apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, false);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "<p>HTTP Status: " . ($httpCode == 200 ? '<span class="success">200 OK</span>' : "<span class='error'>$httpCode</span>") . "</p>";
echo "<p>Response:</p>";
echo "<pre style='background:#0f172a;padding:10px;border-radius:5px;overflow-x:auto;'>" . htmlspecialchars($response) . "</pre>";

$json = json_decode($response, true);
if ($json) {
    echo "<p class='success'>✓ Valid JSON response received!</p>";
} else {
    echo "<p class='error'>✗ Invalid JSON response. This is the problem!</p>";
    echo "<p>The API is returning non-JSON content. Check for PHP errors above.</p>";
}
echo "</div>";

echo "<hr>";
echo "<p style='color:#64748b'>⚠️ Delete this file (test.php) after debugging for security!</p>";
echo "</body></html>";
