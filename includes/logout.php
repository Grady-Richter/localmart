<?php
// logout.php — Universal logout handler for all roles.
// Destroys the session and redirects to the appropriate login page.
session_start();

$role = $_SESSION['role'] ?? 'pembeli';

session_unset();
session_destroy();

// Build the base URL dynamically so redirects work regardless of subfolder depth.
// We go one level up from /includes/ to reach the project root.
$root = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');         // e.g. /localmart/includes
$root = rtrim(dirname($root), '/\\');                           // e.g. /localmart

if ($role === 'penjual') {
    header('Location: ' . $root . '/login_penjual.php');
} elseif ($role === 'admin') {
    header('Location: ' . $root . '/login_admin.php');
} else {
    header('Location: ' . $root . '/login_pembeli.php');
}
exit;
