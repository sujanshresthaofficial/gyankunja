<?php
require_once 'config.php';

$_SESSION = [];
if (session_status() === PHP_SESSION_ACTIVE) {
    session_destroy();
}
json_out(['success' => true]);
