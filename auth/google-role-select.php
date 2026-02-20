<?php
session_start();
if (isset($_SESSION['user_id'])) { header('Location: ../dashboard.php'); exit; }
if (!isset($_SESSION['google_pending'])) { header('Location: login.php'); exit; }

require_once '../config/database.php';

$error   = '';
$pending = $_SESSION['google_pending'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $role  = $_POST['role'] ?? '';
    $terms = $_POST['terms'] ?? '';

    if (!in_array($role, ['patient', 'doctor'])) {
        $error = 'Please select a valid role.';
    } elseif (!$terms) {
        $error = 'You must agree to the Terms of Service and Privacy Policy.';
    } else {
        $password = password_hash(bin2hex(random_bytes(16)), PASSWORD_BCRYPT);

        $stmt = $pdo->prepare('INSERT INTO users (first_name, last_name, email, password, role, google_id, is_active) VALUES (?, ?, ?, ?, ?, ?, 1)');
        $stmt->execute([
            $pending['first_name'],
            $pending['last_name'],
            $pending['email'],
            $password,
            $role,
            $pending['google_id'],
        ]);

        $newUserId = $pdo->lastInsertId();

        if ($role === 'doctor') {
            $pdo->prepare('INSERT INTO doctor_profiles (user_id) VALUES (?)')->execute([$newUserId]);
        }

        $pdo->prepare('INSERT INTO activity_logs (user_id, action, description, ip_address) VALUES (?, ?, ?, ?)')
            ->execute([$newUserId, 'register', 'User registered via Google', $_SERVER['REMOTE_ADDR']]);

        unset($_SESSION['google_pending']);

        $_SESSION['user_id']    = $newUserId;
        $_SESSION['user_name']  = $pending['first_name'] . ' ' . $pending['last_name'];
        $_SESSION['user_role']  = $role;
        $_SESSION['user_email'] = $pending['email'];

        header('Location: ../dashboard.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Choose Your Role – TeleCare AI</title>
    <script src="https://cdn.tailwindcss.com"></script>
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
    </style>
</head>
<body class="bg-dark min-h-screen flex items-center justify-center font-sans antialiased p-6">

<div class="w-full max-w-lg">

    <div class="text-center mb-8">
        <div class="w-16 h-16 bg-gradient-to-br from-primary to-accent rounded-2xl flex items-center justify-center mx-auto mb-4">
            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
            </svg>
        </div>
        <h1 class="text-2xl font-bold text-white mb-1">Welcome, <?= htmlspecialchars($pending['first_name']) ?>!</h1>
        <p class="text-white/45 text-sm">One last step — choose how you'll use TeleCare AI</p>
        <p class="text-white/30 text-xs mt-1"><?= htmlspecialchars($pending['email']) ?></p>
    </div>

    <?php if ($error): ?>
    <div class="flex items-center gap-3 bg-red-500/10 border border-red-500/20 text-red-400 text-sm px-4 py-3 rounded-xl mb-6">
        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>

    <form method="POST" id="roleForm">
        <div class="grid grid-cols-2 gap-4 mb-6">

            <div class="role-card">
                <input type="radio" name="role" id="role-patient" value="patient" class="sr-only" required>
                <label for="role-patient" class="block bg-white/5 border-2 border-white/10 rounded-2xl p-5 cursor-pointer transition-all hover:border-white/20">
                    <div class="flex items-start justify-between mb-4">
                        <div class="w-11 h-11 bg-primary/10 rounded-xl flex items-center justify-center">
                            <svg class="w-6 h-6 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                        </div>
                        <div class="role-dot w-5 h-5 rounded-full border-2 border-white/20 transition-all"></div>
                    </div>
                    <p class="text-white font-semibold text-sm mb-1">Patient</p>
                    <p class="text-white/40 text-xs leading-relaxed">Book appointments and attend online consultations with doctors</p>
                </label>
            </div>

            <div class="role-card">
                <input type="radio" name="role" id="role-doctor" value="doctor" class="sr-only">
                <label for="role-doctor" class="block bg-white/5 border-2 border-white/10 rounded-2xl p-5 cursor-pointer transition-all hover:border-white/20">
                    <div class="flex items-start justify-between mb-4">
                        <div class="w-11 h-11 bg-accent/10 rounded-xl flex items-center justify-center">
                            <svg class="w-6 h-6 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                            </svg>
                        </div>
                        <div class="role-dot w-5 h-5 rounded-full border-2 border-white/20 transition-all"></div>
                    </div>
                    <p class="text-white font-semibold text-sm mb-1">Doctor</p>
                    <p class="text-white/40 text-xs leading-relaxed">Manage appointments, conduct consultations, and generate AI summaries</p>
                </label>
            </div>

        </div>

        <div class="flex items-start gap-3 bg-white/5 border border-white/10 rounded-xl px-4 py-3 mb-5">
            <div class="flex items-center h-5 mt-0.5">
                <input type="checkbox" name="terms" id="terms" value="1" class="w-4 h-4 rounded border-white/20 bg-white/5 text-primary cursor-pointer accent-sky-500" <?= isset($_POST['terms']) ? 'checked' : '' ?>>
            </div>
            <label for="terms" class="text-xs text-white/50 leading-relaxed cursor-pointer">
                I agree to TeleCare AI's
                <button type="button" onclick="openModal('termsModal')" class="text-primary hover:text-primary-dark font-medium transition-colors">Terms of Service</button>
                and
                <button type="button" onclick="openModal('privacyModal')" class="text-primary hover:text-primary-dark font-medium transition-colors">Privacy Policy</button>.
                I understand that my health data will be handled securely and used only to provide medical services.
            </label>
        </div>

        <button type="submit" class="w-full bg-primary hover:bg-primary-dark text-white font-semibold py-3 rounded-xl transition-all text-sm shadow-lg shadow-primary/25">
            Continue to TeleCare AI
        </button>
    </form>

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
                <p>TeleCare AI is a telemedicine platform designed to connect patients and doctors with licensed medical professionals. You must be at least 15 years old to use this service. You agree to provide accurate and complete information when registering.</p>
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

document.getElementById('roleForm').addEventListener('submit', function(e) {
    const role  = document.querySelector('input[name="role"]:checked');
    const terms = document.getElementById('terms');
    if (!role) {
        e.preventDefault();
        alert('Please select a role.');
        return;
    }
    if (!terms.checked) {
        e.preventDefault();
        alert('Please agree to the Terms of Service and Privacy Policy.');
    }
});
</script>
</body>
</html>