<?php
// ============================================
// OTURUM YÖNETİMİ (SESSION GUARD)
// ============================================
session_start();

function isAdminLoggedIn() {
    return isset($_SESSION['admin_id']) && !empty($_SESSION['admin_id']);
}

function requireAdmin() {
    if (!isAdminLoggedIn()) {
        header('Location: admin_login.php');
        exit;
    }
}

function getAdminName() {
    return $_SESSION['admin_isim'] ?? 'Admin';
}

function adminLogout() {
    session_unset();
    session_destroy();
    header('Location: admin_login.php');
    exit;
}
