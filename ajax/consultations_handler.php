<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once '../config/database.php';
require_once '../config/app_config.php';

$userId   = $_SESSION['user_id'];
$userRole = $_SESSION['user_role'];
$action   = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {

    case 'start':
        $apptId = intval($_POST['appointment_id'] ?? 0);

        $stmt = $pdo->prepare("SELECT a.*, u.email AS patient_email,
            CONCAT(p.first_name,' ',p.last_name) AS patient_name,
            CONCAT(d.first_name,' ',d.last_name) AS doctor_name
            FROM appointments a
            JOIN users p ON a.patient_id = p.id
            JOIN users d ON a.doctor_id = d.id
            WHERE a.id = ? AND a.status = 'confirmed'
            AND (a.patient_id = ? OR a.doctor_id = ?)");
        $stmt->execute([$apptId, $userId, $userId]);
        $appt = $stmt->fetch();

        if (!$appt) {
            echo json_encode(['success' => false, 'message' => 'Appointment not found or not confirmed']);
            break;
        }

        $check = $pdo->prepare("SELECT id FROM consultations WHERE appointment_id = ?");
        $check->execute([$apptId]);
        if (!$check->fetch()) {
            $pdo->prepare("INSERT INTO consultations (appointment_id, started_at) VALUES (?, NOW())")
                ->execute([$apptId]);
        }

        $pdo->prepare("UPDATE appointments SET status = 'confirmed' WHERE id = ?")->execute([$apptId]);
        echo json_encode(['success' => true, 'message' => 'Consultation started']);
        break;

    case 'save_notes':
        if ($userRole !== 'doctor') {
            echo json_encode(['success' => false, 'message' => 'Only doctors can save notes']);
            break;
        }

        $apptId = intval($_POST['appointment_id'] ?? 0);
        $notes  = trim($_POST['notes'] ?? '');

        $check = $pdo->prepare("SELECT c.id FROM consultations c JOIN appointments a ON c.appointment_id = a.id WHERE c.appointment_id = ? AND a.doctor_id = ?");
        $check->execute([$apptId, $userId]);
        if (!$check->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Consultation not found']);
            break;
        }

        $pdo->prepare("UPDATE consultations SET transcript = ? WHERE appointment_id = ?")
            ->execute([$notes, $apptId]);

        echo json_encode(['success' => true, 'message' => 'Notes saved']);
        break;

    case 'ai_summary':
        if ($userRole !== 'doctor') {
            echo json_encode(['success' => false, 'message' => 'Only doctors can generate summaries']);
            break;
        }

        $apptId = intval($_POST['appointment_id'] ?? 0);

        $stmt = $pdo->prepare("SELECT c.transcript, c.ai_summary, a.chief_complaint,
            CONCAT(u.first_name,' ',u.last_name) AS patient_name
            FROM consultations c
            JOIN appointments a ON c.appointment_id = a.id
            JOIN users u ON a.patient_id = u.id
            WHERE c.appointment_id = ? AND a.doctor_id = ?");
        $stmt->execute([$apptId, $userId]);
        $consult = $stmt->fetch();

        if (!$consult) {
            echo json_encode(['success' => false, 'message' => 'Consultation not found']);
            break;
        }

        if (empty($consult['transcript'])) {
            echo json_encode(['success' => false, 'message' => 'Please add notes before generating a summary']);
            break;
        }

        if (OPENAI_API_KEY === 'YOUR_OPENAI_API_KEY') {
            $mock = [
                'chief_complaint' => $consult['chief_complaint'] ?? 'Not specified',
                'findings'        => 'Patient presented with the documented symptoms. Clinical examination performed.',
                'diagnosis'       => 'To be determined based on further evaluation.',
                'plan'            => 'Follow-up in 1-2 weeks. Prescribed rest and monitoring.',
                'action_items'    => ['Schedule follow-up appointment', 'Monitor symptoms', 'Return if condition worsens'],
                'follow_up_date'  => date('Y-m-d', strtotime('+7 days')),
            ];
            $pdo->prepare("UPDATE consultations SET ai_summary = ?, action_items = ?, follow_up_date = ? WHERE appointment_id = ?")
                ->execute([json_encode($mock), json_encode($mock['action_items']), $mock['follow_up_date'], $apptId]);
            echo json_encode(['success' => true, 'summary' => $mock, 'demo' => true]);
            break;
        }

        $prompt = "You are a medical assistant. Based on the consultation notes below, generate a structured clinical summary in JSON format with these exact keys: chief_complaint, findings, diagnosis, plan, action_items (array of strings), follow_up_date (YYYY-MM-DD, 1-2 weeks from today).

Patient: {$consult['patient_name']}
Chief Complaint: " . ($consult['chief_complaint'] ?? 'Not specified') . "
Consultation Notes:
{$consult['transcript']}

Respond ONLY with valid JSON. No markdown, no explanation.";

        $ch = curl_init('https://api.openai.com/v1/chat/completions');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . OPENAI_API_KEY,
            ],
            CURLOPT_POSTFIELDS => json_encode([
                'model'       => OPENAI_MODEL,
                'messages'    => [['role' => 'user', 'content' => $prompt]],
                'max_tokens'  => 600,
                'temperature' => 0.3,
            ]),
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            echo json_encode(['success' => false, 'message' => 'OpenAI API error. Check your API key.']);
            break;
        }

        $decoded = json_decode($response, true);
        $content = $decoded['choices'][0]['message']['content'] ?? '';
        $summary = json_decode(trim($content), true);

        if (!$summary) {
            echo json_encode(['success' => false, 'message' => 'Failed to parse AI response']);
            break;
        }

        $pdo->prepare("UPDATE consultations SET ai_summary = ?, action_items = ?, follow_up_date = ? WHERE appointment_id = ?")
            ->execute([
                json_encode($summary),
                json_encode($summary['action_items'] ?? []),
                $summary['follow_up_date'] ?? null,
                $apptId,
            ]);

        echo json_encode(['success' => true, 'summary' => $summary]);
        break;

    case 'end_session':
        if ($userRole !== 'doctor') {
            echo json_encode(['success' => false, 'message' => 'Only doctors can end sessions']);
            break;
        }

        $apptId = intval($_POST['appointment_id'] ?? 0);

        if (!$apptId) {
            echo json_encode(['success' => false, 'message' => 'Invalid appointment ID']);
            break;
        }

        // Verify doctor owns this appointment
        $check = $pdo->prepare("SELECT a.id FROM appointments a WHERE a.id = ? AND a.doctor_id = ? AND a.status IN ('confirmed', 'completed')");
        $check->execute([$apptId, $userId]);
        if (!$check->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Appointment not found or not authorized']);
            break;
        }

        // Update consultations - create record if missing
        $consultCheck = $pdo->prepare("SELECT id FROM consultations WHERE appointment_id = ?");
        $consultCheck->execute([$apptId]);
        if (!$consultCheck->fetch()) {
            $pdo->prepare("INSERT INTO consultations (appointment_id, started_at, ended_at) VALUES (?, NOW(), NOW())")
                ->execute([$apptId]);
        } else {
            $pdo->prepare("UPDATE consultations SET ended_at = NOW() WHERE appointment_id = ?")
                ->execute([$apptId]);
        }

        // Update appointment status
        $pdo->prepare("UPDATE appointments SET status = 'completed' WHERE id = ?")
            ->execute([$apptId]);

        echo json_encode(['success' => true, 'message' => 'Session ended and appointment marked complete']);
        break;

    case 'get':
        $apptId = intval($_GET['appointment_id'] ?? 0);

        $stmt = $pdo->prepare("SELECT c.* FROM consultations c JOIN appointments a ON c.appointment_id = a.id WHERE c.appointment_id = ? AND (a.patient_id = ? OR a.doctor_id = ?)");
        $stmt->execute([$apptId, $userId, $userId]);
        $consult = $stmt->fetch();

        if (!$consult) {
            echo json_encode(['success' => false, 'message' => 'Not found']);
            break;
        }

        if ($consult['ai_summary'])  $consult['ai_summary']  = json_decode($consult['ai_summary'],  true);
        if ($consult['action_items']) $consult['action_items'] = json_decode($consult['action_items'], true);

        echo json_encode(['success' => true, 'consultation' => $consult]);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Unknown action: ' . htmlspecialchars($action)]);
}