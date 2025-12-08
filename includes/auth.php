<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../DatabaseConfiguration.php';

function is_logged_in() {
    return !empty($_SESSION['user_id']);
}

function require_login() {
    if (!is_logged_in()) {
        header('Location: UserAuthenticationForm.php');
        exit;
    }
}

function current_user() {
    if (!is_logged_in()) return null;
    return [
        'id' => $_SESSION['user_id'],
        'email' => isset($_SESSION['user_email']) ? $_SESSION['user_email'] : null,
        'name' => isset($_SESSION['user_name']) ? $_SESSION['user_name'] : null,
        'currency' => isset($_SESSION['user_currency']) ? $_SESSION['user_currency'] : 'USD',
    ];
}

function get_user_currency() {
    if (is_logged_in() && isset($_SESSION['user_currency'])) {
        return $_SESSION['user_currency'];
    }
    return 'USD';
}

function get_currency_symbol($currency = null) {
    if ($currency === null) {
        $currency = get_user_currency();
    }
    
    $symbols = [
        'USD' => '$',
        'EUR' => '€',
        'GBP' => '£',
        'MYR' => 'RM',
        'JPY' => '¥',
        'AUD' => 'A$',
        'CAD' => 'C$',
        'CHF' => 'CHF',
        'CNY' => '¥',
        'INR' => '₹',
        'SGD' => 'S$',
    ];
    
    return isset($symbols[$currency]) ? $symbols[$currency] : '$';
}

function is_dark_mode() {
    if (is_logged_in() && isset($_SESSION['user_dark_mode'])) {
        return (bool)$_SESSION['user_dark_mode'];
    }
    return false;
}

function e($str) {
    return htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8');
}

?>
