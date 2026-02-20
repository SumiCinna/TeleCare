<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: auth/login.php');
    exit;
}

require_once 'config/database.php';
require_once 'config/app_config.php';

$userId    = $_SESSION['user_id'];
$userName  = $_SESSION['user_name'];
$userRole  = $_SESSION['user_role'];
$activePage = 'appointments';

$statusColors = [
    'pending'   => 'bg-amber-500/10 text-amber-400 border-amber-500/20',
    'confirmed' => 'bg-blue-500/10 text-blue-400 border-blue-500/20',
    'completed' => 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20',
    'cancelled' => 'bg-red-500/10 text-red-400 border-red-500/20',
];

$filterStatus = $_GET['status'] ?? 'all';
$search       = trim($_GET['search'] ?? '');
$page         = max(1, intval($_GET['page'] ?? 1));
$perPage      = 10;
$offset       = ($page - 1) * $perPage;

if ($userRole === 'patient') {
    $where  = "WHERE a.patient_id = ?";
    $params = [$userId];
    if ($filterStatus !== 'all') { $where .= " AND a.status = ?"; $params[] = $filterStatus; }
    if ($search) { $where .= " AND (CONCAT(u.first_name,' ',u.last_name) LIKE ? OR dp.specialization LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; }

    $countQ = $pdo->prepare("SELECT COUNT(*) FROM appointments a JOIN users u ON a.doctor_id = u.id LEFT JOIN doctor_profiles dp ON u.id = dp.user_id $where");
    $countQ->execute($params);
    $total = $countQ->fetchColumn();

    $stmt = $pdo->prepare("SELECT a.*, CONCAT(u.first_name,' ',u.last_name) AS other_name, dp.specialization, dp.consultation_fee FROM appointments a JOIN users u ON a.doctor_id = u.id LEFT JOIN doctor_profiles dp ON u.id = dp.user_id $where ORDER BY a.appointment_date DESC, a.appointment_time DESC LIMIT $perPage OFFSET $offset");
    $stmt->execute($params);
    $appointments = $stmt->fetchAll();

} else {
    $where  = "WHERE a.doctor_id = ?";
    $params = [$userId];
    if ($filterStatus !== 'all') { $where .= " AND a.status = ?"; $params[] = $filterStatus; }
    if ($search) { $where .= " AND CONCAT(u.first_name,' ',u.last_name) LIKE ?"; $params[] = "%$search%"; }

    $countQ = $pdo->prepare("SELECT COUNT(*) FROM appointments a JOIN users u ON a.patient_id = u.id $where");
    $countQ->execute($params);
    $total = $countQ->fetchColumn();

    $stmt = $pdo->prepare("SELECT a.*, CONCAT(u.first_name,' ',u.last_name) AS other_name FROM appointments a JOIN users u ON a.patient_id = u.id $where ORDER BY a.appointment_date DESC, a.appointment_time DESC LIMIT $perPage OFFSET $offset");
    $stmt->execute($params);
    $appointments = $stmt->fetchAll();
}

$totalPages = ceil($total / $perPage);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointments – TeleCare AI</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="icon" type="image/jpeg" href="images/logo.jpg">
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
    <script src="https://cdn.jsdelivr.net/npm/@emailjs/browser@4/dist/email.min.js"></script>
    <script>
        (function() {
            emailjs.init('<?= defined('EMAILJS_PUBLIC_KEY') ? EMAILJS_PUBLIC_KEY : 'YOUR_EMAILJS_PUBLIC_KEY' ?>');
        })();
    </script>
</head>
<body class="bg-dark text-white font-sans antialiased flex h-screen overflow-hidden">

<?php include 'includes/sidebar.php'; ?>

<main id="mainContent" class="flex-1 min-h-screen overflow-y-auto transition-all duration-300">

    <header class="h-16 flex items-center justify-between px-6 border-b border-white/5 bg-dark/50 sticky top-0 backdrop-blur-md z-10">
        <div class="flex items-center gap-4">
            <button onclick="toggleSidebar()" class="w-9 h-9 bg-white/5 hover:bg-white/10 rounded-xl flex items-center justify-center transition-colors">
                <svg class="w-5 h-5 text-white/60" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>
            <div>
                <h1 class="text-lg font-semibold text-white">Appointments</h1>
                <p class="text-xs text-white/40"><?= date('l, F j, Y') ?></p>
            </div>
        </div>
        <div class="flex items-center gap-3">
            <?php if ($userRole === 'patient'): ?>
            <button onclick="openBookModal()" class="flex items-center gap-2 bg-primary hover:bg-primary-dark text-white text-sm font-medium px-4 py-2 rounded-xl transition-all">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Book Appointment
            </button>
            <?php endif; ?>
            <div class="w-9 h-9 bg-gradient-to-br from-primary to-accent rounded-full flex items-center justify-center text-sm font-bold">
                <?= strtoupper(substr($userName, 0, 1)) ?>
            </div>
        </div>
    </header>

    <div class="p-6">

        <!-- Stats Row -->
        <?php
        if ($userRole === 'patient') {
            $sq = $pdo->prepare("SELECT status, COUNT(*) as cnt FROM appointments WHERE patient_id = ? GROUP BY status");
        } else {
            $sq = $pdo->prepare("SELECT status, COUNT(*) as cnt FROM appointments WHERE doctor_id = ? GROUP BY status");
        }
        $sq->execute([$userId]);
        $statMap = array_column($sq->fetchAll(), 'cnt', 'status');
        $allCount = array_sum($statMap);
        ?>
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <?php
            $quickStats = [
                ['label' => 'Total',     'value' => $allCount,              'color' => 'text-white',        'bg' => 'bg-white/5'],
                ['label' => 'Pending',   'value' => $statMap['pending']   ?? 0, 'color' => 'text-amber-400',   'bg' => 'bg-amber-500/10'],
                ['label' => 'Confirmed', 'value' => $statMap['confirmed'] ?? 0, 'color' => 'text-blue-400',    'bg' => 'bg-blue-500/10'],
                ['label' => 'Completed', 'value' => $statMap['completed'] ?? 0, 'color' => 'text-emerald-400', 'bg' => 'bg-emerald-500/10'],
            ];
            foreach ($quickStats as $qs): ?>
            <div class="<?= $qs['bg'] ?> border border-white/5 rounded-2xl px-5 py-4">
                <div class="text-2xl font-bold <?= $qs['color'] ?> mb-1"><?= $qs['value'] ?></div>
                <div class="text-xs text-white/40"><?= $qs['label'] ?></div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Filters -->
        <div class="bg-dark-card border border-white/5 rounded-2xl mb-4">
            <div class="flex flex-col sm:flex-row items-start sm:items-center gap-3 p-4">
                <form method="GET" class="flex flex-col sm:flex-row gap-3 w-full">
                    <div class="relative flex-1">
                        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-white/30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search <?= $userRole === 'patient' ? 'doctor' : 'patient' ?> name..." class="w-full bg-white/5 border border-white/10 text-white placeholder-white/25 rounded-xl pl-9 pr-4 py-2.5 text-sm focus:outline-none focus:border-primary transition-all">
                    </div>
                    <select name="status" onchange="this.form.submit()" class="bg-white/5 border border-white/10 text-white rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-primary transition-all appearance-none pr-8">
                        <option value="all"      class="bg-slate-800" <?= $filterStatus === 'all'       ? 'selected' : '' ?>>All Status</option>
                        <option value="pending"  class="bg-slate-800" <?= $filterStatus === 'pending'   ? 'selected' : '' ?>>Pending</option>
                        <option value="confirmed"class="bg-slate-800" <?= $filterStatus === 'confirmed' ? 'selected' : '' ?>>Confirmed</option>
                        <option value="completed"class="bg-slate-800" <?= $filterStatus === 'completed' ? 'selected' : '' ?>>Completed</option>
                        <option value="cancelled"class="bg-slate-800" <?= $filterStatus === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                    </select>
                    <button type="submit" class="bg-primary hover:bg-primary-dark text-white text-sm font-medium px-5 py-2.5 rounded-xl transition-all">Search</button>
                    <?php if ($search || $filterStatus !== 'all'): ?>
                    <a href="appointments.php" class="bg-white/5 hover:bg-white/10 text-white/60 text-sm px-4 py-2.5 rounded-xl transition-all text-center">Clear</a>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <!-- Table -->
        <div class="bg-dark-card border border-white/5 rounded-2xl overflow-hidden">
            <?php if (empty($appointments)): ?>
            <div class="flex flex-col items-center justify-center py-20 px-6 text-center">
                <div class="w-16 h-16 bg-white/5 rounded-2xl flex items-center justify-center mb-4">
                    <svg class="w-8 h-8 text-white/20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                </div>
                <p class="text-white/50 font-medium mb-1">No appointments found</p>
                <p class="text-white/30 text-sm">
                    <?= ($search || $filterStatus !== 'all') ? 'Try adjusting your filters.' : ($userRole === 'patient' ? 'Book your first appointment to get started.' : 'No appointments have been scheduled yet.') ?>
                </p>
                <?php if ($userRole === 'patient' && !$search && $filterStatus === 'all'): ?>
                <button onclick="openBookModal()" class="mt-4 bg-primary hover:bg-primary-dark text-white text-sm font-medium px-5 py-2.5 rounded-xl transition-all">
                    Book Appointment
                </button>
                <?php endif; ?>
            </div>
            <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-white/5">
                            <th class="text-left text-xs font-medium text-white/40 uppercase tracking-wide px-6 py-4"><?= $userRole === 'patient' ? 'Doctor' : 'Patient' ?></th>
                            <th class="text-left text-xs font-medium text-white/40 uppercase tracking-wide px-6 py-4">Date & Time</th>
                            <?php if ($userRole === 'patient'): ?>
                            <th class="text-left text-xs font-medium text-white/40 uppercase tracking-wide px-6 py-4">Specialization</th>
                            <?php endif; ?>
                            <th class="text-left text-xs font-medium text-white/40 uppercase tracking-wide px-6 py-4">Complaint</th>
                            <th class="text-left text-xs font-medium text-white/40 uppercase tracking-wide px-6 py-4">Status</th>
                            <th class="text-right text-xs font-medium text-white/40 uppercase tracking-wide px-6 py-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/[0.04]">
                        <?php foreach ($appointments as $appt): ?>
                        <tr class="hover:bg-white/[0.02] transition-colors group">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 bg-gradient-to-br from-primary/30 to-accent/30 rounded-full flex items-center justify-center text-sm font-bold shrink-0">
                                        <?= strtoupper(substr($appt['other_name'], 0, 1)) ?>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-white"><?= htmlspecialchars($appt['other_name']) ?></p>
                                        <?php if ($userRole === 'patient' && !empty($appt['consultation_fee'])): ?>
                                        <p class="text-xs text-white/35">₱<?= number_format($appt['consultation_fee'], 2) ?></p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <p class="text-sm text-white"><?= date('M j, Y', strtotime($appt['appointment_date'])) ?></p>
                                <p class="text-xs text-white/40"><?= date('g:i A', strtotime($appt['appointment_time'])) ?></p>
                            </td>
                            <?php if ($userRole === 'patient'): ?>
                            <td class="px-6 py-4">
                                <p class="text-sm text-white/60"><?= htmlspecialchars($appt['specialization'] ?? '—') ?></p>
                            </td>
                            <?php endif; ?>
                            <td class="px-6 py-4 max-w-xs">
                                <p class="text-sm text-white/60 truncate"><?= htmlspecialchars($appt['chief_complaint'] ?? '—') ?></p>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-xs px-2.5 py-1 rounded-full border <?= $statusColors[$appt['status']] ?> capitalize font-medium">
                                    <?= $appt['status'] ?>
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center justify-end gap-2">
                                    <button onclick="viewDetails(<?= htmlspecialchars(json_encode($appt)) ?>)" class="text-xs text-white/40 hover:text-white bg-white/5 hover:bg-white/10 px-3 py-1.5 rounded-lg transition-all">
                                        View
                                    </button>
                                    <?php if ($userRole === 'doctor'): ?>
                                        <?php if ($appt['status'] === 'pending'): ?>
                                        <button onclick="updateStatus(<?= $appt['id'] ?>, 'confirm')" class="text-xs text-blue-400 hover:text-white bg-blue-500/10 hover:bg-blue-500/20 border border-blue-500/20 px-3 py-1.5 rounded-lg transition-all">
                                            Confirm
                                        </button>
                                        <?php elseif ($appt['status'] === 'confirmed'): ?>
                                        <button onclick="updateStatus(<?= $appt['id'] ?>, 'complete')" class="text-xs text-emerald-400 hover:text-white bg-emerald-500/10 hover:bg-emerald-500/20 border border-emerald-500/20 px-3 py-1.5 rounded-lg transition-all">
                                            Complete
                                        </button>
                                        <?php endif; ?>
                                        <?php if (in_array($appt['status'], ['pending', 'confirmed'])): ?>
                                        <button onclick="cancelAppointment(<?= $appt['id'] ?>)" class="text-xs text-red-400 hover:text-white bg-red-500/10 hover:bg-red-500/20 border border-red-500/20 px-3 py-1.5 rounded-lg transition-all">
                                            Cancel
                                        </button>
                                        <?php endif; ?>
                                    <?php elseif ($userRole === 'patient' && $appt['status'] === 'pending'): ?>
                                    <button onclick="cancelAppointment(<?= $appt['id'] ?>)" class="text-xs text-red-400 hover:text-white bg-red-500/10 hover:bg-red-500/20 border border-red-500/20 px-3 py-1.5 rounded-lg transition-all">
                                        Cancel
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($totalPages > 1): ?>
            <div class="flex items-center justify-between px-6 py-4 border-t border-white/5">
                <p class="text-xs text-white/40">Showing <?= $offset + 1 ?>–<?= min($offset + $perPage, $total) ?> of <?= $total ?> appointments</p>
                <div class="flex gap-1">
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <a href="?page=<?= $i ?>&status=<?= $filterStatus ?>&search=<?= urlencode($search) ?>"
                       class="w-8 h-8 flex items-center justify-center rounded-lg text-sm transition-all <?= $i === $page ? 'bg-primary text-white' : 'text-white/40 hover:bg-white/5 hover:text-white' ?>">
                        <?= $i ?>
                    </a>
                    <?php endfor; ?>
                </div>
            </div>
            <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</main>

<!-- View Details Modal -->
<div id="detailsModal" class="fixed inset-0 z-50 hidden items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" onclick="closeDetailsModal()"></div>
    <div class="relative bg-dark-card border border-white/10 rounded-2xl shadow-2xl w-full max-w-md">
        <div class="flex items-center justify-between p-6 border-b border-white/5">
            <h3 class="font-semibold text-white">Appointment Details</h3>
            <button onclick="closeDetailsModal()" class="w-8 h-8 bg-white/5 hover:bg-white/10 rounded-lg flex items-center justify-center transition-colors text-white/60 hover:text-white">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        <div class="p-6 space-y-4" id="detailsContent"></div>
    </div>
</div>

<?php if ($userRole === 'patient'): ?>
<!-- Book Appointment Modal -->
<div id="bookModal" class="fixed inset-0 z-50 hidden items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" onclick="closeBookModal()"></div>
    <div class="relative bg-dark-card border border-white/10 rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between p-6 border-b border-white/5 sticky top-0 bg-dark-card z-10">
            <div>
                <h3 class="font-semibold text-white">Book Appointment</h3>
                <p class="text-xs text-white/40 mt-0.5">Fill in the details to schedule a consultation</p>
            </div>
            <button onclick="closeBookModal()" class="w-8 h-8 bg-white/5 hover:bg-white/10 rounded-lg flex items-center justify-center transition-colors text-white/60 hover:text-white">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <div id="bookAlert" class="hidden mx-6 mt-4 px-4 py-3 rounded-xl text-sm"></div>

        <div class="p-6 space-y-5">
            <!-- Step 1: Doctor -->
            <div>
                <label class="block text-sm font-medium text-white/70 mb-2">Select Doctor</label>
                <div id="doctorList" class="space-y-2 max-h-48 overflow-y-auto pr-1">
                    <div class="text-sm text-white/30 py-4 text-center">Loading doctors...</div>
                </div>
                <input type="hidden" id="selectedDoctorId">
            </div>

            <!-- Step 2: Date -->
            <div>
                <label class="block text-sm font-medium text-white/70 mb-2" for="apptDate">Preferred Date</label>
                <input type="date" id="apptDate" min="<?= date('Y-m-d') ?>"
                       class="w-full bg-white/5 border border-white/10 text-white rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-primary transition-all"
                       onchange="loadSlots()">
            </div>

            <!-- Step 3: Time Slots -->
            <div id="slotsSection" class="hidden">
                <label class="block text-sm font-medium text-white/70 mb-2">Available Time Slots</label>
                <div id="slotsList" class="grid grid-cols-3 gap-2"></div>
                <input type="hidden" id="selectedTime">
            </div>

            <!-- Step 4: Complaint -->
            <div>
                <label class="block text-sm font-medium text-white/70 mb-2" for="complaint">Chief Complaint <span class="text-white/30">(optional)</span></label>
                <textarea id="complaint" rows="3" placeholder="Describe your symptoms or reason for visit..."
                          class="w-full bg-white/5 border border-white/10 text-white placeholder-white/25 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-primary transition-all resize-none"></textarea>
            </div>

            <button onclick="submitBooking()" class="w-full bg-primary hover:bg-primary-dark text-white font-semibold py-3 rounded-xl transition-all text-sm">
                Confirm Booking
            </button>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Cancel Confirm Modal -->
<div id="cancelModal" class="fixed inset-0 z-50 hidden items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" onclick="closeCancelModal()"></div>
    <div class="relative bg-dark-card border border-white/10 rounded-2xl shadow-2xl w-full max-w-sm p-6">
        <div class="flex items-center justify-center w-12 h-12 bg-red-500/10 rounded-2xl mx-auto mb-4">
            <svg class="w-6 h-6 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
            </svg>
        </div>
        <h3 class="text-lg font-semibold text-white text-center mb-1">Cancel Appointment?</h3>
        <p class="text-sm text-white/45 text-center mb-6">This action cannot be undone. The appointment will be marked as cancelled.</p>
        <div class="flex gap-3">
            <button onclick="closeCancelModal()" class="flex-1 bg-white/5 hover:bg-white/10 border border-white/10 text-white font-medium py-2.5 rounded-xl text-sm transition-all">Keep It</button>
            <button onclick="confirmCancel()" class="flex-1 bg-red-500 hover:bg-red-600 text-white font-medium py-2.5 rounded-xl text-sm transition-all">Yes, Cancel</button>
        </div>
    </div>
</div>

<?php include 'includes/sidebar_scripts.php'; ?>

<script>
const STATUS_COLORS = {
    pending:   'bg-amber-500/10 text-amber-400 border border-amber-500/20',
    confirmed: 'bg-blue-500/10 text-blue-400 border border-blue-500/20',
    completed: 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20',
    cancelled: 'bg-red-500/10 text-red-400 border border-red-500/20',
};

let cancelTargetId = null;

// ─── Details Modal ─────────────────────────────────────────────────────────
function viewDetails(appt) {
    const role  = '<?= $userRole ?>';
    const label = role === 'patient' ? 'Doctor' : 'Patient';
    const statusBadge = `<span class="text-xs px-2.5 py-1 rounded-full ${STATUS_COLORS[appt.status]} capitalize font-medium">${appt.status}</span>`;

    document.getElementById('detailsContent').innerHTML = `
        <div class="flex items-center gap-3 pb-4 border-b border-white/5">
            <div class="w-12 h-12 bg-gradient-to-br from-primary/30 to-accent/30 rounded-full flex items-center justify-center text-lg font-bold">
                ${appt.other_name.charAt(0).toUpperCase()}
            </div>
            <div>
                <p class="font-medium text-white">${appt.other_name}</p>
                ${appt.specialization ? `<p class="text-xs text-white/40">${appt.specialization}</p>` : ''}
            </div>
        </div>
        <div class="grid grid-cols-2 gap-4">
            <div><p class="text-xs text-white/40 mb-1">Date</p><p class="text-sm text-white font-medium">${formatDate(appt.appointment_date)}</p></div>
            <div><p class="text-xs text-white/40 mb-1">Time</p><p class="text-sm text-white font-medium">${formatTime(appt.appointment_time)}</p></div>
            <div><p class="text-xs text-white/40 mb-1">Status</p>${statusBadge}</div>
            <div><p class="text-xs text-white/40 mb-1">Payment</p><span class="text-xs px-2.5 py-1 rounded-full bg-white/5 text-white/60 capitalize font-medium">${appt.payment_status}</span></div>
        </div>
        ${appt.chief_complaint ? `<div><p class="text-xs text-white/40 mb-1">Chief Complaint</p><p class="text-sm text-white/80 leading-relaxed">${appt.chief_complaint}</p></div>` : ''}
        ${appt.consultation_fee ? `<div><p class="text-xs text-white/40 mb-1">Consultation Fee</p><p class="text-sm text-white font-medium">₱${parseFloat(appt.consultation_fee).toFixed(2)}</p></div>` : ''}
        <div><p class="text-xs text-white/40 mb-1">Booked On</p><p class="text-sm text-white/60">${formatDate(appt.created_at)}</p></div>
    `;
    openModal('detailsModal');
}
function closeDetailsModal() { closeModal('detailsModal'); }

// ─── Book Modal ─────────────────────────────────────────────────────────────
<?php if ($userRole === 'patient'): ?>
let selectedDoctorId = null;
let selectedTime     = null;

async function openBookModal() {
    openModal('bookModal');
    selectedDoctorId = null;
    selectedTime     = null;
    document.getElementById('apptDate').value    = '';
    document.getElementById('complaint').value   = '';
    document.getElementById('slotsSection').classList.add('hidden');
    hideBookAlert();
    await loadDoctors();
}

function closeBookModal() { closeModal('bookModal'); }

async function loadDoctors() {
    const res  = await fetch('ajax/appointments_handler.php?action=get_doctors');
    const data = await res.json();
    const list = document.getElementById('doctorList');

    if (!data.success || !data.doctors.length) {
        list.innerHTML = '<p class="text-sm text-white/30 py-4 text-center">No doctors available</p>';
        return;
    }

    list.innerHTML = data.doctors.map(d => `
        <div class="doctor-card flex items-center gap-3 p-3 rounded-xl border border-white/5 hover:border-primary/40 hover:bg-primary/5 cursor-pointer transition-all"
             data-id="${d.id}" onclick="selectDoctor(this, ${d.id})">
            <div class="w-9 h-9 bg-gradient-to-br from-primary/30 to-accent/30 rounded-full flex items-center justify-center font-bold text-sm shrink-0">
                ${d.name.charAt(0)}
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-white truncate">${d.name}</p>
                <p class="text-xs text-white/40">${d.specialization || 'General'} ${d.consultation_fee ? '· ₱' + parseFloat(d.consultation_fee).toFixed(2) : ''}</p>
            </div>
            <div class="w-4 h-4 rounded-full border-2 border-white/20 selected-dot shrink-0"></div>
        </div>
    `).join('');
}

function selectDoctor(el, id) {
    document.querySelectorAll('.doctor-card').forEach(c => {
        c.classList.remove('border-primary/40', 'bg-primary/5');
        c.querySelector('.selected-dot').classList.remove('bg-primary', 'border-primary');
    });
    el.classList.add('border-primary/40', 'bg-primary/5');
    el.querySelector('.selected-dot').classList.add('bg-primary', 'border-primary');
    selectedDoctorId = id;
    loadSlots();
}

async function loadSlots() {
    if (!selectedDoctorId || !document.getElementById('apptDate').value) return;
    const date = document.getElementById('apptDate').value;
    const section = document.getElementById('slotsSection');
    const list    = document.getElementById('slotsList');
    selectedTime  = null;

    list.innerHTML = '<div class="col-span-3 text-sm text-white/30 py-2 text-center">Loading slots...</div>';
    section.classList.remove('hidden');

    const res  = await fetch(`ajax/appointments_handler.php?action=get_slots&doctor_id=${selectedDoctorId}&date=${date}`);
    const data = await res.json();

    if (!data.success) {
        list.innerHTML = `<div class="col-span-3 text-sm text-red-400 py-2 text-center">${data.message}</div>`;
        return;
    }

    const available = data.slots.filter(s => s.available);
    if (!available.length) {
        list.innerHTML = '<div class="col-span-3 text-sm text-white/30 py-2 text-center">No slots available for this date</div>';
        return;
    }

    list.innerHTML = data.slots.map(s => s.available ? `
        <button type="button" class="slot-btn py-2 px-3 rounded-lg border text-xs font-medium transition-all border-white/10 text-white/60 hover:border-primary hover:text-primary hover:bg-primary/5"
                data-time="${s.time}" onclick="selectSlot(this, '${s.time}')">${s.label}</button>
    ` : `
        <div class="py-2 px-3 rounded-lg border border-white/5 text-xs text-white/20 text-center line-through">${s.label}</div>
    `).join('');
}

function selectSlot(el, time) {
    document.querySelectorAll('.slot-btn').forEach(b => {
        b.classList.remove('bg-primary', 'text-white', 'border-primary');
        b.classList.add('border-white/10', 'text-white/60');
    });
    el.classList.add('bg-primary', 'text-white', 'border-primary');
    el.classList.remove('border-white/10', 'text-white/60');
    selectedTime = time;
}

async function submitBooking() {
    hideBookAlert();
    if (!selectedDoctorId) { showBookAlert('Please select a doctor.', 'error'); return; }
    if (!document.getElementById('apptDate').value) { showBookAlert('Please select a date.', 'error'); return; }
    if (!selectedTime) { showBookAlert('Please select a time slot.', 'error'); return; }

    const body = new FormData();
    body.append('action', 'book');
    body.append('doctor_id', selectedDoctorId);
    body.append('date', document.getElementById('apptDate').value);
    body.append('time', selectedTime);
    body.append('complaint', document.getElementById('complaint').value);

    const res  = await fetch('ajax/appointments_handler.php', { method: 'POST', body });
    const data = await res.json();

    if (data.success) {
        // Send EmailJS confirmation
        await sendBookingEmail(
            selectedDoctorId,
            document.getElementById('apptDate').value,
            selectedTime,
            document.getElementById('complaint').value
        );
        showBookAlert(data.message, 'success');
        setTimeout(() => { closeBookModal(); location.reload(); }, 1500);
    } else {
        showBookAlert(data.message, 'error');
    }
}

function showBookAlert(msg, type) {
    const el = document.getElementById('bookAlert');
    el.className = `mx-6 mt-4 px-4 py-3 rounded-xl text-sm flex items-center gap-2 ${type === 'success' ? 'bg-emerald-500/10 border border-emerald-500/20 text-emerald-400' : 'bg-red-500/10 border border-red-500/20 text-red-400'}`;
    el.textContent = msg;
    el.classList.remove('hidden');
}
function hideBookAlert() {
    document.getElementById('bookAlert').classList.add('hidden');
}

async function sendBookingEmail(doctorId, date, time, complaint) {
    const EMAILJS_SERVICE  = '<?= EMAILJS_SERVICE_ID ?>';
    const EMAILJS_TEMPLATE = '<?= EMAILJS_BOOKING_TEMPLATE ?>';

    if (EMAILJS_SERVICE === 'YOUR_EMAILJS_SERVICE_ID') return; // skip if not configured

    try {
        const doctorCard = document.querySelector(`.doctor-card[data-id="${doctorId}"]`);
        const doctorName = doctorCard?.querySelector('p')?.textContent ?? 'Your Doctor';
        const d = new Date(date + 'T' + time);
        const formattedDate = d.toLocaleDateString('en-US', { weekday:'long', year:'numeric', month:'long', day:'numeric' });
        const formattedTime = d.toLocaleTimeString('en-US', { hour:'numeric', minute:'2-digit' });

        await emailjs.send(EMAILJS_SERVICE, EMAILJS_TEMPLATE, {
            to_name:     '<?= htmlspecialchars($userName ?? '', ENT_QUOTES) ?>',
            to_email:    '<?= htmlspecialchars($_SESSION['user_email'] ?? '', ENT_QUOTES) ?>',
            doctor_name: doctorName,
            appt_date:   formattedDate,
            appt_time:   formattedTime,
            complaint:   complaint || 'Not specified',
            app_name:    'TeleCare AI',
        });
    } catch (e) {
        console.warn('EmailJS error:', e);
    }
}
<?php endif; ?>

// ─── Cancel Modal ───────────────────────────────────────────────────────────
function cancelAppointment(id) {
    cancelTargetId = id;
    openModal('cancelModal');
}
function closeCancelModal() { closeModal('cancelModal'); cancelTargetId = null; }

async function confirmCancel() {
    if (!cancelTargetId) return;
    const body = new FormData();
    body.append('action', 'cancel');
    body.append('appointment_id', cancelTargetId);
    const res  = await fetch('ajax/appointments_handler.php', { method: 'POST', body });
    const data = await res.json();
    closeCancelModal();
    if (data.success) { location.reload(); } else { alert(data.message); }
}

// ─── Status Update (Doctor) ─────────────────────────────────────────────────
async function updateStatus(id, action) {
    const labels = { confirm: 'Confirm', complete: 'Mark as Complete' };
    if (!confirm(`${labels[action]} this appointment?`)) return;
    const body = new FormData();
    body.append('action', action);
    body.append('appointment_id', id);
    const res  = await fetch('ajax/appointments_handler.php', { method: 'POST', body });
    const data = await res.json();
    if (data.success) { location.reload(); } else { alert(data.message); }
}

// ─── Helpers ─────────────────────────────────────────────────────────────────
function openModal(id)  { const m = document.getElementById(id); m.classList.remove('hidden'); m.classList.add('flex'); }
function closeModal(id) { const m = document.getElementById(id); m.classList.add('hidden'); m.classList.remove('flex'); }

function formatDate(str) {
    if (!str) return '—';
    const d = new Date(str.replace(' ', 'T'));
    return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
}
function formatTime(str) {
    if (!str) return '—';
    const [h, m] = str.split(':');
    const d = new Date(); d.setHours(h, m);
    return d.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' });
}
</script>

</body>
</html>