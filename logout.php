<?php
require_once 'db.php';

// Destroy session
session_destroy();

// Redirect to login page
redirectJS('login.php');
?>
