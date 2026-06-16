<?php
// መጀመሪያ ሴሽኑ መጀመሩን ማረጋገጥ
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// የዳታቤዝ ግንኙነት ፋይልን ማካተት
require_once __DIR__ . '/../config/db.php';

/**
 * የተጠቃሚውን ሚና (Role) ለመፈተሽ እና ካልተፈቀደለት ለማስወጣት
 */
function require_login($allowed_roles = null) {
    if (empty($_SESSION['user'])) {
        header('Location: ' . bhu_base() . 'login.php');
        exit;
    }
    if ($allowed_roles !== null) {
        $role = $_SESSION['user']['role'];
        $allowed = is_array($allowed_roles) ? $allowed_roles : [$allowed_roles];
        if (!in_array($role, $allowed, true)) {
            http_response_code(403);
            die('Access denied for role: ' . htmlspecialchars($role));
        }
    }
}

/**
 * የቤዝ ዩአርኤል (Base URL) መፈለጊያ ፈንክሽን
 */
function bhu_base() {
    $path = dirname($_SERVER['SCRIPT_NAME']);
    if (basename($path) === 'dashboards' || basename($path) === 'actions') {
        $path = dirname($path);
    }
    return rtrim($path, '/\\') . '/';
}

function role_dashboard($role) {
    return match ($role) {
        'admin'     => 'admin_dashboard.php',
        'depthead'  => 'depthead_dashboard.php',
        'registrar' => 'registrar_dashboard.php',
        'student'   => 'student_dashboard.php',
        default     => 'office_dashboard.php',
    };
}

/**
 * XSS ጥቃትን ለመከላከል (Escape function)
 */
function e($v) { 
    return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8'); 
}

/**
 * የተዋሃደ እና ደህንነቱ የተጠበቀ የ ፍላሽ መልእክት ማሳያ ፈንክሽን (FIXED DUPLICATION)
 */
function flash($msg = null, $type = 'success') {
    // 1. መልእክት ወደ ሴሽን ለመጫን ሲጠራ (ለምሳሌ፡ flash("መልእክት", "danger"))
    if ($msg !== null) {
        $_SESSION['flash'] = ['msg' => $msg, 'type' => $type];
        return;
    }
    
    // 2. በገጹ ላይ መልእክቱን አውጥቶ ለማሳየት ሲጠራ (ያለ ፓራሜትር)
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        $message = '';
        $message_type = 'success';

        // ሀ. ሴሽኑ በ Array መልክ የመጣ ከሆነ
        if (is_array($flash)) {
            if (isset($flash['message'])) {
                $message = $flash['message'];
            } elseif (isset($flash['msg'])) {
                $message = $flash['msg'];
            } else {
                $message = '';
            }
            $message_type = $flash['type'] ?? 'success';
        } 
        // ለ. ሴሽኑ ተራ ጽሑፍ (String) ብቻ ከሆነ
        else if (is_string($flash)) {
            $message = $flash;
            $message_type = 'success';
        }

        // መልእክቱ ባዶ ካልሆነ በሚያምር የ Bootstrap አሌርት ያሳየው
        if (!empty($message)) {
            echo '<div class="alert alert-' . e($message_type) . ' alert-dismissible fade show" role="alert" style="margin: 15px 0;">'
                . e($message)
                . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
        }
        
        // ካሳየ በኋላ ሴሽኑን ያጥፋው
        unset($_SESSION['flash']);
    }
}
?>