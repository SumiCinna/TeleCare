<?php
session_start();
if (isset($_SESSION['user_id'])) { header('Location: ../dashboard.php'); exit; }
require_once '../config/database.php';
require_once '../config/app_config.php';
require_once '../vendor/autoload.php';

if (!isset($_GET['code'])) { header('Location: login.php?error=oauth_failed'); exit; }

$client = new Google\Client();
$client->setClientId(GOOGLE_CLIENT_ID);
$client->setClientSecret(GOOGLE_CLIENT_SECRET);
$client->setRedirectUri(GOOGLE_REDIRECT_LOGIN);

try {
    $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
    if (isset($token['error'])) { header('Location: login.php?error=token_failed'); exit; }
    $client->setAccessToken($token);

    $oauth    = new Google\Service\Oauth2($client);
    $userInfo = $oauth->userinfo->get();

    $googleId = $userInfo->getId();
    $email    = $userInfo->getEmail();
    $name     = $userInfo->getName();
    $firstName = $userInfo->getGivenName() ?? explode(' ', $name)[0];
    $lastName  = $userInfo->getFamilyName() ?? (explode(' ', $name)[1] ?? '');

    $stmt = $pdo->prepare('SELECT id, first_name, last_name, email, role, is_active FROM users WHERE email = ? OR google_id = ? LIMIT 1');
    $stmt->execute([$email, $googleId]);
    $user = $stmt->fetch();

    if ($user) {
        if (!$user['is_active']) { header('Location: login.php?error=deactivated'); exit; }

        if (!$user['google_id'] ?? true) {
            $pdo->prepare('UPDATE users SET google_id = ? WHERE id = ?')->execute([$googleId, $user['id']]);
        }

        $_SESSION['user_id']    = $user['id'];
        $_SESSION['user_name']  = $user['first_name'] . ' ' . $user['last_name'];
        $_SESSION['user_role']  = $user['role'];
        $_SESSION['user_email'] = $user['email'];

        $pdo->prepare('INSERT INTO activity_logs (user_id, action, description, ip_address) VALUES (?, ?, ?, ?)')
            ->execute([$user['id'], 'login', 'User logged in via Google', $_SERVER['REMOTE_ADDR']]);

        header('Location: ../dashboard.php');
        exit;
    } else {
        $_SESSION['google_pending'] = [
            'google_id'  => $googleId,
            'email'      => $email,
            'first_name' => $firstName,
            'last_name'  => $lastName,
        ];
        header('Location: google-role-select.php');
        exit;
    }
} catch (Exception $e) {
    header('Location: login.php?error=oauth_error');
    exit;
}