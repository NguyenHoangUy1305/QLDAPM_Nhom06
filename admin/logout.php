<?php
require_once __DIR__ . '/../config/db.php';
unset($_SESSION['admin_user']);
header('Location: login.php');
exit;
