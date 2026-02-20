<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: auth/login.php');
    exit;
}

require_once 'config/database.php';

$userId   = $_SESSION['user_id'];
$userName = $_SESSION['user_name'];
$userRole = $_SESSION['user_role'];

$stats = [];

if ($userRole === 'patient') {
    $q = $pdo->prepare('SELECT COUNT(*) FROM appointments WHERE patient_id = ?');
    $q->execute([$userId]);
    $stats['total_appointments'] = $q->fetchColumn();

    $q = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE patient_id = ? AND status = 'completed'");
    $q->execute([$userId]);
    $stats['completed'] = $q->fetchColumn();

    $q = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE patient_id = ? AND status = 'pending'");
    $q->execute([$userId]);
    $stats['pending'] = $q->fetchColumn();

    $q = $pdo->prepare("SELECT COUNT(*) FROM medical_documents WHERE patient_id = ?");
    $q->execute([$userId]);
    $stats['documents'] = $q->fetchColumn();

    $q = $pdo->prepare("SELECT a.*, CONCAT(u.first_name, ' ', u.last_name) AS doctor_name, dp.specialization FROM appointments a JOIN users u ON a.doctor_id = u.id LEFT JOIN doctor_profiles dp ON u.id = dp.user_id WHERE a.patient_id = ? ORDER BY a.appointment_date DESC LIMIT 5");
    $q->execute([$userId]);
    $recentAppointments = $q->fetchAll();

} elseif ($userRole === 'doctor') {
    $q = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE doctor_id = ?");
    $q->execute([$userId]);
    $stats['total_appointments'] = $q->fetchColumn();

    $q = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE doctor_id = ? AND DATE(appointment_date) = CURDATE()");
    $q->execute([$userId]);
    $stats['today'] = $q->fetchColumn();

    $q = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE doctor_id = ? AND status = 'pending'");
    $q->execute([$userId]);
    $stats['pending'] = $q->fetchColumn();

    $q = $pdo->prepare("SELECT COUNT(DISTINCT patient_id) FROM appointments WHERE doctor_id = ?");
    $q->execute([$userId]);
    $stats['patients'] = $q->fetchColumn();

    $q = $pdo->prepare("SELECT a.*, CONCAT(u.first_name, ' ', u.last_name) AS patient_name FROM appointments a JOIN users u ON a.patient_id = u.id WHERE a.doctor_id = ? ORDER BY a.appointment_date DESC LIMIT 5");
    $q->execute([$userId]);
    $recentAppointments = $q->fetchAll();

} else {
    $q = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role = 'patient'");
    $q->execute();
    $stats['patients'] = $q->fetchColumn();

    $q = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role = 'doctor'");
    $q->execute();
    $stats['doctors'] = $q->fetchColumn();

    $q = $pdo->prepare("SELECT COUNT(*) FROM appointments");
    $q->execute();
    $stats['total_appointments'] = $q->fetchColumn();

    $q = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE DATE(appointment_date) = CURDATE()");
    $q->execute();
    $stats['today'] = $q->fetchColumn();

    $q = $pdo->prepare("SELECT a.*, CONCAT(p.first_name, ' ', p.last_name) AS patient_name, CONCAT(d.first_name, ' ', d.last_name) AS doctor_name FROM appointments a JOIN users p ON a.patient_id = p.id JOIN users d ON a.doctor_id = d.id ORDER BY a.created_at DESC LIMIT 5");
    $q->execute();
    $recentAppointments = $q->fetchAll();
}

$activePage = 'dashboard';

$statusColors = [
    'pending'   => 'bg-amber-500/10 text-amber-400 border-amber-500/20',
    'confirmed' => 'bg-blue-500/10 text-blue-400 border-blue-500/20',
    'completed' => 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20',
    'cancelled' => 'bg-red-500/10 text-red-400 border-red-500/20',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard – TeleCare AI</title>
    <link rel="icon" type="image/jpeg" href="images/logo.jpg">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="css/dashboard.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: { DEFAULT: '#0ea5e9', dark: '#0284c7' },
                        accent:  '#6366f1',
                        dark:    { DEFAULT: '#0f172a', card: '#1e293b', sidebar: '#111827' }
                    },
                    fontFamily: { sans: ['Inter', 'system-ui', 'sans-serif'] }
                }
            }
        }
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="bg-dark text-white font-sans antialiased flex h-screen overflow-hidden">

<?php include 'includes/sidebar.php'; ?>

