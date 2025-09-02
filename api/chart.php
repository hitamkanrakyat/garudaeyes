<?php
// API: return login attempts per hour as JSON
// Add robust error handling to aid debugging in deployment
require_once __DIR__ . '/../init.php';
header('Content-Type: application/json');

set_error_handler(function($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});

try {
    $pdo = get_pdo();
    $labels = [];
    $counts = [];
    $hours = [];
    for ($i = 23; $i >= 0; $i--) {
        $t = new DateTimeImmutable("-{$i} hours");
        $hours[] = $t->format('Y-m-d H:00:00');
        $labels[] = $t->format('H:00');
    }
    $stmt = $pdo->prepare("SELECT DATE_FORMAT(created_at, '%Y-%m-%d %H:00:00') as h, COUNT(*) as c FROM login_attempts WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR) GROUP BY h");
    $stmt->execute();
    $rows = $stmt->fetchAll();
    $map = [];
    foreach ($rows as $r) { $map[$r['h']] = (int)$r['c']; }
    foreach ($hours as $h) { $counts[] = $map[$h] ?? 0; }

    echo json_encode(['labels'=>$labels,'data'=>$counts]);
} catch (Throwable $e) {
    // Log to temporary file for debugging on the host
    @file_put_contents('/tmp/chart_error.log', date('c') . " - " . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n", FILE_APPEND);
    http_response_code(500);
    echo json_encode(['error' => 'internal', 'message' => 'internal server error']);
}

