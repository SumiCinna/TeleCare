<?php
session_start();
header('Content-Type: application/json');

require_once '../config/database.php';

$data = json_decode(file_get_contents('php://input'), true);
if(!isset($data['credential'])) {
    echo json_encode(['success' => false, 'message' => 'No credential provided']);
    exit();
}

$response = file_get_contents('https://oauth2.googleapis.com/tokeninfo?id_token=' . $data['credential']);
$payload = json_decode($response, true);

if(!$payload || !isset($payload['sub'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid token']);
    exit();
}

if($payload['aud'] !== '901503175288-na0f91f6bppnfbthdl6cn7fbg8e5m0bi.apps.googleusercontent.com') {
    echo json_encode(['success' => false, 'message' => 'Token mismatch']);
    exit();
}

$email     = $payload['email'];
$google_id = $payload['sub'];
$picture   = $payload['picture'] ?? null;

try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? OR google_id = ? LIMIT 1");
    $stmt->execute([$email, $google_id]);
    $user = $stmt->fetch();

    if($user) {
        if(!$user['is_active']) {
            echo json_encode(['success' => false, 'message' => 'Your account has been deactivated.']);
            exit();
        }

        if(empty($user['google_id'])) {
            $pdo->prepare("UPDATE users SET google_id = ? WHERE id = ?")->execute([$google_id, $user['id']]);
        }

        
        $pdo->prepare("INSERT INTO activity_logs (user_id, action, description, ip_address) VALUES (?, ?, ?, ?)")
            ->execute([$user['id'], 'login', 'User logged in via Google', $_SERVER['REMOTE_ADDR']]);

        $_SESSION['user_id']    = $user['id'];
        $_SESSION['user_name']  = $user['first_name'] . ' ' . $user['last_name'];
        $_SESSION['user_role']  = $user['role'];
        $_SESSION['user_email'] = $user['email'];

        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'No account found. Please sign up first.']);
    }
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>