<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once '../config/database.php';

$userId   = $_SESSION['user_id'];
$userRole = $_SESSION['user_role'];
$action   = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {

    case 'get_doctors':
        $stmt = $pdo->query("SELECT u.id, CONCAT(u.first_name, ' ', u.last_name) AS name, dp.specialization, dp.consultation_fee FROM users u LEFT JOIN doctor_profiles dp ON u.id = dp.user_id WHERE u.role = 'doctor' AND u.is_active = 1 ORDER BY u.first_name");
        echo json_encode(['success' => true, 'doctors' => $stmt->fetchAll()]);
        break;

    case 'get_slots':
        $doctorId = intval($_GET['doctor_id'] ?? 0);
        $date     = $_GET['date'] ?? '';

        if (!$doctorId || !$date) {
            echo json_encode(['success' => false, 'message' => 'Missing parameters']);
            break;
        }

        $dateObj = DateTime::createFromFormat('Y-m-d', $date);
        if (!$dateObj || $dateObj->format('Y-m-d') !== $date) {
            echo json_encode(['success' => false, 'message' => 'Invalid date']);
            break;
        }

        if ($dateObj < new DateTime('today')) {
            echo json_encode(['success' => false, 'message' => 'Cannot book past dates']);
            break;
        }

        $stmt = $pdo->prepare("SELECT appointment_time FROM appointments WHERE doctor_id = ? AND appointment_date = ? AND status NOT IN ('cancelled')");
        $stmt->execute([$doctorId, $date]);
        $booked = array_column($stmt->fetchAll(), 'appointment_time');

        $slots   = [];
        $start   = strtotime('08:00');
        $end     = strtotime('17:00');
        $interval= 30 * 60;

        for ($t = $start; $t < $end; $t += $interval) {
            $timeStr = date('H:i:s', $t);
            $slots[] = [
                'time'     => $timeStr,
                'label'    => date('g:i A', $t),
                'available'=> !in_array($timeStr, $booked),
            ];
        }

        echo json_encode(['success' => true, 'slots' => $slots]);
        break;

    case 'book':
        if ($userRole !== 'patient') {
            echo json_encode(['success' => false, 'message' => 'Only patients can book appointments']);
            break;
        }

        $doctorId  = intval($_POST['doctor_id'] ?? 0);
        $date      = trim($_POST['date'] ?? '');
        $time      = trim($_POST['time'] ?? '');
        $complaint = trim($_POST['complaint'] ?? '');

        if (!$doctorId || !$date || !$time) {
            echo json_encode(['success' => false, 'message' => 'Doctor, date and time are required']);
            break;
        }

        $dateObj = DateTime::createFromFormat('Y-m-d', $date);
        if (!$dateObj || $dateObj < new DateTime('today')) {
            echo json_encode(['success' => false, 'message' => 'Invalid or past date']);
            break;
        }

        $docCheck = $pdo->prepare("SELECT id FROM users WHERE id = ? AND role = 'doctor' AND is_active = 1");
        $docCheck->execute([$doctorId]);
        if (!$docCheck->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Doctor not found']);
            break;
        }

        $slotCheck = $pdo->prepare("SELECT id FROM appointments WHERE doctor_id = ? AND appointment_date = ? AND appointment_time = ? AND status NOT IN ('cancelled')");
        $slotCheck->execute([$doctorId, $date, $time]);
        if ($slotCheck->fetch()) {
            echo json_encode(['success' => false, 'message' => 'This time slot is already booked']);
            break;
        }

        $dupCheck = $pdo->prepare("SELECT id FROM appointments WHERE patient_id = ? AND doctor_id = ? AND appointment_date = ? AND status NOT IN ('cancelled')");
        $dupCheck->execute([$userId, $doctorId, $date]);
        if ($dupCheck->fetch()) {
            echo json_encode(['success' => false, 'message' => 'You already have an appointment with this doctor on this date']);
            break;
        }

        $stmt = $pdo->prepare("INSERT INTO appointments (patient_id, doctor_id, appointment_date, appointment_time, chief_complaint) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$userId, $doctorId, $date, $time, $complaint ?: null]);

        $log = $pdo->prepare("INSERT INTO activity_logs (user_id, action, description, ip_address) VALUES (?, ?, ?, ?)");
        $log->execute([$userId, 'book_appointment', "Booked appointment with doctor ID $doctorId on $date at $time", $_SERVER['REMOTE_ADDR']]);

        echo json_encode(['success' => true, 'message' => 'Appointment booked successfully!']);
        break;

    case 'cancel':
        $apptId = intval($_POST['appointment_id'] ?? 0);
        if (!$apptId) { echo json_encode(['success' => false, 'message' => 'Invalid appointment']); break; }

        if ($userRole === 'patient') {
            $stmt = $pdo->prepare("SELECT id, status FROM appointments WHERE id = ? AND patient_id = ?");
            $stmt->execute([$apptId, $userId]);
        } else {
            $stmt = $pdo->prepare("SELECT id, status FROM appointments WHERE id = ? AND doctor_id = ?");
            $stmt->execute([$apptId, $userId]);
        }

        $appt = $stmt->fetch();
        if (!$appt) { echo json_encode(['success' => false, 'message' => 'Appointment not found']); break; }
        if ($appt['status'] === 'completed') { echo json_encode(['success' => false, 'message' => 'Cannot cancel a completed appointment']); break; }

        $pdo->prepare("UPDATE appointments SET status = 'cancelled' WHERE id = ?")->execute([$apptId]);
        echo json_encode(['success' => true, 'message' => 'Appointment cancelled']);
        break;

    case 'confirm':
        if ($userRole !== 'doctor') { echo json_encode(['success' => false, 'message' => 'Unauthorized']); break; }
        $apptId = intval($_POST['appointment_id'] ?? 0);
        $stmt = $pdo->prepare("SELECT id, status FROM appointments WHERE id = ? AND doctor_id = ?");
        $stmt->execute([$apptId, $userId]);
        $appt = $stmt->fetch();
        if (!$appt) { echo json_encode(['success' => false, 'message' => 'Appointment not found']); break; }
        if ($appt['status'] !== 'pending') { echo json_encode(['success' => false, 'message' => 'Only pending appointments can be confirmed']); break; }
        $pdo->prepare("UPDATE appointments SET status = 'confirmed' WHERE id = ?")->execute([$apptId]);
        echo json_encode(['success' => true, 'message' => 'Appointment confirmed']);
        break;

    case 'complete':
        if ($userRole !== 'doctor') { echo json_encode(['success' => false, 'message' => 'Unauthorized']); break; }
        $apptId = intval($_POST['appointment_id'] ?? 0);
        $stmt = $pdo->prepare("SELECT id, status FROM appointments WHERE id = ? AND doctor_id = ?");
        $stmt->execute([$apptId, $userId]);
        $appt = $stmt->fetch();
        if (!$appt) { echo json_encode(['success' => false, 'message' => 'Appointment not found']); break; }
        if ($appt['status'] !== 'confirmed') { echo json_encode(['success' => false, 'message' => 'Only confirmed appointments can be completed']); break; }
        $pdo->prepare("UPDATE appointments SET status = 'completed' WHERE id = ?")->execute([$apptId]);
        echo json_encode(['success' => true, 'message' => 'Appointment marked as completed']);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Unknown action']);
}