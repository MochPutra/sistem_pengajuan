<?php
function escape($value) {
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function flash($message, $type = 'success') {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    $_SESSION['flash'] = [
        'message' => $message,
        'type' => $type,
    ];
}

function showFlash() {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    if (!empty($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        echo '<div class="alert alert-' . escape($flash['type']) . ' alert-dismissible fade show" role="alert">';
        echo escape($flash['message']);
        echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
        echo '</div>';
    }
}

function currentUser() {
    return $_SESSION['user'] ?? null;
}

function isAdmin() {
    return currentUser() && currentUser()['role'] === 'Admin';
}

function fileIcon($filename) {
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    switch ($ext) {
        case 'pdf': return 'bi-file-earmark-pdf-fill text-danger';
        case 'jpg':
        case 'jpeg':
        case 'png': return 'bi-file-earmark-image-fill text-success';
        default: return 'bi-file-earmark-fill text-secondary';
    }
}
