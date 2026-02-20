<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TeleCare AI – Intelligent Teleconsultation</title>
    <link rel="icon" type="image/jpeg" href="images/logo.jpg">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="css/index.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: { DEFAULT: '#0ea5e9', dark: '#0284c7', light: '#e0f2fe' },
                        accent:  { DEFAULT: '#6366f1', dark: '#4f46e5' },
                        dark:    { DEFAULT: '#0f172a', card: '#1e293b' }
                    },
                    fontFamily: {
                        sans: ['Inter', 'system-ui', 'sans-serif']
                    }
                }
            }
        }
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body class="bg-dark text-white font-sans antialiased overflow-x-hidden">

<nav class="fixed top-0 left-0 right-0 z-50 bg-dark/90 backdrop-blur-md border-b border-white/5">
    <div class="max-w-7xl mx-auto px-6 h-16 flex items-center justify-between">
        <div class="flex items-center gap-2">
            <img src="images/logo.jpg" alt="TeleCare AI" class="h-9 w-auto object-contain rounded-lg">
            <span class="text-lg font-bold tracking-tight">Tele<span class="text-primary">Care</span> <span class="text-xs font-medium text-white/40 ml-1">AI</span></span>
        </div>
        <div class="hidden md:flex items-center gap-8 text-sm text-white/60">
            <a href="#features" class="hover:text-white transition-colors">Features</a>
            <a href="#how-it-works" class="hover:text-white transition-colors">How It Works</a>
            <a href="#about" class="hover:text-white transition-colors">About</a>
        </div>
        <div class="flex items-center gap-3">
            <a href="auth/login.php" class="text-sm text-white/70 hover:text-white px-4 py-2 transition-colors">Sign In</a>
            <a href="auth/register.php" class="text-sm bg-primary hover:bg-primary-dark px-4 py-2 rounded-lg font-medium transition-colors">Get Started</a>
        </div>
    </div>
</nav>

<section class="min-h-screen flex items-center justify-center pt-16 px-6 relative overflow-hidden">
    <div class="absolute inset-0 pointer-events-none">
        <div class="absolute top-1/4 left-1/4 w-96 h-96 bg-primary/10 rounded-full blur-3xl"></div>
        <div class="absolute bottom-1/4 right-1/4 w-96 h-96 bg-accent/10 rounded-full blur-3xl"></div>
    </div>
    <div class="max-w-5xl mx-auto text-center relative z-10">
        <span class="inline-flex items-center gap-2 bg-primary/10 border border-primary/20 text-primary text-xs font-semibold px-4 py-1.5 rounded-full mb-6">
            <span class="w-1.5 h-1.5 bg-primary rounded-full animate-pulse"></span>
            AI-Powered Healthcare Platform
        </span>
        <h1 class="text-5xl md:text-7xl font-extrabold leading-tight mb-6 tracking-tight">
            Smart Teleconsultation<br>
            <span class="bg-gradient-to-r from-primary to-accent bg-clip-text text-transparent">Built for Clinics</span>
        </h1>
        <p class="text-lg md:text-xl text-white/50 max-w-2xl mx-auto mb-10 leading-relaxed">
            Book appointments, consult doctors remotely, digitize lab results with OCR, and let AI summarize every consultation — all in one secure platform.
        </p>
        <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
            <a href="auth/register.php" class="w-full sm:w-auto bg-primary hover:bg-primary-dark text-white font-semibold px-8 py-4 rounded-xl transition-all hover:scale-105 shadow-lg shadow-primary/25">
                Start Free Consultation
            </a>
            <a href="#features" class="w-full sm:w-auto bg-white/5 hover:bg-white/10 border border-white/10 text-white font-semibold px-8 py-4 rounded-xl transition-all">
                Explore Features
            </a>
        </div>
        <div class="mt-16 grid grid-cols-3 gap-8 max-w-sm mx-auto text-center">
            <div>
                <div class="text-2xl font-bold text-white">AI</div>
                <div class="text-xs text-white/40 mt-1">Powered</div>
            </div>
            <div class="border-l border-r border-white/10">
                <div class="text-2xl font-bold text-white">OCR</div>
                <div class="text-xs text-white/40 mt-1">Document Scan</div>
            </div>
            <div>
                <div class="text-2xl font-bold text-white">24/7</div>
                <div class="text-xs text-white/40 mt-1">Available</div>
            </div>
        </div>
    </div>
</section>

