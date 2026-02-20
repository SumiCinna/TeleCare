<?php
session_start();
if (isset($_SESSION['user_id'])) { header('Location: ../dashboard.php'); exit; }
require_once '../config/database.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName  = trim($_POST['last_name']  ?? '');
    $email     = trim($_POST['email']      ?? '');
    $password  = $_POST['password']        ?? '';
    $confirm   = $_POST['confirm_password'] ?? '';
    $role      = $_POST['role']            ?? '';
    $terms     = $_POST['terms']           ?? '';

    if (empty($firstName) || empty($lastName)) {
        $error = 'First and last name are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters.';
    } elseif (!preg_match('/[A-Z]/', $password)) {
        $error = 'Password must contain at least one uppercase letter.';
    } elseif (!preg_match('/[0-9]/', $password)) {
        $error = 'Password must contain at least one number.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } elseif (!in_array($role, ['patient', 'doctor'])) {
        $error = 'Please select a valid role.';
    } elseif (!$terms) {
        $error = 'You must agree to the Terms of Service and Privacy Policy.';
    } else {
        $check = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        $check->execute([$email]);
        if ($check->fetch()) {
            $error = 'An account with this email already exists.';
        } else {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare('INSERT INTO users (first_name, last_name, email, password, role, is_active) VALUES (?, ?, ?, ?, ?, 1)');
            $stmt->execute([$firstName, $lastName, $email, $hash, $role]);
            $newId = $pdo->lastInsertId();
            if ($role === 'doctor') {
                $pdo->prepare('INSERT INTO doctor_profiles (user_id) VALUES (?)')->execute([$newId]);
            }
            $pdo->prepare('INSERT INTO activity_logs (user_id, action, description, ip_address) VALUES (?, ?, ?, ?)')
                ->execute([$newId, 'register', 'User registered', $_SERVER['REMOTE_ADDR']]);
            header('Location: login.php?registered=1');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account – TeleCare AI</title>
    <link rel="icon" type="image/jpeg" href="../images/logo.jpg">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://accounts.google.com/gsi/client" async defer></script>
    <link rel="stylesheet" href="../css/login.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: { DEFAULT: '#0ea5e9', dark: '#0284c7' },
                        accent:  '#6366f1',
                        dark:    { DEFAULT: '#0f172a', card: '#1e293b' }
                    },
                    fontFamily: { sans: ['Inter', 'system-ui', 'sans-serif'] }
                }
            }
        }
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .role-card input:checked + label {
            border-color: #0ea5e9;
            background-color: rgba(14,165,233,0.08);
        }
        .role-card input:checked + label .role-dot {
            background-color: #0ea5e9;
            border-color: #0ea5e9;
        }
        #g_id_onload, .g_id_signin { width: 100% !important; }
        .g_id_signin > div { width: 100% !important; }
        .modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.75);
            z-index: 50;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
        }
        .modal-overlay.active { display: flex; }
        .req { display: flex; align-items: center; gap: 6px; font-size: 0.7rem; }
        .req svg { width: 14px; height: 14px; flex-shrink: 0; }
        .req.met { color: #34d399; }
        .req.unmet { color: rgba(255,255,255,0.3); }
    </style>
</head>
<body class="bg-dark min-h-screen flex font-sans antialiased">

<div class="hidden lg:flex lg:w-1/2 relative overflow-hidden">
    <div class="absolute inset-0 bg-gradient-to-br from-accent/20 via-primary/10 to-dark"></div>
    <div class="absolute top-1/4 left-1/4 w-72 h-72 bg-accent/15 rounded-full blur-3xl"></div>
    <div class="absolute bottom-1/4 right-1/3 w-56 h-56 bg-primary/15 rounded-full blur-3xl"></div>
    <div class="relative z-10 flex flex-col justify-between p-12 w-full">
        <a href="../index.php" class="flex items-center gap-2">
            <img src="../images/logo.jpg" alt="TeleCare AI" class="h-9 w-auto object-contain rounded-lg">
            <span class="text-xl font-bold">Tele<span class="text-primary">Care</span> <span class="text-white/30 text-sm">AI</span></span>
        </a>
        <div>
            <h2 class="text-4xl font-extrabold text-white leading-tight mb-4">Join TeleCare AI<br>today.</h2>
            <p class="text-white/50 text-base leading-relaxed max-w-sm">Connect with doctors, manage your health records, and experience AI-powered consultations.</p>
            <div class="mt-10 grid grid-cols-2 gap-4">
                <div class="bg-white/5 border border-white/8 rounded-2xl p-4">
                    <div class="text-2xl font-bold text-primary mb-1">AI</div>
                    <p class="text-xs text-white/40">Clinical Summaries</p>
                </div>
                <div class="bg-white/5 border border-white/8 rounded-2xl p-4">
                    <div class="text-2xl font-bold text-accent mb-1">24/7</div>
                    <p class="text-xs text-white/40">Platform Access</p>
                </div>
                <div class="bg-white/5 border border-white/8 rounded-2xl p-4">
                    <div class="text-2xl font-bold text-emerald-400 mb-1">🔒</div>
                    <p class="text-xs text-white/40">Secure & Private</p>
                </div>
                <div class="bg-white/5 border border-white/8 rounded-2xl p-4">
                    <div class="text-2xl font-bold text-amber-400 mb-1">📋</div>
                    <p class="text-xs text-white/40">Digital Records</p>
                </div>
            </div>
        </div>
        <p class="text-white/20 text-xs">© 2026 TeleCare AI · BS Information Systems</p>
    </div>
</div>

<div class="w-full lg:w-1/2 flex items-center justify-center p-8 overflow-y-auto">
    <div class="w-full max-w-md py-8">
        <div class="mb-6">
            <a href="../index.php" class="flex items-center gap-2 mb-6 lg:hidden">
                <img src="../images/logo.jpg" alt="TeleCare AI" class="h-8 w-auto object-contain rounded-lg">
                <span class="font-bold">Tele<span class="text-primary">Care</span> AI</span>
            </a>
            <h1 class="text-3xl font-bold text-white mb-1">Create account</h1>
            <p class="text-white/45 text-sm">Join TeleCare AI for free</p>
        </div>

        <?php if ($error): ?>
        <div class="flex items-center gap-3 bg-red-500/10 border border-red-500/20 text-red-400 text-sm px-4 py-3 rounded-xl mb-5" id="errorBox">
            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <span id="errorMsg"><?= htmlspecialchars($error) ?></span>
        </div>
        <?php else: ?>
        <div class="hidden flex items-center gap-3 bg-red-500/10 border border-red-500/20 text-red-400 text-sm px-4 py-3 rounded-xl mb-5" id="errorBox">
            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <span id="errorMsg"></span>
        </div>
        <?php endif; ?>

        <div id="g_id_onload"
            data-client_id="901503175288-na0f91f6bppnfbthdl6cn7fbg8e5m0bi.apps.googleusercontent.com"
            data-callback="handleGoogleRegister">
        </div>
        <div class="g_id_signin mb-5" data-type="standard" data-theme="outline" data-size="large" data-text="signup_with" data-width="100%"></div>

        <div class="flex items-center gap-3 mb-5">
            <div class="flex-1 h-px bg-white/10"></div>
            <span class="text-xs text-white/30">or register with email</span>
            <div class="flex-1 h-px bg-white/10"></div>
        </div>

        <form method="POST" novalidate id="registerForm">
            <div class="space-y-4">
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-medium text-white/70 mb-1.5">First Name</label>
                        <input type="text" name="first_name" value="<?= htmlspecialchars($_POST['first_name'] ?? '') ?>" placeholder="John"
                               class="w-full bg-white/5 border border-white/10 text-white placeholder-white/25 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-primary transition-all" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-white/70 mb-1.5">Last Name</label>
                        <input type="text" name="last_name" value="<?= htmlspecialchars($_POST['last_name'] ?? '') ?>" placeholder="Doe"
                               class="w-full bg-white/5 border border-white/10 text-white placeholder-white/25 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-primary transition-all" required>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-white/70 mb-1.5">Email Address</label>
                    <input type="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" placeholder="you@example.com"
                           class="w-full bg-white/5 border border-white/10 text-white placeholder-white/25 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-primary transition-all" required>
                </div>

                <div>
                    <label class="block text-sm font-medium text-white/70 mb-1.5">Password</label>
                    <div class="relative">
                        <input type="password" id="password" name="password" placeholder="Min. 8 characters"
                               class="w-full bg-white/5 border border-white/10 text-white placeholder-white/25 rounded-xl px-4 py-3 pr-12 text-sm focus:outline-none focus:border-primary transition-all" required>
                        <button type="button" id="togglePassword" class="absolute right-3 top-1/2 -translate-y-1/2 text-white/30 hover:text-white/70 transition-colors p-1">
                            <svg id="eyeOpen" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                            <svg id="eyeClosed" class="w-5 h-5 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                            </svg>
                        </button>
                    </div>
                    <div class="mt-2 flex flex-wrap gap-x-4 gap-y-1 px-1" id="pwRequirements">
                        <div class="req unmet" id="req-len">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            8+ characters
                        </div>
                        <div class="req unmet" id="req-upper">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            Uppercase letter
                        </div>
                        <div class="req unmet" id="req-num">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            Number
                        </div>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-white/70 mb-1.5">Confirm Password</label>
                    <div class="relative">
                        <input type="password" id="confirm_password" name="confirm_password" placeholder="Re-enter password"
                               class="w-full bg-white/5 border border-white/10 text-white placeholder-white/25 rounded-xl px-4 py-3 pr-12 text-sm focus:outline-none focus:border-primary transition-all" required>
                        <button type="button" id="toggleConfirm" class="absolute right-3 top-1/2 -translate-y-1/2 text-white/30 hover:text-white/70 transition-colors p-1">
                            <svg id="eyeOpenC" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                            <svg id="eyeClosedC" class="w-5 h-5 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                            </svg>
                        </button>
                    </div>
                    <p class="text-red-400 text-xs mt-1 hidden" id="confirmError">Passwords do not match.</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-white/70 mb-2">I am a...</label>
                    <div class="grid grid-cols-2 gap-3">
                        <div class="role-card">
                            <input type="radio" name="role" id="role-patient" value="patient" class="sr-only" <?= ($_POST['role'] ?? '') === 'patient' ? 'checked' : '' ?> required>
                            <label for="role-patient" class="flex items-center gap-3 bg-white/5 border-2 border-white/10 rounded-xl px-4 py-3 cursor-pointer transition-all hover:border-white/20">
                                <svg class="w-5 h-5 text-primary shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                                <div>
                                    <p class="text-white text-sm font-medium">Patient</p>
                                    <p class="text-white/35 text-xs">Book & consult</p>
                                </div>
                                <div class="role-dot ml-auto w-4 h-4 rounded-full border-2 border-white/20 shrink-0 transition-all"></div>
                            </label>
                        </div>
                        <div class="role-card">
                            <input type="radio" name="role" id="role-doctor" value="doctor" class="sr-only" <?= ($_POST['role'] ?? '') === 'doctor' ? 'checked' : '' ?>>
                            <label for="role-doctor" class="flex items-center gap-3 bg-white/5 border-2 border-white/10 rounded-xl px-4 py-3 cursor-pointer transition-all hover:border-white/20">
                                <svg class="w-5 h-5 text-accent shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                </svg>
                                <div>
                                    <p class="text-white text-sm font-medium">Doctor</p>
                                    <p class="text-white/35 text-xs">Manage & consult</p>
                                </div>
                                <div class="role-dot ml-auto w-4 h-4 rounded-full border-2 border-white/20 shrink-0 transition-all"></div>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="flex items-start gap-3 bg-white/5 border border-white/10 rounded-xl px-4 py-3">
                    <div class="flex items-center h-5 mt-0.5">
                        <input type="checkbox" name="terms" id="terms" value="1" class="w-4 h-4 rounded border-white/20 bg-white/5 cursor-pointer accent-sky-500" <?= isset($_POST['terms']) ? 'checked' : '' ?>>
                    </div>
                    <label for="terms" class="text-xs text-white/50 leading-relaxed cursor-pointer">
                        I agree to TeleCare AI's
                        <button type="button" onclick="openModal('termsModal')" class="text-primary hover:text-primary-dark font-medium transition-colors">Terms of Service</button>
                        and
                        <button type="button" onclick="openModal('privacyModal')" class="text-primary hover:text-primary-dark font-medium transition-colors">Privacy Policy</button>.
                        I understand that my health data will be handled securely and used only to provide medical services.
                    </label>
                </div>

                <button type="submit" class="w-full bg-primary hover:bg-primary-dark text-white font-semibold py-3 rounded-xl transition-all hover:scale-[1.01] active:scale-100 shadow-lg shadow-primary/25 text-sm">
                    Create Account
                </button>
            </div>
        </form>

        <p class="text-center text-sm text-white/40 mt-6">
            Already have an account?
            <a href="login.php" class="text-primary hover:text-primary-dark font-medium transition-colors"> Sign in</a>
        </p>
    </div>
</div>

<div class="modal-overlay" id="termsModal">
    <div class="bg-dark border border-white/10 rounded-2xl w-full max-w-lg max-h-[80vh] flex flex-col">
        <div class="flex items-center justify-between px-6 py-4 border-b border-white/10 shrink-0">
            <h2 class="text-white font-semibold">Terms of Service</h2>
            <button onclick="closeModal('termsModal')" class="text-white/40 hover:text-white transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div class="overflow-y-auto px-6 py-5 text-sm text-white/50 leading-relaxed space-y-4">
            <p class="text-white/70 font-medium">Last updated: February 2026</p>
            <p>Welcome to TeleCare AI. By using our platform, you agree to these Terms of Service. Please read them carefully.</p>
            <div>
                <p class="text-white/70 font-medium mb-1">1. Use of the Platform</p>
                <p>TeleCare AI is a telemedicine platform designed to connect patients with licensed medical professionals. You must be at least 18 years old to use this service. You agree to provide accurate and complete information when registering.</p>
            </div>
            <div>
                <p class="text-white/70 font-medium mb-1">2. Medical Disclaimer</p>
                <p>TeleCare AI does not replace in-person medical care. Consultations conducted through the platform are for informational and supplementary purposes. In case of a medical emergency, contact emergency services immediately.</p>
            </div>
            <div>
                <p class="text-white/70 font-medium mb-1">3. Account Responsibility</p>
                <p>You are responsible for maintaining the confidentiality of your account credentials. You must notify us immediately of any unauthorized access to your account.</p>
            </div>
            <div>
                <p class="text-white/70 font-medium mb-1">4. Prohibited Conduct</p>
                <p>You agree not to misuse the platform, impersonate others, submit false medical information, or attempt to access systems or data beyond your authorization.</p>
            </div>
            <div>
                <p class="text-white/70 font-medium mb-1">5. Termination</p>
                <p>We reserve the right to suspend or terminate accounts that violate these terms without prior notice.</p>
            </div>
            <div>
                <p class="text-white/70 font-medium mb-1">6. Changes to Terms</p>
                <p>We may update these Terms from time to time. Continued use of the platform after changes constitutes your acceptance of the updated Terms.</p>
            </div>
        </div>
        <div class="px-6 py-4 border-t border-white/10 shrink-0">
            <button onclick="acceptAndClose('termsModal')" class="w-full bg-primary hover:bg-primary-dark text-white font-medium py-2.5 rounded-xl text-sm transition-all">
                I Understand
            </button>
        </div>
    </div>
</div>

<div class="modal-overlay" id="privacyModal">
    <div class="bg-dark border border-white/10 rounded-2xl w-full max-w-lg max-h-[80vh] flex flex-col">
        <div class="flex items-center justify-between px-6 py-4 border-b border-white/10 shrink-0">
            <h2 class="text-white font-semibold">Privacy Policy</h2>
            <button onclick="closeModal('privacyModal')" class="text-white/40 hover:text-white transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div class="overflow-y-auto px-6 py-5 text-sm text-white/50 leading-relaxed space-y-4">
            <p class="text-white/70 font-medium">Last updated: February 2026</p>
            <p>TeleCare AI is committed to protecting your personal and medical information. This Privacy Policy explains how we collect, use, and safeguard your data.</p>
            <div>
                <p class="text-white/70 font-medium mb-1">1. Information We Collect</p>
                <p>We collect information you provide during registration (name, email, role), information from Google if you sign in via Google, and health-related information you share during consultations.</p>
            </div>
            <div>
                <p class="text-white/70 font-medium mb-1">2. How We Use Your Information</p>
                <p>Your information is used to provide and improve our services, facilitate doctor-patient communication, generate AI-assisted consultation summaries, and comply with applicable laws and regulations.</p>
            </div>
            <div>
                <p class="text-white/70 font-medium mb-1">3. Data Security</p>
                <p>We implement industry-standard security measures including encryption, secure servers, and role-based access control to protect your data from unauthorized access or disclosure.</p>
            </div>
            <div>
                <p class="text-white/70 font-medium mb-1">4. Data Sharing</p>
                <p>We do not sell your personal data. Your health information is only shared with the medical professionals directly involved in your care, or as required by law.</p>
            </div>
            <div>
                <p class="text-white/70 font-medium mb-1">5. Your Rights</p>
                <p>You have the right to access, correct, or request deletion of your personal data at any time by contacting our support team.</p>
            </div>
            <div>
                <p class="text-white/70 font-medium mb-1">6. Cookies</p>
                <p>We use session cookies solely for authentication purposes. We do not use tracking or advertising cookies.</p>
            </div>
            <div>
                <p class="text-white/70 font-medium mb-1">7. Contact</p>
                <p>If you have questions about this Privacy Policy, please contact us through the TeleCare AI support page.</p>
            </div>
        </div>
        <div class="px-6 py-4 border-t border-white/10 shrink-0">
            <button onclick="acceptAndClose('privacyModal')" class="w-full bg-primary hover:bg-primary-dark text-white font-medium py-2.5 rounded-xl text-sm transition-all">
                I Understand
            </button>
        </div>
    </div>
</div>

<script>
function openModal(id) {
    document.getElementById(id).classList.add('active');
    document.body.style.overflow = 'hidden';
}
function closeModal(id) {
    document.getElementById(id).classList.remove('active');
    document.body.style.overflow = '';
}
function acceptAndClose(id) {
    document.getElementById('terms').checked = true;
    closeModal(id);
}
document.querySelectorAll('.modal-overlay').forEach(overlay => {
    overlay.addEventListener('click', function(e) {
        if (e.target === this) closeModal(this.id);
    });
});

const passwordInput  = document.getElementById('password');
const confirmInput   = document.getElementById('confirm_password');
const confirmError   = document.getElementById('confirmError');

passwordInput.addEventListener('input', function() {
    const val = this.value;
    document.getElementById('req-len').className   = 'req ' + (val.length >= 8       ? 'met' : 'unmet');
    document.getElementById('req-upper').className = 'req ' + (/[A-Z]/.test(val)     ? 'met' : 'unmet');
    document.getElementById('req-num').className   = 'req ' + (/[0-9]/.test(val)     ? 'met' : 'unmet');
    if (confirmInput.value) {
        confirmError.classList.toggle('hidden', confirmInput.value === val);
    }
});

confirmInput.addEventListener('input', function() {
    confirmError.classList.toggle('hidden', this.value === passwordInput.value);
});

document.getElementById('togglePassword').addEventListener('click', () => {
    const isHidden = passwordInput.type === 'password';
    passwordInput.type = isHidden ? 'text' : 'password';
    document.getElementById('eyeOpen').classList.toggle('hidden', isHidden);
    document.getElementById('eyeClosed').classList.toggle('hidden', !isHidden);
});

document.getElementById('toggleConfirm').addEventListener('click', () => {
    const isHidden = confirmInput.type === 'password';
    confirmInput.type = isHidden ? 'text' : 'password';
    document.getElementById('eyeOpenC').classList.toggle('hidden', isHidden);
    document.getElementById('eyeClosedC').classList.toggle('hidden', !isHidden);
});

function handleGoogleRegister(response) {
    fetch('../auth/google-register.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ credential: response.credential })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            window.location.href = 'google-role-select.php';
        } else {
            const box = document.getElementById('errorBox');
            document.getElementById('errorMsg').textContent = data.message;
            box.classList.remove('hidden');
            box.classList.add('flex');
        }
    })
    .catch(() => {
        const box = document.getElementById('errorBox');
        document.getElementById('errorMsg').textContent = 'Something went wrong. Please try again.';
        box.classList.remove('hidden');
        box.classList.add('flex');
    });
}
</script>
</body>
</html>