<?php
require_once __DIR__ . '/../../../server/config.php';
require_once __DIR__ . '/../../../server/auth.php';
require_login_or_redirect();

header('Content-Type: text/html; charset=utf-8');

$userId = (int)($_SESSION['user_id'] ?? $_SESSION['usuario']['ID'] ?? 0);
$nivel = (int)($_SESSION['usuario']['nivel'] ?? $_SESSION['nivel'] ?? 0);

// Obtener ID del módulo mapa_general (fallback a 10)
$modId = 10;
try {
    $stmMod = $pdo->prepare("SELECT ID FROM modulo WHERE descripcion = 'mapa_general' LIMIT 1");
    if ($stmMod->execute() && ($row = $stmMod->fetch(PDO::FETCH_ASSOC))) {
        $modId = (int)$row['ID'];
    }
} catch (\Throwable $e) {
    error_log("Error obteniendo módulo: " . $e->getMessage());
}

// Cargar entidades activas
$rowsEnt = $pdo->query("SELECT ID, descripcion FROM entidad WHERE activo = 1")->fetchAll(PDO::FETCH_ASSOC);
$allIds = array_map('intval', array_column($rowsEnt, 'ID'));
$nameById = [];
foreach ($rowsEnt as $r) {
    $nameById[(int)$r['ID']] = $r['descripcion'];
}

// Admin (nivel >=3) => todas
$permitidas = [];
if ($nivel >= 3) {
    $permitidas = $allIds;
} else {
    // Consultar permisos específicos
    $stmt = $pdo->prepare("
        SELECT FK_entidad, FK_bus 
        FROM permiso_usuario 
        WHERE Fk_usuario = ? 
        AND Fk_modulo = ? 
        AND activo = 1
    ");
    $stmt->execute([$userId, $modId]);
    $permisos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($permisos as $p) {
        if ($p['FK_entidad'] === null) {
            $permitidas = $allIds;
            break;
        }
        $entId = (int)$p['FK_entidad'];
        if ($entId > 0 && in_array($entId, $allIds)) {
            $permitidas[] = $entId;
        }
    }
    $permitidas = array_values(array_unique($permitidas));
}

// Render de salida amigable + JSON
echo "<h2>Debug permisos &mdash; mapa_general</h2>";
echo "<p><b>Usuario:</b> {$userId} | <b>Nivel:</b> {$nivel}</p>";

echo "<h3>Entidades permitidas (".count($permitidas).")</h3>";
if ($permitidas) {
    echo "<ul>";
    foreach ($permitidas as $id) {
        $nombre = $nameById[$id] ?? '(desconocido)';
        echo "<li>[{$id}] {$nombre}</li>";
    }
    echo "</ul>";
} else {
    echo "<p class='text-muted'><i>Sin entidades permitidas</i></p>";
}

echo "<h3>JSON</h3>";
echo "<pre>".json_encode(['permitidas' => $permitidas], JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT)."</pre>";

echo "<hr>";
echo "<p><a href='../sections/mapag/general.php'>Ir a la vista normal</a></p>";
