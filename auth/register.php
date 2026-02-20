<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header('Location: ../dashboard.php');
    exit;
}

require_once '../config/database.php';

$error   = '';
$success = '';
$fields  = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fields = [
        'first_name' => trim($_POST['first_name'] ?? ''),
        'last_name'  => trim($_POST['last_name']  ?? ''),
        'email'      => trim($_POST['email']      ?? ''),
        'role'       => $_POST['role']            ?? 'patient',
        'gender'     => $_POST['gender']          ?? '',
        'phone'      => trim($_POST['phone']      ?? ''),
    ];
    $password        = $_POST['password']         ?? '';
    $passwordConfirm = $_POST['password_confirm'] ?? '';

    $errors = [];

    if (empty($fields['first_name'])) $errors[] = 'First name is required.';
    if (empty($fields['last_name']))  $errors[] = 'Last name is required.';

    if (!filter_var($fields['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    } else {
        $chk = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        $chk->execute([$fields['email']]);
        if ($chk->fetch()) $errors[] = 'This email is already registered.';
    }

    $pwRegex = '/^(?=.*[A-Z])(?=.*[!@#$%^&*()_+\-=\[\]{};\':"\\|,.<>\/?]).{8,}$/';
    if (!preg_match($pwRegex, $password)) {
        $errors[] = 'Password must be at least 8 characters, include 1 uppercase letter and 1 special character.';
    }

    if ($password !== $passwordConfirm) {
        $errors[] = 'Passwords do not match.';
    }

    if (!in_array($fields['role'], ['patient', 'doctor'])) {
        $errors[] = 'Invalid role selected.';
    }

    if (empty($errors)) {
        $hashed = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
        $stmt = $pdo->prepare('INSERT INTO users (first_name, last_name, email, password, role, gender, phone) VALUES (?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([
            $fields['first_name'],
            $fields['last_name'],
            $fields['email'],
            $hashed,
            $fields['role'],
            $fields['gender'] ?: null,
            $fields['phone']  ?: null,
        ]);

        $newId = $pdo->lastInsertId();

        if ($fields['role'] === 'patient') {
            $pdo->prepare('INSERT INTO patient_profiles (user_id) VALUES (?)')->execute([$newId]);
        } elseif ($fields['role'] === 'doctor') {
            $pdo->prepare('INSERT INTO doctor_profiles (user_id) VALUES (?)')->execute([$newId]);
        }

        $log = $pdo->prepare('INSERT INTO activity_logs (user_id, action, description, ip_address) VALUES (?, ?, ?, ?)');
        $log->execute([$newId, 'register', 'New user registered', $_SERVER['REMOTE_ADDR']]);

        header('Location: login.php?registered=1');
        exit;
    } else {
        $error = implode(' ', $errors);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account – TeleCare AI</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="../css/register.css">
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
</head>
<body class="bg-dark min-h-screen flex font-sans antialiased">

<div class="hidden lg:flex lg:w-5/12 relative overflow-hidden">
    <div class="absolute inset-0 bg-gradient-to-br from-accent/20 via-primary/10 to-dark"></div>
    <div class="absolute top-1/4 left-1/4 w-80 h-80 bg-accent/10 rounded-full blur-3xl"></div>
    <div class="absolute bottom-1/3 right-1/3 w-60 h-60 bg-primary/15 rounded-full blur-3xl"></div>
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
                Join TeleCare AI<br>today.
            </h2>
            <p class="text-white/50 text-base leading-relaxed max-w-xs mb-10">
                Create your account in seconds and get access to smart teleconsultation, AI-powered records, and more.
            </p>
            <div class="space-y-3">
                <div class="flex items-center gap-3">
                    <div class="w-6 h-6 bg-primary/20 rounded-full flex items-center justify-center">
                        <svg class="w-3 h-3 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                        </svg>
                    </div>
                    <span class="text-sm text-white/60">Free to create an account</span>
                </div>
                <div class="flex items-center gap-3">
                    <div class="w-6 h-6 bg-primary/20 rounded-full flex items-center justify-center">
                        <svg class="w-3 h-3 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                        </svg>
                    </div>
                    <span class="text-sm text-white/60">Secure encrypted data storage</span>
                </div>
                <div class="flex items-center gap-3">
                    <div class="w-6 h-6 bg-primary/20 rounded-full flex items-center justify-center">
                        <svg class="w-3 h-3 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                        </svg>
                    </div>
                    <span class="text-sm text-white/60">Role-based access (Patient · Doctor)</span>
                </div>
            </div>
        </div>
        <p class="text-white/20 text-xs">© 2026 TeleCare AI · BS Information Systems</p>
    </div>
</div>

<div class="w-full lg:w-7/12 flex items-center justify-center p-8 overflow-y-auto">
    <div class="w-full max-w-lg py-8">
        <div class="mb-8">
            <a href="../index.php" class="flex items-center gap-2 mb-8 lg:hidden">
                <div class="w-8 h-8 bg-gradient-to-br from-primary to-accent rounded-lg flex items-center justify-center">
                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                    </svg>
                </div>
                <span class="font-bold">Tele<span class="text-primary">Care</span> AI</span>
            </a>
            <h1 class="text-3xl font-bold text-white mb-1">Create account</h1>
            <p class="text-white/45 text-sm">Fill in the details below to get started</p>
        </div>

        <?php if ($error): ?>
        <div class="flex items-start gap-3 bg-red-500/10 border border-red-500/20 text-red-400 text-sm px-4 py-3 rounded-xl mb-6">
            <svg class="w-5 h-5 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span><?= htmlspecialchars($error) ?></span>
        </div>
        <?php endif; ?>

        <form method="POST" action="" novalidate id="registerForm">
            <div class="space-y-5">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-white/70 mb-1.5" for="first_name">First Name</label>
                        <input
                            type="text"
                            id="first_name"
                            name="first_name"
                            value="<?= htmlspecialchars($fields['first_name'] ?? '') ?>"
                            placeholder="Juan"
                            class="w-full bg-white/5 border border-white/10 text-white placeholder-white/25 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all"
                        >
                        <p class="text-red-400 text-xs mt-1 hidden" id="firstNameError">Required.</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-white/70 mb-1.5" for="last_name">Last Name</label>
                        <input
                            type="text"
                            id="last_name"
                            name="last_name"
                            value="<?= htmlspecialchars($fields['last_name'] ?? '') ?>"
                            placeholder="dela Cruz"
                            class="w-full bg-white/5 border border-white/10 text-white placeholder-white/25 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all"
                        >
                        <p class="text-red-400 text-xs mt-1 hidden" id="lastNameError">Required.</p>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-white/70 mb-1.5" for="email">Email Address</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        value="<?= htmlspecialchars($fields['email'] ?? '') ?>"
                        placeholder="you@example.com"
                        class="w-full bg-white/5 border border-white/10 text-white placeholder-white/25 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all"
                    >
                    <p class="text-red-400 text-xs mt-1 hidden" id="emailError">Please enter a valid email address.</p>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-white/70 mb-1.5" for="role">I am a</label>
                        <select
                            id="role"
                            name="role"
                            class="w-full bg-white/5 border border-white/10 text-white rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all appearance-none"
                        >
                            <option value="patient" class="bg-slate-800" <?= ($fields['role'] ?? 'patient') === 'patient' ? 'selected' : '' ?>>Patient</option>
                            <option value="doctor"  class="bg-slate-800" <?= ($fields['role'] ?? '') === 'doctor'  ? 'selected' : '' ?>>Doctor</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-white/70 mb-1.5" for="gender">Gender</label>
                        <select
                            id="gender"
                            name="gender"
                            class="w-full bg-white/5 border border-white/10 text-white rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all appearance-none"
                        >
                            <option value="" class="bg-slate-800">Prefer not to say</option>
                            <option value="male"   class="bg-slate-800" <?= ($fields['gender'] ?? '') === 'male'   ? 'selected' : '' ?>>Male</option>
                            <option value="female" class="bg-slate-800" <?= ($fields['gender'] ?? '') === 'female' ? 'selected' : '' ?>>Female</option>
                            <option value="other"  class="bg-slate-800" <?= ($fields['gender'] ?? '') === 'other'  ? 'selected' : '' ?>>Other</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-white/70 mb-1.5" for="phone">Phone Number <span class="text-white/30">(optional)</span></label>
                    <input
                        type="tel"
                        id="phone"
                        name="phone"
                        value="<?= htmlspecialchars($fields['phone'] ?? '') ?>"
                        placeholder="+63 9XX XXX XXXX"
                        class="w-full bg-white/5 border border-white/10 text-white placeholder-white/25 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all"
                    >
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
                        >
                        <button type="button" class="toggle-eye absolute right-3 top-1/2 -translate-y-1/2 text-white/30 hover:text-white/70 transition-colors p-1" data-target="password">
                            <svg class="eye-open w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                            <svg class="eye-closed w-5 h-5 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                            </svg>
                        </button>
                    </div>
                    <div class="mt-2 space-y-1" id="pwRules">
                        <div class="flex items-center gap-2 text-xs" id="rule-length">
                            <div class="w-3 h-3 rounded-full border border-white/20 rule-dot"></div>
                            <span class="text-white/40 rule-text">At least 8 characters</span>
                        </div>
                        <div class="flex items-center gap-2 text-xs" id="rule-upper">
                            <div class="w-3 h-3 rounded-full border border-white/20 rule-dot"></div>
                            <span class="text-white/40 rule-text">1 uppercase letter</span>
                        </div>
                        <div class="flex items-center gap-2 text-xs" id="rule-special">
                            <div class="w-3 h-3 rounded-full border border-white/20 rule-dot"></div>
                            <span class="text-white/40 rule-text">1 special character (!@#$%...)</span>
                        </div>
                    </div>
                    <p class="text-red-400 text-xs mt-1 hidden" id="passwordError">Password does not meet requirements.</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-white/70 mb-1.5" for="password_confirm">Confirm Password</label>
                    <div class="relative">
                        <input
                            type="password"
                            id="password_confirm"
                            name="password_confirm"
                            placeholder="••••••••"
                            class="w-full bg-white/5 border border-white/10 text-white placeholder-white/25 rounded-xl px-4 py-3 pr-12 text-sm focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all"
                        >
                        <button type="button" class="toggle-eye absolute right-3 top-1/2 -translate-y-1/2 text-white/30 hover:text-white/70 transition-colors p-1" data-target="password_confirm">
                            <svg class="eye-open w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                            <svg class="eye-closed w-5 h-5 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                            </svg>
                        </button>
                    </div>
                    <p class="text-red-400 text-xs mt-1 hidden" id="confirmError">Passwords do not match.</p>
                </div>

                <button
                    type="submit"
                    class="w-full bg-primary hover:bg-primary-dark text-white font-semibold py-3 rounded-xl transition-all hover:scale-[1.01] active:scale-100 shadow-lg shadow-primary/25 text-sm mt-2"
                >
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

<script>
document.querySelectorAll('.toggle-eye').forEach(btn => {
    btn.addEventListener('click', () => {
        const input   = document.getElementById(btn.dataset.target);
        const isHidden = input.type === 'password';
        input.type = isHidden ? 'text' : 'password';
        btn.querySelector('.eye-open').classList.toggle('hidden', isHidden);
        btn.querySelector('.eye-closed').classList.toggle('hidden', !isHidden);
    });
});

const pwInput = document.getElementById('password');

function checkRule(ruleId, passed) {
    const row  = document.getElementById(ruleId);
    const dot  = row.querySelector('.rule-dot');
    const text = row.querySelector('.rule-text');
    if (passed) {
        dot.classList.add('bg-emerald-500', 'border-emerald-500');
        dot.classList.remove('border-white/20');
        text.classList.add('text-emerald-400');
        text.classList.remove('text-white/40');
    } else {
        dot.classList.remove('bg-emerald-500', 'border-emerald-500');
        dot.classList.add('border-white/20');
        text.classList.remove('text-emerald-400');
        text.classList.add('text-white/40');
    }
}

pwInput.addEventListener('input', () => {
    const v = pwInput.value;
    checkRule('rule-length',  v.length >= 8);
    checkRule('rule-upper',   /[A-Z]/.test(v));
    checkRule('rule-special', /[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(v));
});

document.getElementById('registerForm').addEventListener('submit', function(e) {
    let valid = true;

    const first   = document.getElementById('first_name');
    const last    = document.getElementById('last_name');
    const email   = document.getElementById('email');
    const pass    = document.getElementById('password');
    const confirm = document.getElementById('password_confirm');

    const emailReg = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    const pwReg    = /^(?=.*[A-Z])(?=.*[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]).{8,}$/;

    const show = (id, show) => document.getElementById(id).classList.toggle('hidden', !show);
    const mark = (el, err) => { el.classList.toggle('border-red-500', err); el.classList.toggle('border-white/10', !err); };

    if (!first.value.trim())      { mark(first, true); show('firstNameError', true); valid = false; } else { mark(first, false); show('firstNameError', false); }
    if (!last.value.trim())       { mark(last, true);  show('lastNameError', true);  valid = false; } else { mark(last, false);  show('lastNameError', false); }
    if (!emailReg.test(email.value.trim())) { mark(email, true); show('emailError', true); valid = false; } else { mark(email, false); show('emailError', false); }
    if (!pwReg.test(pass.value))  { mark(pass, true);  show('passwordError', true);  valid = false; } else { mark(pass, false);  show('passwordError', false); }
    if (pass.value !== confirm.value) { mark(confirm, true); show('confirmError', true); valid = false; } else { mark(confirm, false); show('confirmError', false); }

    if (!valid) e.preventDefault();
});
</script>
</body>
</html>