<main id="mainContent" class="flex-1 min-h-screen overflow-y-auto transition-all duration-300">
    <header class="h-16 flex items-center justify-between px-6 border-b border-white/5 bg-dark/50 sticky top-0 backdrop-blur-md z-10">
        <div class="flex items-center gap-4">
            <button onclick="toggleSidebar()" id="hamburgerBtn" class="w-9 h-9 bg-white/5 hover:bg-white/10 rounded-xl flex items-center justify-center transition-colors" title="Toggle sidebar">
                <svg class="w-5 h-5 text-white/60" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>
            <div>
                <h1 class="text-lg font-semibold text-white">Dashboard</h1>
                <p class="text-xs text-white/40"><?= date('l, F j, Y') ?></p>
            </div>
        </div>
        <div class="flex items-center gap-3">
            <button class="w-9 h-9 bg-white/5 hover:bg-white/10 rounded-xl flex items-center justify-center transition-colors relative">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                </svg>
            </button>
            <div class="w-9 h-9 bg-gradient-to-br from-primary to-accent rounded-full flex items-center justify-center text-sm font-bold">
                <?= strtoupper(substr($userName, 0, 1)) ?>
            </div>
        </div>
    </header>

    <div class="p-8">
        <div class="mb-8">
            <h2 class="text-xl font-semibold text-white mb-1">
                Good <?= (date('H') < 12) ? 'morning' : ((date('H') < 17) ? 'afternoon' : 'evening') ?>, <?= htmlspecialchars(explode(' ', $userName)[0]) ?> 👋
            </h2>
            <p class="text-white/45 text-sm">Here's what's happening with your <?= $userRole === 'admin' ? 'clinic' : 'account' ?> today.</p>
        </div>

        <div class="grid grid-cols-2 xl:grid-cols-4 gap-5 mb-8">
            <?php
            $statCards = [];
            if ($userRole === 'patient') {
                $statCards = [
                    ['label' => 'Total Appointments', 'value' => $stats['total_appointments'], 'icon' => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z', 'color' => 'from-blue-500 to-cyan-400'],
                    ['label' => 'Completed',           'value' => $stats['completed'],          'icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',                                                  'color' => 'from-emerald-500 to-teal-400'],
                    ['label' => 'Pending',             'value' => $stats['pending'],            'icon' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',                                                    'color' => 'from-amber-500 to-orange-400'],
                    ['label' => 'Documents',           'value' => $stats['documents'],          'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z', 'color' => 'from-violet-500 to-purple-400'],
                ];
            } elseif ($userRole === 'doctor') {
                $statCards = [
                    ['label' => 'Total Appointments', 'value' => $stats['total_appointments'], 'icon' => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z', 'color' => 'from-blue-500 to-cyan-400'],
                    ['label' => "Today's Patients",    'value' => $stats['today'],              'icon' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z', 'color' => 'from-emerald-500 to-teal-400'],
                    ['label' => 'Pending Review',      'value' => $stats['pending'],            'icon' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',                                                    'color' => 'from-amber-500 to-orange-400'],
                    ['label' => 'Total Patients',      'value' => $stats['patients'],           'icon' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z', 'color' => 'from-violet-500 to-purple-400'],
                ];
            } else {
                $statCards = [
                    ['label' => 'Total Patients',      'value' => $stats['patients'],           'icon' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z', 'color' => 'from-blue-500 to-cyan-400'],
                    ['label' => 'Doctors',             'value' => $stats['doctors'],            'icon' => 'M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0m6 2a9 9 0 11-18 0 9 9 0 0118 0z', 'color' => 'from-emerald-500 to-teal-400'],
                    ['label' => 'All Appointments',    'value' => $stats['total_appointments'], 'icon' => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z', 'color' => 'from-amber-500 to-orange-400'],
                    ['label' => "Today's Sessions",    'value' => $stats['today'],              'icon' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',                                                    'color' => 'from-violet-500 to-purple-400'],
                ];
            }
            foreach ($statCards as $card): ?>
            <div class="bg-dark-card border border-white/5 rounded-2xl p-5">
                <div class="flex items-start justify-between mb-4">
                    <div class="w-10 h-10 bg-gradient-to-br <?= $card['color'] ?> rounded-xl flex items-center justify-center">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?= $card['icon'] ?>"/>
                        </svg>
                    </div>
                </div>
                <div class="text-3xl font-bold text-white mb-1"><?= number_format($card['value']) ?></div>
                <div class="text-xs text-white/40"><?= $card['label'] ?></div>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="bg-dark-card border border-white/5 rounded-2xl">
            <div class="flex items-center justify-between p-6 border-b border-white/5">
                <h3 class="font-semibold text-white">Recent Appointments</h3>
                <a href="#" class="text-xs text-primary hover:text-primary-dark transition-colors">View all →</a>
            </div>
            <?php if (empty($recentAppointments)): ?>
            <div class="flex flex-col items-center justify-center py-16 text-center px-6">
                <div class="w-14 h-14 bg-white/5 rounded-2xl flex items-center justify-center mb-4">
                    <svg class="w-7 h-7 text-white/20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                </div>
                <p class="text-white/40 text-sm">No appointments yet</p>
                <?php if ($userRole === 'patient'): ?>
                <a href="#" class="mt-3 text-xs text-primary hover:underline">Book your first appointment</a>
                <?php endif; ?>
            </div>
            <?php else: ?>
            <div class="divide-y divide-white/5">
                <?php foreach ($recentAppointments as $appt): ?>
                <div class="flex items-center justify-between px-6 py-4 hover:bg-white/[0.02] transition-colors">
                    <div class="flex items-center gap-4">
                        <div class="w-9 h-9 bg-white/5 rounded-xl flex items-center justify-center shrink-0">
                            <svg class="w-4 h-4 text-white/40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-white">
                                <?= htmlspecialchars($appt['doctor_name'] ?? $appt['patient_name'] ?? 'N/A') ?>
                                <?php if (isset($appt['specialization']) && $appt['specialization']): ?>
                                <span class="text-white/30 font-normal text-xs">(<?= htmlspecialchars($appt['specialization']) ?>)</span>
                                <?php endif; ?>
                            </p>
                            <p class="text-xs text-white/40">
                                <?= date('M j, Y', strtotime($appt['appointment_date'])) ?>
                                at <?= date('g:i A', strtotime($appt['appointment_time'])) ?>
                            </p>
                        </div>
                    </div>
                    <span class="text-xs px-2.5 py-1 rounded-full border <?= $statusColors[$appt['status']] ?? 'bg-white/5 text-white/40 border-white/10' ?> capitalize">
                        <?= $appt['status'] ?>
                    </span>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php include 'includes/sidebar_scripts.php'; ?>

</body>
</html>