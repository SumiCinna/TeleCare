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

$email      = $payload['email'];
$name       = $payload['name'];
$google_id  = $payload['sub'];
$picture    = $payload['picture'] ?? null;
$first_name = $payload['given_name'] ?? explode(' ', $name)[0];
$last_name  = $payload['family_name'] ?? (explode(' ', $name)[1] ?? '');

try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? OR google_id = ? LIMIT 1");
    $stmt->execute([$email, $google_id]);
    if($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Account already exists. Please login instead.']);
        exit();
    }

    $_SESSION['google_pending'] = [
        'google_id'  => $google_id,
        'email'      => $email,
        'first_name' => $first_name,
        'last_name'  => $last_name,
        'picture'    => $picture,
    ];

    echo json_encode(['success' => true]);
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>