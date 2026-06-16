<?php
// ገጹ ከመከፈቱ በፊት ማንኛውንም የሊንክ መዘግየት ለመከላከል (Output Buffering)
ob_start();

require_once __DIR__ . '/../includes/auth.php';
require_login('student');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['student_id'])) {
    
    $student_id = (int)$_POST['student_id'];
    $request_reason = trim($_POST['request_reason'] ?? '');
    if ($request_reason === '') {
        $_SESSION['flash'] = [
            'message' => 'Please enter a reason before sending the clearance request.',
            'type' => 'danger'
        ];
        header('Location: http://localhost/bhu/dashboards/student_dashboard.php');
        exit();
    }
    $offices = ['library', 'cafeteria', 'dormitory', 'finance', 'sports'];

    // 1. ለሁሉም ቢሮዎች ሁኔታውን ማስተካከል
    foreach ($offices as $office) {
        $check_stmt = $conn->prepare("SELECT student_id FROM clearances WHERE student_id = ? AND office = ?");
        $check_stmt->bind_param('is', $student_id, $office);
        $check_stmt->execute();
        $result = $check_stmt->get_result();

        if ($result->num_rows === 0) {
            $insert_stmt = $conn->prepare("INSERT INTO clearances (student_id, office, status) VALUES (?, ?, 'pending')");
            $insert_stmt->bind_param('is', $student_id, $office);
            $insert_stmt->execute();
        } else {
            $update_stmt = $conn->prepare("UPDATE clearances SET status = 'pending' WHERE student_id = ? AND office = ? AND status != 'cleared'");
            $update_stmt->bind_param('is', $student_id, $office);
            $update_stmt->execute();
        }
    }

    // 2. የመጨረሻ ማጽደቂያ ሰንጠረዥን መፈተሽ
    $check_fa = $conn->prepare("SELECT student_id FROM final_approval WHERE student_id = ?");
    $check_fa->bind_param('i', $student_id);
    $check_fa->execute();
    
    if ($check_fa->get_result()->num_rows === 0) {
        $insert_fa = $conn->prepare("INSERT INTO final_approval (student_id, dept_status, registrar_status, request_reason) VALUES (?, 'pending', 'pending', ?)");
        $insert_fa->bind_param('is', $student_id, $request_reason);
        $insert_fa->execute();
    } else {
        $update_fa = $conn->prepare("UPDATE final_approval SET request_reason = ? WHERE student_id = ?");
        $update_fa->bind_param('si', $request_reason, $student_id);
        $update_fa->execute();
    }

    // 3. የፍላሽ መልእክት ማዘጋጀት
    $_SESSION['flash'] = [
        'message' => "🚀 Clearance request successfully sent to all offices!",
        'type' => 'success'
    ];
    
    // ወደ ተማሪው ዳሽቦርድ መመለሻ ሊንክ (አስተማማኝ እንዲሆን ፍጹም አድራሻ መጠቀም)
    header("Location: http://localhost/bhu/dashboards/student_dashboard.php");
    exit();

} else {
    header("Location: http://localhost/bhu/dashboards/student_dashboard.php");
    exit();
}

// የ Buffering ማብቂያ
ob_end_flush();
?>