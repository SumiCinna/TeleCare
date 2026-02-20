<?php
session_start();
if (isset($_SESSION['user_id'])) { header('Location: ../dashboard.php'); exit; }
require_once '../config/database.php';
require_once '../config/app_config.php';
require_once '../vendor/autoload.php';

if (!isset($_GET['code'])) { header('Location: register.php?error=oauth_failed'); exit; }

$client = new Google\Client();
$client->setClientId(GOOGLE_CLIENT_ID);
$client->setClientSecret(GOOGLE_CLIENT_SECRET);
$client->setRedirectUri(GOOGLE_REDIRECT_REGISTER);

try {
    $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
    if (isset($token['error'])) { header('Location: register.php?error=token_failed'); exit; }
    $client->setAccessToken($token);

    $oauth    = new Google\Service\Oauth2($client);
    $userInfo = $oauth->userinfo->get();

    $googleId  = $userInfo->getId();
    $email     = $userInfo->getEmail();
    $name      = $userInfo->getName();
    $firstName = $userInfo->getGivenName() ?? explode(' ', $name)[0];
    $lastName  = $userInfo->getFamilyName() ?? (explode(' ', $name)[1] ?? '');

    $existing = $pdo->prepare('SELECT id FROM users WHERE email = ? OR google_id = ? LIMIT 1');
    $existing->execute([$email, $googleId]);
    if ($existing->fetch()) {
        header('Location: login.php?error=already_registered');
        exit;
    }

    $_SESSION['google_pending'] = [
        'google_id'  => $googleId,
        'email'      => $email,
        'first_name' => $firstName,
        'last_name'  => $lastName,
    ];

    header('Location: google-role-select.php');
    exit;
} catch (Exception $e) {
    header('Location: register.php?error=oauth_error');
    exit;
}