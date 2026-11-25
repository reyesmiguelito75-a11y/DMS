<?php

session_start();

// Remove all session data
session_unset();

// Destroy the current session
session_destroy();

// Return to the login page
header("Location: login.php");
exit();

?>