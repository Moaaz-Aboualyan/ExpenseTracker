<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // Not logged in
    header("Location: logout.php");
    exit;
}

// Logged in
header("Location: DashboardOverview.php");
exit;
