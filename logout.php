<?php
require_once __DIR__ . '/config/db.php';
unset($_SESSION['user']);
header('Location: index.php');
exit;
