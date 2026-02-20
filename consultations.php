<?php
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: auth/login.php'); exit; }

require_once 'config/database.php';

$userId    = $_SESSION['user_id'];
$userName  = $_SESSION['user_name'];
$userRole  = $_SESSION['user_role'];
$activePage = 'consultations';

$filterStatus = $_GET['status'] ?? 'all';
$search       = trim($_GET['search'] ?? '');
$page         = max(1, intval($_GET['page'] ?? 1));
$perPage      = 10;
$offset       = ($page - 1) * $perPage;

$statusColors = [
    'pending'   => 'bg-amber-500/10 text-amber-400 border-amber-500/20',
    'confirmed' => 'bg-blue-500/10 text-blue-400 border-blue-500/20',
    'completed' => 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20',
    'cancelled' => 'bg-red-500/10 text-red-400 border-red-500/20',
];

if ($userRole === 'patient') {
    $baseWhere  = "WHERE a.patient_id = ? AND a.status IN ('confirmed','completed')";
    $baseParams = [$userId];
    if ($filterStatus !== 'all') { $baseWhere .= " AND a.status = ?"; $baseParams[] = $filterStatus; }
    if ($search) { $baseWhere .= " AND CONCAT(d.first_name,' ',d.last_name) LIKE ?"; $baseParams[] = "%$search%"; }

    $countQ = $pdo->prepare("SELECT COUNT(*) FROM appointments a JOIN users d ON a.doctor_id = d.id $baseWhere");
    $countQ->execute($baseParams);
    $total = $countQ->fetchColumn();

    $stmt = $pdo->prepare("SELECT a.id AS appointment_id, a.appointment_date, a.appointment_time, a.status, a.chief_complaint,
        CONCAT(d.first_name,' ',d.last_name) AS other_name,
        dp.specialization,
        c.id AS consult_id, c.started_at, c.ended_at, c.ai_summary, c.transcript
        FROM appointments a
        JOIN users d ON a.doctor_id = d.id
        LEFT JOIN doctor_profiles dp ON d.id = dp.user_id
        LEFT JOIN consultations c ON a.id = c.appointment_id
        $baseWhere ORDER BY a.appointment_date DESC, a.appointment_time DESC LIMIT $perPage OFFSET $offset");
    $stmt->execute($baseParams);

} else {
    $baseWhere  = "WHERE a.doctor_id = ? AND a.status IN ('confirmed','completed')";
    $baseParams = [$userId];
    if ($filterStatus !== 'all') { $baseWhere .= " AND a.status = ?"; $baseParams[] = $filterStatus; }
    if ($search) { $baseWhere .= " AND CONCAT(p.first_name,' ',p.last_name) LIKE ?"; $baseParams[] = "%$search%"; }

    $countQ = $pdo->prepare("SELECT COUNT(*) FROM appointments a JOIN users p ON a.patient_id = p.id $baseWhere");
    $countQ->execute($baseParams);
    $total = $countQ->fetchColumn();

    $stmt = $pdo->prepare("SELECT a.id AS appointment_id, a.appointment_date, a.appointment_time, a.status, a.chief_complaint,
        CONCAT(p.first_name,' ',p.last_name) AS other_name,
        c.id AS consult_id, c.started_at, c.ended_at, c.ai_summary, c.transcript
        FROM appointments a
        JOIN users p ON a.patient_id = p.id
        LEFT JOIN consultations c ON a.id = c.appointment_id
        $baseWhere ORDER BY a.appointment_date DESC, a.appointment_time DESC LIMIT $perPage OFFSET $offset");
    $stmt->execute($baseParams);
}

$sessions    = $stmt->fetchAll();
$totalPages  = ceil($total / $perPage);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consultations – TeleCare AI</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="icon" type="image/jpeg" href="images/logo.jpg">
    <script>
        tailwind.config = {
            theme: { extend: {
                colors: {
                    primary: { DEFAULT: '#0ea5e9', dark: '#0284c7' },
                    accent: '#6366f1',
                    dark: { DEFAULT: '#0f172a', card: '#1e293b', sidebar: '#111827' }
                },
                fontFamily: { sans: ['Inter','system-ui','sans-serif'] }
            }}
        }
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="bg-dark text-white font-sans antialiased flex h-screen overflow-hidden">

<?php include 'includes/sidebar.php'; ?>

<main class="flex-1 min-h-screen overflow-y-auto">
    <header class="h-16 flex items-center justify-between px-6 border-b border-white/5 bg-dark/50 sticky top-0 backdrop-blur-md z-10">
        <div class="flex items-center gap-4">
            <button onclick="toggleSidebar()" class="w-9 h-9 bg-white/5 hover:bg-white/10 rounded-xl flex items-center justify-center transition-colors">
                <svg class="w-5 h-5 text-white/60" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
            </button>
            <div>
                <h1 class="text-lg font-semibold text-white">Consultations</h1>
                <p class="text-xs text-white/40"><?= date('l, F j, Y') ?></p>
            </div>
        </div>
        <div class="w-9 h-9 bg-gradient-to-br from-primary to-accent rounded-full flex items-center justify-center text-sm font-bold">
            <?= strtoupper(substr($userName, 0, 1)) ?>
        </div>
    </header>

    <div class="p-6">

        <!-- Info banner -->
        <div class="bg-primary/5 border border-primary/15 rounded-2xl p-4 mb-6 flex items-start gap-3">
            <svg class="w-5 h-5 text-primary shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <div>
                <p class="text-sm font-medium text-white">Consultations are available for confirmed appointments.</p>
                <p class="text-xs text-white/45 mt-0.5">
                    <?= $userRole === 'doctor'
                        ? 'Click "Start Session" to open the consultation room, add notes, and generate an AI summary after the call.'
                        : 'Click "Join Session" to enter the consultation room when your doctor has started the session.' ?>
                </p>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-dark-card border border-white/5 rounded-2xl mb-4">
            <form method="GET" class="flex flex-col sm:flex-row gap-3 p-4">
                <div class="relative flex-1">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-white/30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input type="text" name="search" value="<?= htmlspecialchars($search) ?>"
                           placeholder="Search <?= $userRole === 'patient' ? 'doctor' : 'patient' ?> name..."
                           class="w-full bg-white/5 border border-white/10 text-white placeholder-white/25 rounded-xl pl-9 pr-4 py-2.5 text-sm focus:outline-none focus:border-primary transition-all">
                </div>
                <select name="status" onchange="this.form.submit()" class="bg-white/5 border border-white/10 text-white rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-primary appearance-none">
                    <option value="all"       class="bg-slate-800" <?= $filterStatus==='all'       ?'selected':'' ?>>All</option>
                    <option value="confirmed" class="bg-slate-800" <?= $filterStatus==='confirmed' ?'selected':'' ?>>Confirmed</option>
                    <option value="completed" class="bg-slate-800" <?= $filterStatus==='completed' ?'selected':'' ?>>Completed</option>
                </select>
                <button type="submit" class="bg-primary hover:bg-primary-dark text-white text-sm font-medium px-5 py-2.5 rounded-xl transition-all">Search</button>
                <?php if ($search || $filterStatus !== 'all'): ?>
                <a href="consultations.php" class="bg-white/5 hover:bg-white/10 text-white/60 text-sm px-4 py-2.5 rounded-xl transition-all text-center">Clear</a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Sessions list -->
        <?php if (empty($sessions)): ?>
        <div class="bg-dark-card border border-white/5 rounded-2xl flex flex-col items-center justify-center py-20 text-center px-6">
            <div class="w-16 h-16 bg-white/5 rounded-2xl flex items-center justify-center mb-4">
                <svg class="w-8 h-8 text-white/20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.069A1 1 0 0121 8.87v6.26a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                </svg>
            </div>
            <p class="text-white/50 font-medium mb-1">No consultations yet</p>
            <p class="text-white/30 text-sm">Consultations appear here once appointments are confirmed.</p>
            <a href="appointments.php" class="mt-4 text-xs text-primary hover:underline">View Appointments →</a>
        </div>
        <?php else: ?>
        <div class="space-y-4">
            <?php foreach ($sessions as $s):
                $hasSummary = !empty($s['ai_summary']);
                $hasStarted = !empty($s['started_at']);
                $isEnded    = !empty($s['ended_at']);
                $isToday    = date('Y-m-d') === date('Y-m-d', strtotime($s['appointment_date']));
                $summaryData = $hasSummary ? json_decode($s['ai_summary'], true) : null;
            ?>
            <div class="bg-dark-card border border-white/5 rounded-2xl p-5 hover:border-white/10 transition-all">
                <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4">
                    <div class="flex items-start gap-4">
                        <div class="w-11 h-11 bg-gradient-to-br from-primary/30 to-accent/30 rounded-full flex items-center justify-center font-bold text-base shrink-0">
                            <?= strtoupper(substr($s['other_name'], 0, 1)) ?>
                        </div>
                        <div>
                            <div class="flex items-center gap-2 flex-wrap">
                                <p class="font-medium text-white"><?= htmlspecialchars($s['other_name']) ?></p>
                                <?php if (!empty($s['specialization'])): ?>
                                <span class="text-xs text-white/35">(<?= htmlspecialchars($s['specialization']) ?>)</span>
                                <?php endif; ?>
                                <span class="text-xs px-2 py-0.5 rounded-full border <?= $statusColors[$s['status']] ?> capitalize"><?= $s['status'] ?></span>
                                <?php if ($hasSummary): ?>
                                <span class="text-xs px-2 py-0.5 rounded-full bg-violet-500/10 border border-violet-500/20 text-violet-400">AI Summary Ready</span>
                                <?php endif; ?>
                            </div>
                            <p class="text-sm text-white/45 mt-1">
                                <?= date('M j, Y', strtotime($s['appointment_date'])) ?> at <?= date('g:i A', strtotime($s['appointment_time'])) ?>
                            </p>
                            <?php if ($s['chief_complaint']): ?>
                            <p class="text-xs text-white/35 mt-1 max-w-sm truncate">Complaint: <?= htmlspecialchars($s['chief_complaint']) ?></p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="flex items-center gap-2 shrink-0">
                        <?php if ($hasSummary): ?>
                        <button onclick="viewSummary(<?= htmlspecialchars(json_encode($summaryData)) ?>)"
                                class="text-xs text-violet-400 bg-violet-500/10 hover:bg-violet-500/20 border border-violet-500/20 px-3 py-1.5 rounded-lg transition-all">
                            View Summary
                        </button>
                        <?php endif; ?>

                        <?php if ($s['status'] === 'confirmed'): ?>
                            <?php if ($userRole === 'doctor'): ?>
                            <a href="consultation_room.php?appointment_id=<?= $s['appointment_id'] ?>"
                               class="text-xs text-white bg-primary hover:bg-primary-dark px-4 py-1.5 rounded-lg transition-all font-medium flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.069A1 1 0 0121 8.87v6.26a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                <?= $hasStarted ? 'Resume Session' : 'Start Session' ?>
                            </a>
                            <?php else: ?>
                            <?php if ($hasStarted && !$isEnded): ?>
                            <a href="consultation_room.php?appointment_id=<?= $s['appointment_id'] ?>"
                               class="text-xs text-white bg-emerald-500 hover:bg-emerald-600 px-4 py-1.5 rounded-lg transition-all font-medium flex items-center gap-1.5">
                                <span class="w-2 h-2 bg-white rounded-full animate-pulse"></span>
                                Join Session
                            </a>
                            <?php else: ?>
                            <span class="text-xs text-white/30 bg-white/5 px-4 py-1.5 rounded-lg">Waiting for doctor</span>
                            <?php endif; ?>
                            <?php endif; ?>
                        <?php elseif ($s['status'] === 'completed'): ?>
                            <a href="consultation_room.php?appointment_id=<?= $s['appointment_id'] ?>"
                               class="text-xs text-white/60 bg-white/5 hover:bg-white/10 border border-white/10 px-3 py-1.5 rounded-lg transition-all">
                                View Notes Record
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if ($hasSummary && $summaryData): ?>
                <div class="mt-4 pt-4 border-t border-white/5 grid sm:grid-cols-2 gap-3">
                    <?php if (!empty($summaryData['diagnosis'])): ?>
                    <div class="bg-white/[0.03] rounded-xl p-3">
                        <p class="text-xs text-white/40 mb-1">Diagnosis</p>
                        <p class="text-sm text-white"><?= htmlspecialchars($summaryData['diagnosis']) ?></p>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($summaryData['follow_up_date'])): ?>
                    <div class="bg-white/[0.03] rounded-xl p-3">
                        <p class="text-xs text-white/40 mb-1">Follow-up Date</p>
                        <p class="text-sm text-white"><?= date('M j, Y', strtotime($summaryData['follow_up_date'])) ?></p>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>

        <?php if ($totalPages > 1): ?>
        <div class="flex items-center justify-between mt-4 px-2">
            <p class="text-xs text-white/40">Showing <?= $offset+1 ?>–<?= min($offset+$perPage,$total) ?> of <?= $total ?></p>
            <div class="flex gap-1">
                <?php for ($i=1;$i<=$totalPages;$i++): ?>
                <a href="?page=<?=$i?>&status=<?=$filterStatus?>&search=<?=urlencode($search)?>"
                   class="w-8 h-8 flex items-center justify-center rounded-lg text-sm transition-all <?= $i===$page?'bg-primary text-white':'text-white/40 hover:bg-white/5' ?>">
                    <?=$i?>
                </a>
                <?php endfor; ?>
            </div>
        </div>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</main>

<!-- Summary Modal -->
<div id="summaryModal" class="fixed inset-0 z-50 hidden items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" onclick="closeSummaryModal()"></div>
    <div class="relative bg-dark-card border border-white/10 rounded-2xl shadow-2xl w-full max-w-lg max-h-[85vh] overflow-y-auto">
        <div class="flex items-center justify-between p-6 border-b border-white/5 sticky top-0 bg-dark-card">
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 bg-violet-500/10 border border-violet-500/20 rounded-lg flex items-center justify-center">
                    <svg class="w-4 h-4 text-violet-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                    </svg>
                </div>
                <h3 class="font-semibold text-white">AI Clinical Summary</h3>
            </div>
            <button onclick="closeSummaryModal()" class="w-8 h-8 bg-white/5 hover:bg-white/10 rounded-lg flex items-center justify-center text-white/60 hover:text-white transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div class="p-6 space-y-4" id="summaryContent"></div>
    </div>
</div>

<?php include 'includes/sidebar_scripts.php'; ?>

<script>
function viewSummary(data) {
    const fields = [
        ['Chief Complaint', data.chief_complaint],
        ['Findings',        data.findings],
        ['Diagnosis',       data.diagnosis],
        ['Treatment Plan',  data.plan],
        ['Follow-up Date',  data.follow_up_date],
    ];

    let html = fields.filter(([,v])=>v).map(([label, value]) => `
        <div class="bg-white/[0.03] rounded-xl p-4">
            <p class="text-xs text-white/40 mb-1.5">${label}</p>
            <p class="text-sm text-white leading-relaxed">${value}</p>
        </div>
    `).join('');

    if (data.action_items && data.action_items.length) {
        html += `<div class="bg-white/[0.03] rounded-xl p-4">
            <p class="text-xs text-white/40 mb-2">Action Items</p>
            <ul class="space-y-1.5">
                ${data.action_items.map(a => `<li class="flex items-start gap-2 text-sm text-white"><span class="text-primary mt-0.5">•</span>${a}</li>`).join('')}
            </ul>
        </div>`;
    }

    document.getElementById('summaryContent').innerHTML = html;
    const m = document.getElementById('summaryModal');
    m.classList.remove('hidden');
    m.classList.add('flex');
}

function closeSummaryModal() {
    const m = document.getElementById('summaryModal');
    m.classList.add('hidden');
    m.classList.remove('flex');
}
</script>
</body>
</html>