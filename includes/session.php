<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Function to check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Function to check if user is admin
function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

// Function to require login
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: /greentrack/login.html');
        exit();
    }
}

// Function to require admin
function requireAdmin() {
    if (!isAdmin()) {
        header('Location: /greentrack/login.html');
        exit();
    }
}

// Function to get current user ID
function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

// Function to get current username
function getCurrentUsername() {
    return $_SESSION['username'] ?? null;
}

// Function to set user session
function setUserSession($userData) {
    $_SESSION['user_id'] = $userData['id'];
    $_SESSION['username'] = $userData['username'];
    $_SESSION['role'] = $userData['role'];
    $_SESSION['email'] = $userData['email'];
}

// Function to clear user session
function clearUserSession() {
    session_unset();
    session_destroy();
}
?> 