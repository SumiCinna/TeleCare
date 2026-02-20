<?php
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: auth/login.php'); exit; }

require_once 'config/database.php';
require_once 'config/app_config.php';

$userId   = $_SESSION['user_id'];
$userName = $_SESSION['user_name'];
$userRole = $_SESSION['user_role'];
$apptId   = intval($_GET['appointment_id'] ?? 0);

if (!$apptId) { header('Location: consultations.php'); exit; }

$stmt = $pdo->prepare("SELECT a.*, 
    a.patient_id, a.doctor_id,
    CONCAT(p.first_name,' ',p.last_name) AS patient_name,
    CONCAT(d.first_name,' ',d.last_name) AS doctor_name,
    p.email AS patient_email, d.email AS doctor_email,
    dp.specialization,
    c.id AS consult_id, c.transcript AS notes, c.ai_summary, c.started_at, c.ended_at
    FROM appointments a
    JOIN users p ON a.patient_id = p.id
    JOIN users d ON a.doctor_id = d.id
    LEFT JOIN doctor_profiles dp ON d.id = dp.user_id
    LEFT JOIN consultations c ON a.id = c.appointment_id
    WHERE a.id = ? AND (a.patient_id = ? OR a.doctor_id = ?)
    AND a.status IN ('confirmed','completed')");
$stmt->execute([$apptId, $userId, $userId]);
$appt = $stmt->fetch();

if (!$appt) { header('Location: consultations.php'); exit; }

$isDoctor  = ($userRole === 'doctor');
$isEnded   = !empty($appt['ended_at']);
$roomName  = 'telecare-' . md5('appt-' . $apptId . '-' . date('Y-m-d', strtotime($appt['appointment_date'])));
$summaryData = $appt['ai_summary'] ? json_decode($appt['ai_summary'], true) : null;

if ($isDoctor && empty($appt['consult_id'])) {
    $pdo->prepare("INSERT INTO consultations (appointment_id, started_at) VALUES (?, NOW())")->execute([$apptId]);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consultation Room – TeleCare AI</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="css/dashboard.css">
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
    <script src="https://cdn.jsdelivr.net/npm/@emailjs/browser@4/dist/email.min.js"></script>
    <script>emailjs.init('<?= EMAILJS_PUBLIC_KEY ?>');</script>
    <style>
        #jitsiContainer { height: calc(100vh - 64px); }
        .tab-btn.active { background: rgba(14,165,233,0.1); color: #0ea5e9; border-color: rgba(14,165,233,0.3); }
        .notes-area { resize: none; min-height: 200px; }
    </style>
</head>
<body class="bg-dark text-white font-sans antialiased overflow-hidden">

<header class="h-16 flex items-center justify-between px-6 border-b border-white/5 bg-dark-sidebar z-20 relative">
    <div class="flex items-center gap-4">
        <a href="consultations.php" class="w-9 h-9 bg-white/5 hover:bg-white/10 rounded-xl flex items-center justify-center transition-colors text-white/60 hover:text-white">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        </a>
        <div class="flex items-center gap-2">
            <div class="w-7 h-7 bg-gradient-to-br from-primary to-accent rounded-lg flex items-center justify-center shrink-0">
                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
            </div>
            <div>
                <p class="text-sm font-semibold text-white leading-tight">
                    Consultation with <?= htmlspecialchars($isDoctor ? $appt['patient_name'] : $appt['doctor_name']) ?>
                </p>
                <p class="text-xs text-white/40 leading-tight">
                    <?= date('M j, Y', strtotime($appt['appointment_date'])) ?> · <?= date('g:i A', strtotime($appt['appointment_time'])) ?>
                    <?php if (!empty($appt['specialization'])): ?> · <?= htmlspecialchars($appt['specialization']) ?><?php endif; ?>
                </p>
            </div>
        </div>
    </div>

    <div class="flex items-center gap-2">
        <?php if (!$isEnded): ?>
        <div class="flex items-center gap-1.5 text-xs text-emerald-400 bg-emerald-500/10 border border-emerald-500/20 px-3 py-1.5 rounded-full">
            <span class="w-1.5 h-1.5 bg-emerald-400 rounded-full animate-pulse"></span>
            Live Session
        </div>
        <?php else: ?>
        <span class="text-xs text-white/40 bg-white/5 px-3 py-1.5 rounded-full">Session Ended</span>
        <?php endif; ?>

        <?php if ($isDoctor && !$isEnded): ?>
        <button id="endSessionBtn" onclick="endSession()" class="flex items-center gap-2 bg-red-500 hover:bg-red-600 text-white text-xs font-medium px-4 py-2 rounded-xl transition-all">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z M9 10a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1v-4z"/></svg>
            End Session
        </button>
        <?php endif; ?>
    </div>
</header>

<div class="flex" style="height:calc(100vh - 64px)">

    <div class="flex-1 bg-black relative" id="jitsiContainer">
        <?php if ($isEnded): ?>
        <div class="absolute inset-0 flex flex-col items-center justify-center text-center p-8">
            <div class="w-16 h-16 bg-white/5 rounded-2xl flex items-center justify-center mb-4">
                <svg class="w-8 h-8 text-white/20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.069A1 1 0 0121 8.87v6.26a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                </svg>
            </div>
            <p class="text-white/40 font-medium">Session has ended</p>
            <p class="text-white/25 text-sm mt-1">Ended <?= $appt['ended_at'] ? date('M j \a\t g:i A', strtotime($appt['ended_at'])) : '' ?></p>
        </div>
        <?php endif; ?>
        <div id="jitsiFrame" class="w-full h-full"></div>
    </div>

    <div class="w-96 shrink-0 bg-dark-card border-l border-white/5 flex flex-col">

        <div class="flex border-b border-white/5 px-4 pt-3 gap-2">
            <button onclick="switchTab('notes')" id="tab-notes" class="tab-btn active flex-1 text-xs font-medium py-2 px-3 rounded-lg border border-transparent transition-all">Notes</button>
            <button onclick="switchTab('summary')" id="tab-summary" class="tab-btn flex-1 text-xs font-medium py-2 px-3 rounded-lg border border-transparent text-white/50 hover:text-white transition-all">AI Summary</button>
            <button onclick="switchTab('info')" id="tab-info" class="tab-btn flex-1 text-xs font-medium py-2 px-3 rounded-lg border border-transparent text-white/50 hover:text-white transition-all">Info</button>
        </div>

        <div id="panel-notes" class="flex-1 flex flex-col overflow-hidden">
            <div class="p-4 flex-1 flex flex-col">
                <?php if ($isDoctor && !$isEnded): ?>
                <p class="text-xs text-white/40 mb-2">Consultation notes (only visible to you)</p>
                <textarea id="notesInput" class="notes-area flex-1 w-full bg-white/5 border border-white/10 text-white placeholder-white/25 rounded-xl p-3 text-sm focus:outline-none focus:border-primary transition-all" placeholder="Type your clinical notes here..."><?= htmlspecialchars($appt['notes'] ?? '') ?></textarea>
                <div class="flex items-center gap-2 mt-3">
                    <button onclick="saveNotes()" class="flex-1 bg-white/5 hover:bg-white/10 border border-white/10 text-white text-xs font-medium py-2.5 rounded-xl transition-all">Save Notes</button>
                    <button onclick="generateSummary()" id="summaryBtn" class="flex-1 bg-violet-500/20 hover:bg-violet-500/30 border border-violet-500/30 text-violet-300 text-xs font-medium py-2.5 rounded-xl transition-all flex items-center justify-center gap-1.5">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
                        AI Summary
                    </button>
                </div>
                <p id="notesFeedback" class="text-xs mt-2 hidden text-center"></p>
                <?php elseif ($isDoctor && $isEnded): ?>
                <p class="text-xs text-white/40 mb-2">Consultation notes</p>
                <div class="flex-1 bg-white/[0.03] rounded-xl p-3 text-sm text-white/70 leading-relaxed overflow-y-auto">
                    <?= nl2br(htmlspecialchars($appt['notes'] ?? 'No notes recorded.')) ?>
                </div>
                <?php else: ?>
                <div class="flex-1 flex flex-col items-center justify-center text-center p-4">
                    <svg class="w-10 h-10 text-white/15 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                    <p class="text-sm text-white/40">Notes are private to the doctor</p>
                    <p class="text-xs text-white/25 mt-1">The AI summary will be shared with you after the session</p>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <div id="panel-summary" class="flex-1 overflow-y-auto hidden">
            <div class="p-4 space-y-3" id="summaryPanel">
                <?php if ($summaryData): ?>
                <?php
                $fields = [
                    ['Chief Complaint', $summaryData['chief_complaint'] ?? null],
                    ['Findings',        $summaryData['findings']        ?? null],
                    ['Diagnosis',       $summaryData['diagnosis']       ?? null],
                    ['Treatment Plan',  $summaryData['plan']            ?? null],
                    ['Follow-up Date',  !empty($summaryData['follow_up_date']) ? date('M j, Y', strtotime($summaryData['follow_up_date'])) : null],
                ];
                foreach ($fields as [$label, $value]):
                    if (!$value) continue;
                ?>
                <div class="bg-white/[0.03] rounded-xl p-3">
                    <p class="text-xs text-white/40 mb-1"><?= $label ?></p>
                    <p class="text-sm text-white leading-relaxed"><?= htmlspecialchars($value) ?></p>
                </div>
                <?php endforeach; ?>
                <?php if (!empty($summaryData['action_items'])): ?>
                <div class="bg-white/[0.03] rounded-xl p-3">
                    <p class="text-xs text-white/40 mb-2">Action Items</p>
                    <ul class="space-y-1.5">
                        <?php foreach ($summaryData['action_items'] as $item): ?>
                        <li class="flex items-start gap-2 text-sm text-white">
                            <svg class="w-3.5 h-3.5 text-primary mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            <?= htmlspecialchars($item) ?>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>
                <div class="bg-violet-500/5 border border-violet-500/15 rounded-xl p-3 flex items-center gap-2">
                    <svg class="w-3.5 h-3.5 text-violet-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
                    <p class="text-xs text-violet-300/70">Generated by TeleCare AI</p>
                </div>
                <?php else: ?>
                <div class="flex flex-col items-center justify-center py-12 text-center px-4">
                    <div class="w-12 h-12 bg-violet-500/10 border border-violet-500/15 rounded-2xl flex items-center justify-center mb-3">
                        <svg class="w-6 h-6 text-violet-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
                    </div>
                    <p class="text-sm text-white/40 font-medium">No summary yet</p>
                    <p class="text-xs text-white/25 mt-1">
                        <?= $isDoctor ? 'Add notes and click "AI Summary" to generate one.' : 'The doctor will generate a summary after the session.' ?>
                    </p>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <div id="panel-info" class="flex-1 overflow-y-auto hidden">
            <div class="p-4 space-y-3">
                <?php
                $infoFields = [
                    ['Patient',         $appt['patient_name']],
                    ['Doctor',          $appt['doctor_name']],
                    ['Specialization',  $appt['specialization'] ?? '—'],
                    ['Date',            date('M j, Y', strtotime($appt['appointment_date']))],
                    ['Time',            date('g:i A', strtotime($appt['appointment_time']))],
                    ['Chief Complaint', $appt['chief_complaint'] ?? '—'],
                    ['Session Started', $appt['started_at'] ? date('M j \a\t g:i A', strtotime($appt['started_at'])) : 'Not yet'],
                    ['Session Ended',   $appt['ended_at']   ? date('M j \a\t g:i A', strtotime($appt['ended_at']))   : '—'],
                ];
                foreach ($infoFields as [$label, $value]): ?>
                <div class="bg-white/[0.03] rounded-xl px-4 py-3 flex justify-between items-start gap-3">
                    <p class="text-xs text-white/40 shrink-0"><?= $label ?></p>
                    <p class="text-sm text-white text-right"><?= htmlspecialchars($value) ?></p>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

    </div>
</div>

<div id="endModal" class="fixed inset-0 z-50 hidden items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" onclick="closeEndModal()"></div>
    <div class="relative bg-dark-card border border-white/10 rounded-2xl shadow-2xl w-full max-w-sm p-6">
        <div class="flex items-center justify-center w-12 h-12 bg-red-500/10 rounded-2xl mx-auto mb-4">
            <svg class="w-6 h-6 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z M9 10a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1v-4z"/></svg>
        </div>
        <h3 class="text-lg font-semibold text-white text-center mb-1">End Session?</h3>
        <p class="text-sm text-white/45 text-center mb-4">This will mark the appointment as completed and close the consultation room.</p>
        <div id="endModalError" class="hidden mb-4 px-4 py-3 rounded-xl text-sm bg-red-500/10 border border-red-500/20 text-red-400 text-center"></div>
        <div class="flex gap-3">
            <button onclick="closeEndModal()" id="cancelEndBtn" class="flex-1 bg-white/5 hover:bg-white/10 border border-white/10 text-white font-medium py-2.5 rounded-xl text-sm transition-all">Cancel</button>
            <button onclick="confirmEndSession()" id="confirmEndBtn" class="flex-1 bg-red-500 hover:bg-red-600 text-white font-medium py-2.5 rounded-xl text-sm transition-all">End Session</button>
        </div>
    </div>
</div>

<script src='https://meet.jit.si/external_api.js'></script>
<script>
const APPT_ID   = <?= $apptId ?>;
const IS_DOCTOR = <?= $isDoctor ? 'true' : 'false' ?>;
const IS_ENDED  = <?= $isEnded ? 'true' : 'false' ?>;
const ROOM_NAME = '<?= $roomName ?>';
const USER_NAME = '<?= htmlspecialchars($userName, ENT_QUOTES) ?>';
const USER_ROLE = '<?= $userRole ?>';

if (!IS_ENDED) {
    if (IS_DOCTOR) sendConfirmationEmail();

    const domain  = 'meet.jit.si';
    const options = {
        roomName: ROOM_NAME,
        parentNode: document.getElementById('jitsiFrame'),
        userInfo: { displayName: USER_NAME + ' (' + USER_ROLE + ')' },
        configOverwrite: {
            startWithAudioMuted: false,
            startWithVideoMuted: false,
            prejoinPageEnabled: false,
            disableDeepLinking: true,
        },
        interfaceConfigOverwrite: {
            TOOLBAR_BUTTONS: ['microphone','camera','closedcaptions','desktop','fullscreen','fodeviceselection','hangup','chat','settings','videoquality','filmstrip'],
            SHOW_JITSI_WATERMARK: false,
            SHOW_WATERMARK_FOR_GUESTS: false,
        },
    };
    new JitsiMeetExternalAPI(domain, options);
}

async function sendConfirmationEmail() {
    const SERVICE  = '<?= EMAILJS_SERVICE_ID ?>';
    const TEMPLATE = '<?= EMAILJS_CONFIRM_TEMPLATE ?>';
    if (SERVICE === 'YOUR_EMAILJS_SERVICE_ID') return;
    try {
        await emailjs.send(SERVICE, TEMPLATE, {
            to_name:     '<?= htmlspecialchars($appt['patient_name'], ENT_QUOTES) ?>',
            to_email:    '<?= htmlspecialchars($appt['patient_email'], ENT_QUOTES) ?>',
            doctor_name: '<?= htmlspecialchars($appt['doctor_name'], ENT_QUOTES) ?>',
            appt_date:   '<?= date('F j, Y', strtotime($appt['appointment_date'])) ?>',
            appt_time:   '<?= date('g:i A', strtotime($appt['appointment_time'])) ?>',
        });
        console.log('Confirmation email sent');
    } catch (e) {
        console.warn('EmailJS error:', e);
    }
}

function switchTab(name) {
    ['notes','summary','info'].forEach(t => {
        document.getElementById('tab-' + t).classList.toggle('active', t === name);
        document.getElementById('tab-' + t).classList.toggle('text-white/50', t !== name);
        document.getElementById('panel-' + t).classList.toggle('hidden', t !== name);
        document.getElementById('panel-' + t).classList.toggle('flex', t === name && t === 'notes');
    });
}

async function saveNotes() {
    const notesEl = document.getElementById('notesInput');
    if (!notesEl) return true;
    const fd = new FormData();
    fd.append('action', 'save_notes');
    fd.append('appointment_id', APPT_ID);
    fd.append('notes', notesEl.value);
    try {
        const res  = await fetch('ajax/consultations_handler.php', { method: 'POST', body: fd });
        const data = await res.json();
        showFeedback(data.success ? 'Notes saved ✓' : data.message, data.success ? 'success' : 'error');
        return data.success;
    } catch (e) {
        console.warn('Save notes error:', e);
        return false;
    }
}

if (IS_DOCTOR && !IS_ENDED) {
    setInterval(saveNotes, 30000);
}

async function generateSummary() {
    const btn = document.getElementById('summaryBtn');
    btn.textContent = 'Generating...';
    btn.disabled = true;
    await saveNotes();
    const fd = new FormData();
    fd.append('action', 'ai_summary');
    fd.append('appointment_id', APPT_ID);
    try {
        const res  = await fetch('ajax/consultations_handler.php', { method: 'POST', body: fd });
        const data = await res.json();
        btn.textContent = 'AI Summary';
        btn.disabled = false;
        if (!data.success) { showFeedback(data.message, 'error'); return; }
        renderSummary(data.summary);
        switchTab('summary');
        if (data.demo) showFeedback('Demo summary (add OpenAI key for real AI)', 'info');
    } catch (e) {
        btn.textContent = 'AI Summary';
        btn.disabled = false;
        showFeedback('Failed to generate summary', 'error');
    }
}

function renderSummary(s) {
    const panel  = document.getElementById('summaryPanel');
    const fields = [
        ['Chief Complaint', s.chief_complaint],
        ['Findings',        s.findings],
        ['Diagnosis',       s.diagnosis],
        ['Treatment Plan',  s.plan],
        ['Follow-up Date',  s.follow_up_date],
    ];
    let html = fields.filter(([,v])=>v).map(([label, value]) => `
        <div class="bg-white/[0.03] rounded-xl p-3">
            <p class="text-xs text-white/40 mb-1">${label}</p>
            <p class="text-sm text-white leading-relaxed">${value}</p>
        </div>`).join('');
    if (s.action_items?.length) {
        html += `<div class="bg-white/[0.03] rounded-xl p-3">
            <p class="text-xs text-white/40 mb-2">Action Items</p>
            <ul class="space-y-1.5">
                ${s.action_items.map(a=>`<li class="flex items-start gap-2 text-sm text-white">
                    <svg class="w-3.5 h-3.5 text-primary mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>${a}</li>`).join('')}
            </ul></div>`;
    }
    html += `<div class="bg-violet-500/5 border border-violet-500/15 rounded-xl p-3 flex items-center gap-2">
        <svg class="w-3.5 h-3.5 text-violet-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
        <p class="text-xs text-violet-300/70">Generated by TeleCare AI</p></div>`;
    panel.innerHTML = html;
}

function endSession()    { openModal('endModal'); }
function closeEndModal() {
    if (document.getElementById('confirmEndBtn').disabled) return;
    closeModal('endModal');
}

async function confirmEndSession() {
    const confirmBtn = document.getElementById('confirmEndBtn');
    const cancelBtn  = document.getElementById('cancelEndBtn');
    const errorEl    = document.getElementById('endModalError');

    if (confirmBtn.disabled) return;

    confirmBtn.textContent = 'Ending...';
    confirmBtn.disabled    = true;
    cancelBtn.disabled     = true;
    errorEl.classList.add('hidden');

    try {
        if (IS_DOCTOR) await saveNotes();

        const fd = new FormData();
        fd.append('action', 'end_session');
        fd.append('appointment_id', APPT_ID);

        const res  = await fetch('ajax/consultations_handler.php', { method: 'POST', body: fd });
        const text = await res.text();

        let data;
        try {
            data = JSON.parse(text);
        } catch (parseErr) {
            console.error('Server response:', text);
            throw new Error('Server returned invalid response. Check consultations_handler.php');
        }

        if (data.success) {
            window.location.href = 'consultations.php';
        } else {
            throw new Error(data.message || 'Failed to end session');
        }

    } catch (e) {
        console.error('End session error:', e);
        errorEl.textContent = e.message || 'Something went wrong. Please try again.';
        errorEl.classList.remove('hidden');
        confirmBtn.textContent = 'End Session';
        confirmBtn.disabled    = false;
        cancelBtn.disabled     = false;
    }
}

function openModal(id)  { const m = document.getElementById(id); m.classList.remove('hidden'); m.classList.add('flex'); }
function closeModal(id) { const m = document.getElementById(id); m.classList.add('hidden');    m.classList.remove('flex'); }

function showFeedback(msg, type) {
    const el = document.getElementById('notesFeedback');
    if (!el) return;
    const colors = { success: 'text-emerald-400', error: 'text-red-400', info: 'text-amber-400' };
    el.className = `text-xs mt-2 text-center ${colors[type] || 'text-white/40'}`;
    el.textContent = msg;
    el.classList.remove('hidden');
    setTimeout(() => el.classList.add('hidden'), 3000);
}

document.addEventListener('keydown', e => { if (e.key === 'Escape' && !document.getElementById('confirmEndBtn').disabled) closeEndModal(); });
</script>
</body>
</html>