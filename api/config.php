<?php
// ============================================
// BBShoots — config.php  (FIXED v3)
// ============================================

// ── Database Configuration ─────────────────────────────
define('DB_HOST', 'sql102.byetcluster.com');
define('DB_PORT', 3306);  // ✅ FIXED: Added missing port definition
define('DB_NAME', 'if0_41323896_bbshoots');
define('DB_USER', 'if0_41323896');
define('DB_PASS', 'bbshoots2026');

// ── Application URL (single definition) ─────────────────
// Auto-detect based on host
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
if (strpos($host, 'localhost') !== false) {
    define('APP_URL', 'http://localhost/bbshoots');
} else {
    define('APP_URL', 'http://' . $host);
}

// ── Admin Credentials ──────────────────────────────────
define('ADMIN_EMAIL',    'bbshoots49@gmail.com');
define('ADMIN_PASSWORD', 'BBShoots@2025');

// ── Email Configuration (PHPMailer) ─────────────────────
define('MAIL_HOST',      'smtp.gmail.com');
define('MAIL_PORT',      587);
define('MAIL_USERNAME',  'bbshoots49@gmail.com');
define('MAIL_PASSWORD',  'YOUR_GMAIL_APP_PASSWORD');  // Replace with your Gmail App Password
define('MAIL_FROM',      'bbshoots49@gmail.com');
define('MAIL_FROM_NAME', 'BBShoots Productions');

// ── Session Configuration ──────────────────────────────
if (session_status() === PHP_SESSION_NONE) {
    session_name('BBSHOOTS');
    
    // Auto-detect cookie domain
    $cookieDomain = '';
    if (strpos($host, 'localhost') === false) {
        // Live server - use actual domain without port
        $cookieDomain = preg_replace('/:\d+$/', '', $host);
    } else {
        $cookieDomain = 'localhost';
    }
    
    ini_set('session.cookie_path',     '/');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.use_strict_mode', '1');
    ini_set('session.gc_maxlifetime',  '86400'); // 24 hours
    
    session_set_cookie_params([
        'lifetime' => 86400,
        'path'     => '/',
        'domain'   => $cookieDomain,
        'secure'   => false,  // Set to true if using HTTPS
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

// ── CORS Headers ───────────────────────────────────────
$origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';

// Allow localhost on any port OR the live domain
$allowedOrigins = [
    'http://localhost',
    'http://bbshoots.42web.io',
    'https://bbshoots.42web.io',
];

// Check if origin is allowed (including localhost with any port)
$isAllowed = false;
if (preg_match('#^https?://localhost(:\d+)?$#', $origin)) {
    $isAllowed = true;
} elseif (in_array($origin, $allowedOrigins)) {
    $isAllowed = true;
}

if ($isAllowed && $origin) {
    header('Access-Control-Allow-Origin: ' . $origin);
}

header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');
header('Content-Type: application/json; charset=utf-8');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// ── Database Connection ────────────────────────────────
function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT
                 . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            // Return JSON error instead of crashing
            resp(false, null, 'Database connection failed: ' . $e->getMessage(), 500);
        }
    }
    return $pdo;
}

// ── Helper Functions ───────────────────────────────────
function resp(bool $ok, $data = null, string $msg = '', int $code = 200): void {
    http_response_code($code);
    echo json_encode(['success' => $ok, 'data' => $data, 'error' => $msg]);
    exit();
}

function body(): array {
    $raw  = file_get_contents('php://input');
    $json = json_decode($raw, true);
    return is_array($json) ? $json : (is_array($_POST) ? $_POST : []);
}

function generateRef(): string {
    $db    = getDB();
    $year  = date('Y');
    $count = (int)$db->query("SELECT COUNT(*) FROM bookings WHERE YEAR(created_at)=$year")->fetchColumn() + 1;
    return 'BK-' . $year . '-' . str_pad($count, 3, '0', STR_PAD_LEFT);
}

function addNotif(string $type, string $msg): void {
    $db = getDB();
    $db->prepare("INSERT INTO notifications(type,message) VALUES(?,?)")->execute([$type, $msg]);
}

function requireAdmin(): void {
    if (empty($_SESSION['admin'])) {
        resp(false, null, 'Admin access required. Please login again.', 401);
    }
}

function requireClient(): void {
    if (empty($_SESSION['client_id'])) {
        resp(false, null, 'Login required.', 401);
    }
}
