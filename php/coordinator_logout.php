<?php
require_once 'config.php';

// Clear all session data and destroy the session
$_SESSION = [];
session_destroy();

header('Location: coordinator_login.php');
exit;