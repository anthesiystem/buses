<?php
// /final/mapa/server/session_acl.php
header('Content-Type: application/json; charset=utf-8');
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
echo json_encode($_SESSION['acl'] ?? ['all'=>false,'mods'=>[]], JSON_UNESCAPED_UNICODE);
