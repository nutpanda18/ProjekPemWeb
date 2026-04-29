<?php
/**
 * Home.php - The Router
 * Checks Cookies instead of Sessions for Vercel compatibility
 */

if (!isset($_COOKIE['isLoggedIn']) || $_COOKIE['isLoggedIn'] !== 'true') {
    // If not logged in, go back to the landing page
    header("Location: /index.html");
    exit();
}

// Redirect based on the role stored in the cookie
if ($_COOKIE['role'] === 'admin') {
    header("Location: /api/dashboard_admin.php");
} else {
    // Ensure this filename matches your user dashboard file
    header("Location: /api/dashboard_user.php"); 
}
exit();
?>