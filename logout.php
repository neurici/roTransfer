<?php
require_once __DIR__ . '/util.php';
logout_user();
header('Location: index.php');
exit;
