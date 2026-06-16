<?php
// config/db.php — Database connection for BHU Clearance System
$DB_HOST = 'localhost';
$DB_USER = 'root';
$DB_PASS = '';
$DB_NAME = 'bhu_clearance_db';

$conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
if ($conn->connect_error) {
    die('Database connection failed: ' . $conn->connect_error);
}
$conn->set_charset('utf8mb4');

date_default_timezone_set('Africa/Addis_Ababa');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Convert a Gregorian date/time to an Ethiopian calendar date string.
 */
function ethiopian_date_string($date = null) {
    if ($date instanceof DateTimeInterface) {
        $dt = new DateTimeImmutable($date->format('Y-m-d H:i:s'), new DateTimeZone('Africa/Addis_Ababa'));
    } elseif (is_int($date)) {
        $dt = (new DateTimeImmutable('@' . $date))->setTimezone(new DateTimeZone('Africa/Addis_Ababa'));
    } else {
        $dt = new DateTimeImmutable($date ?? 'now', new DateTimeZone('Africa/Addis_Ababa'));
    }

    [$year, $month, $day] = [
        (int) $dt->format('Y'),
        (int) $dt->format('n'),
        (int) $dt->format('j'),
    ];

    [$eYear, $eMonth, $eDay] = gregorian_to_ethiopian($year, $month, $day);

    static $months = [
        'Meskerem', 'Tikimt', 'Hidar', 'Tahsas', 'Tir', 'Yekatit',
        'Megabit', 'Miyazya', 'Ginbot', 'Sene', 'Hamle', 'Nehase', 'Pagume',
    ];

    return sprintf('%d %s %d E.C.', $eDay, $months[$eMonth - 1], $eYear);
}

function gregorian_to_ethiopian(int $year, int $month, int $day): array {
    $jd = gregorian_to_jd($year, $month, $day);
    $r = ($jd - 1723857) % 1461;
    $n = ($r % 365) + 365 * floor($r / 1460);
    $eYear = 4 * floor(($jd - 1723857) / 1461) + floor($r / 365) - floor($r / 1460);
    $eMonth = floor($n / 30) + 1;
    $eDay = ($n % 30) + 1;
    return [$eYear, $eMonth, $eDay];
}

function gregorian_to_jd(int $year, int $month, int $day): int {
    return (int) (
        floor((1461 * ($year + 4800 + floor(($month - 14) / 12))) / 4)
        + floor((367 * ($month - 2 - 12 * floor(($month - 14) / 12))) / 12)
        - floor((3 * floor(($year + 4900 + floor(($month - 14) / 12)) / 100)) / 4)
        + $day - 32075
    );
}

define('BHU_OFFICES', ['library','cafeteria','dormitory','finance','sports']);
define('OFFICE_LABELS', [
    'library'   => '📚 Library Office',
    'cafeteria' => '🍽️ Cafeteria Office',
    'dormitory' => '🛏️ Dormitory / Proctor Office',
    'finance'   => '💰 Finance Office',
    'sports'    => '⚽ Sports / Store Office',
]);
