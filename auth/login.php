<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header('Location: ../dashboard.php');
    exit;
}

require_once '../config/database.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (empty($password)) {
        $error = 'Password is required.';
    } else {
        $stmt = $pdo->prepare('SELECT id, first_name, last_name, email, password, role, is_active FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            if (!$user['is_active']) {
                $error = 'Your account has been deactivated. Contact support.';
            } else {
                $_SESSION['user_id']   = $user['id'];
                $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['user_email']= $user['email'];

                $log = $pdo->prepare('INSERT INTO activity_logs (user_id, action, description, ip_address) VALUES (?, ?, ?, ?)');
                $log->execute([$user['id'], 'login', 'User logged in', $_SERVER['REMOTE_ADDR']]);

                header('Location: ../dashboard.php');
                exit;
            }
        } else {
            $error = 'Invalid email or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In – TeleCare AI</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="../css/login.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: { DEFAULT: '#0ea5e9', dark: '#0284c7', light: '#e0f2fe' },
                        accent:  '#6366f1',
                        dark:    { DEFAULT: '#0f172a', card: '#1e293b' }
                    },
                    fontFamily: { sans: ['Inter', 'system-ui', 'sans-serif'] }
                }
            }
        }
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="bg-dark min-h-screen flex font-sans antialiased">

<div class="hidden lg:flex lg:w-1/2 relative overflow-hidden">
    <div class="absolute inset-0 bg-gradient-to-br from-primary/20 via-accent/10 to-dark"></div>
    <div class="absolute top-1/3 left-1/3 w-72 h-72 bg-primary/15 rounded-full blur-3xl"></div>
    <div class="absolute bottom-1/3 right-1/4 w-56 h-56 bg-accent/15 rounded-full blur-3xl"></div>
    <div class="relative z-10 flex flex-col justify-between p-12 w-full">
        <a href="../index.php" class="flex items-center gap-2">
            <div class="w-9 h-9 bg-gradient-to-br from-primary to-accent rounded-xl flex items-center justify-center">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                </svg>
            </div>
            <span class="text-xl font-bold">Tele<span class="text-primary">Care</span> <span class="text-white/30 text-sm">AI</span></span>
        </a>
        <div>
            <h2 class="text-4xl font-extrabold text-white leading-tight mb-4">
                Your health,<br>our priority.
            </h2>
            <p class="text-white/50 text-base leading-relaxed max-w-sm">
                Access your patient records, book appointments, and consult with doctors — all from one intelligent platform.
            </p>
            <div class="mt-10 space-y-4">
                <?php
                $highlights = [
                    ['AI-powered consultation summaries', 'M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z'],
                    ['Medical document OCR & digitization', 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
                    ['Secure role-based access control', 'M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z'],
                ];
                foreach ($highlights as [$label, $icon]): ?>
                <div class="flex items-center gap-3 text-sm text-white/60">
                    <div class="w-8 h-8 bg-white/5 rounded-lg flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?= $icon ?>"/>
                        </svg>
                    </div>
                    <?= $label ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <p class="text-white/20 text-xs">© 2026 TeleCare AI · BS Information Systems</p>
    </div>
</div>

<div class="w-full lg:w-1/2 flex items-center justify-center p-8">
    <div class="w-full max-w-md">
        <div class="mb-8">
            <a href="../index.php" class="flex items-center gap-2 mb-8 lg:hidden">
                <div class="w-8 h-8 bg-gradient-to-br from-primary to-accent rounded-lg flex items-center justify-center">
                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                    </svg>
                </div>
                <span class="font-bold">Tele<span class="text-primary">Care</span> AI</span>
            </a>
            <h1 class="text-3xl font-bold text-white mb-1">Welcome back</h1>
            <p class="text-white/45 text-sm">Sign in to your TeleCare account</p>
        </div>

        <?php if (isset($_GET['registered'])): ?>
        <div class="flex items-center gap-3 bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-sm px-4 py-3 rounded-xl mb-6">
            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            Account created successfully! You can now sign in.
        </div>
        <?php endif; ?>

        <?php if ($error): ?>
        <div class="flex items-center gap-3 bg-red-500/10 border border-red-500/20 text-red-400 text-sm px-4 py-3 rounded-xl mb-6">
            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <?= htmlspecialchars($error) ?>
        </div>
        <?php endif; ?>

        <form method="POST" action="" novalidate id="loginForm">
            <div class="space-y-5">
                <div>
                    <label class="block text-sm font-medium text-white/70 mb-1.5" for="email">Email Address</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                        placeholder="you@example.com"
                        class="w-full bg-white/5 border border-white/10 text-white placeholder-white/25 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all"
                        required
                    >
                    <p class="text-red-400 text-xs mt-1 hidden" id="emailError">Please enter a valid email address.</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-white/70 mb-1.5" for="password">Password</label>
                    <div class="relative">
                        <input
                            type="password"
                            id="password"
                            name="password"
                            placeholder="••••••••"
                            class="w-full bg-white/5 border border-white/10 text-white placeholder-white/25 rounded-xl px-4 py-3 pr-12 text-sm focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all"
                            required
                        >
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
                    <p class="text-red-400 text-xs mt-1 hidden" id="passwordError">Password is required.</p>
                </div>

                <button
                    type="submit"
                    class="w-full bg-primary hover:bg-primary-dark text-white font-semibold py-3 rounded-xl transition-all hover:scale-[1.01] active:scale-100 shadow-lg shadow-primary/25 text-sm mt-2"
                >
                    Sign In
                </button>
            </div>
        </form>

        <p class="text-center text-sm text-white/40 mt-6">
            Don't have an account?
            <a href="register.php" class="text-primary hover:text-primary-dark font-medium transition-colors"> Create one</a>
        </p>
    </div>
</div>

<script>
const togglePassword = document.getElementById('togglePassword');
const passwordInput  = document.getElementById('password');
const eyeOpen        = document.getElementById('eyeOpen');
const eyeClosed      = document.getElementById('eyeClosed');

togglePassword.addEventListener('click', () => {
    const isHidden = passwordInput.type === 'password';
    passwordInput.type = isHidden ? 'text' : 'password';
    eyeOpen.classList.toggle('hidden', isHidden);
    eyeClosed.classList.toggle('hidden', !isHidden);
});

document.getElementById('loginForm').addEventListener('submit', function(e) {
    let valid = true;
    const email    = document.getElementById('email');
    const password = document.getElementById('password');
    const emailErr = document.getElementById('emailError');
    const passErr  = document.getElementById('passwordError');
    const emailReg = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

    if (!emailReg.test(email.value.trim())) {
        email.classList.add('border-red-500');
        emailErr.classList.remove('hidden');
        valid = false;
    } else {
        email.classList.remove('border-red-500');
        emailErr.classList.add('hidden');
    }

    if (!password.value) {
        password.classList.add('border-red-500');
        passErr.classList.remove('hidden');
        valid = false;
    } else {
        password.classList.remove('border-red-500');
        passErr.classList.add('hidden');
    }

    if (!valid) e.preventDefault();
});
</script>
</body>
</html>