<section id="features" class="py-24 px-6">
    <div class="max-w-7xl mx-auto">
        <div class="text-center mb-16">
            <h2 class="text-3xl md:text-4xl font-bold mb-4">Everything your clinic needs</h2>
            <p class="text-white/50 max-w-xl mx-auto">A unified platform that combines smart scheduling, AI documentation, and secure teleconsultation.</p>
        </div>
        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php
            $features = [
                ['icon'=>'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z', 'title'=>'Smart Scheduling', 'desc'=>'Calendar-based booking with real-time availability, conflict prevention, and automated email confirmations.', 'color'=>'from-blue-500 to-cyan-400'],
                ['icon'=>'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z', 'title'=>'Medical OCR', 'desc'=>'Upload lab results or prescriptions and watch TeleCare instantly extract and structure the data.', 'color'=>'from-violet-500 to-purple-400'],
                ['icon'=>'M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z', 'title'=>'Speech-to-Text', 'desc'=>'Voice notes converted to structured text via Whisper API — reducing documentation time significantly.', 'color'=>'from-emerald-500 to-teal-400'],
                ['icon'=>'M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z', 'title'=>'AI Summarization', 'desc'=>'OpenAI-powered summaries of consultations, including decisions, action items, and follow-up plans.', 'color'=>'from-amber-500 to-orange-400'],
                ['icon'=>'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z', 'title'=>'Secure Payments', 'desc'=>'Integrated PayMongo processing for GCash, Maya, cards, and online banking with live status updates.', 'color'=>'from-pink-500 to-rose-400'],
                ['icon'=>'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z', 'title'=>'Dashboard', 'desc'=>'Centralized management for appointments, staff, records, and system analytics with audit-ready logs.', 'color'=>'from-sky-500 to-blue-400'],
            ];
            foreach ($features as $f): ?>
            <div class="bg-dark-card border border-white/5 rounded-2xl p-6 hover:border-white/10 transition-all group">
                <div class="w-11 h-11 rounded-xl bg-gradient-to-br <?= $f['color'] ?> flex items-center justify-center mb-5">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?= $f['icon'] ?>"/>
                    </svg>
                </div>
                <h3 class="font-semibold text-white mb-2"><?= $f['title'] ?></h3>
                <p class="text-sm text-white/45 leading-relaxed"><?= $f['desc'] ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section id="how-it-works" class="py-24 px-6 bg-white/[0.02]">
    <div class="max-w-5xl mx-auto">
        <div class="text-center mb-16">
            <h2 class="text-3xl md:text-4xl font-bold mb-4">How It Works</h2>
            <p class="text-white/50 max-w-xl mx-auto">From registration to consultation in just a few steps.</p>
        </div>
        <div class="grid md:grid-cols-3 gap-10 relative">
            <div class="hidden md:block absolute top-8 left-1/3 right-1/3 h-0.5 bg-gradient-to-r from-primary/40 to-accent/40"></div>
            <?php
            $steps = [
                ['num'=>'01','title'=>'Create Account','desc'=>'Register as a patient, doctor, or admin with secure role-based authentication.'],
                ['num'=>'02','title'=>'Book Appointment','desc'=>'Choose a doctor, pick a time slot, and pay online — all in under 2 minutes.'],
                ['num'=>'03','title'=>'Consult & Review','desc'=>'Attend your teleconsultation and get an AI-generated summary automatically.'],
            ];
            foreach ($steps as $s): ?>
            <div class="text-center relative z-10">
                <div class="w-16 h-16 bg-gradient-to-br from-primary to-accent rounded-2xl flex items-center justify-center mx-auto mb-5 font-bold text-lg">
                    <?= $s['num'] ?>
                </div>
                <h3 class="font-semibold text-white mb-2"><?= $s['title'] ?></h3>
                <p class="text-sm text-white/45 leading-relaxed"><?= $s['desc'] ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="py-24 px-6">
    <div class="max-w-3xl mx-auto text-center">
        <h2 class="text-3xl md:text-4xl font-bold mb-4">Ready to modernize your clinic?</h2>
        <p class="text-white/50 mb-8">Join TeleCare AI today — it's free to get started.</p>
        <a href="auth/register.php" class="inline-block bg-gradient-to-r from-primary to-accent hover:opacity-90 text-white font-semibold px-10 py-4 rounded-xl transition-all hover:scale-105 shadow-xl">
            Create Your Account
        </a>
    </div>
</section>

<footer class="border-t border-white/5 py-8 px-6">
    <div class="max-w-7xl mx-auto flex flex-col md:flex-row items-center justify-between gap-4 text-sm text-white/30">
        <span>© 2026 TeleCare AI. All rights reserved.</span>
    </div>
</footer>

</body>
</html>