<?php
require_once __DIR__ . '/../server/config.php';
require_once __DIR__ . '/../server/auth.php';

header('Content-Type: text/html; charset=utf-8');

// Mapa de estados
$ENT = [
    'AGU'=>[ 1,'AGUASCALIENTES'],
    'BCN'=>[ 2,'BAJA CALIFORNIA'],
    'BCS'=>[ 3,'BAJA CALIFORNIA SUR'],
    'CAM'=>[ 4,'CAMPECHE'],
    'CHP'=>[ 5,'CHIAPAS'],
    'CHH'=>[ 6,'CHIHUAHUA'],
    'CMX'=>[ 7,'CIUDAD DE MEXICO'],
    'COA'=>[ 8,'COAHUILA'],
    'COL'=>[ 9,'COLIMA'],
    'DUR'=>[10,'DURANGO'],
    'GUA'=>[11,'GUANAJUATO'],
    'GRO'=>[12,'GUERRERO'],
    'HID'=>[13,'HIDALGO'],
    'JAL'=>[14,'JALISCO'],
    'MEX'=>[15,'ESTADO DE MEXICO'],
    'MIC'=>[16,'MICHOACAN'],
    'MOR'=>[17,'MORELOS'],
    'NAY'=>[18,'NAYARIT'],
    'NLE'=>[19,'NUEVO LEON'],
    'OAX'=>[20,'OAXACA'],
    'PUE'=>[21,'PUEBLA'],
    'QUE'=>[22,'QUERETARO'],
    'ROO'=>[23,'QUINTANA ROO'],
    'SLP'=>[24,'SAN LUIS POTOSI'],
    'SIN'=>[25,'SINALOA'],
    'SON'=>[26,'SONORA'],
    'TAB'=>[27,'TABASCO'],
    'TAM'=>[28,'TAMAULIPAS'],
    'TLA'=>[29,'TLAXCALA'],
    'VER'=>[30,'VERACRUZ'],
    'YUC'=>[31,'YUCATAN'],
    'ZAC'=>[32,'ZACATECAS'],
];

// Leer el SVG
$svgPath = __DIR__ . '/mapa.svg';
$svg = file_get_contents($svgPath);

echo "<h2>Debug SVG y Estados</h2>";

// Verificar SVG
if ($svg === false) {
    echo "<p style='color:red'>Error: No se pudo leer el archivo SVG</p>";
    exit;
}

// Extraer todos los paths del SVG
preg_match_all('/<path\b[^>]*\bid="MX-([A-Z]{3})"[^>]*>/u', $svg, $matches);

echo "<h3>Estados encontrados en SVG:</h3>";
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Código</th><th>ID en \$ENT</th><th>Nombre en \$ENT</th><th>data-entidad-id actual</th></tr>";

foreach ($matches[1] as $code) {
    $currentPath = strstr($svg, "id=\"MX-$code\"");
    $currentPath = substr($currentPath, 0, strpos($currentPath, '>') + 1);
    
    preg_match('/data-entidad-id="(\d+)"/', $currentPath, $idMatch);
    $currentId = $idMatch ? $idMatch[1] : 'No encontrado';
    
    $inEnt = isset($ENT[$code]);
    $entId = $inEnt ? $ENT[$code][0] : 'N/A';
    $entName = $inEnt ? $ENT[$code][1] : 'N/A';
    
    $style = !$inEnt ? "background-color: #ffcccc;" : "";
    
    echo "<tr style='$style'>";
    echo "<td>$code</td>";
    echo "<td>$entId</td>";
    echo "<td>$entName</td>";
    echo "<td>$currentId</td>";
    echo "</tr>";
}

echo "</table>";

// Debug de permisos del usuario actual
$userId = (int)($_SESSION['user_id'] ?? $_SESSION['usuario']['ID'] ?? 0);
$nivel = (int)($_SESSION['usuario']['nivel'] ?? $_SESSION['nivel'] ?? 0);

echo "<h3>Información del Usuario:</h3>";
echo "<p>ID: $userId</p>";
echo "<p>Nivel: $nivel</p>";

// Obtener permisos
$modId = 10;
try {
    $stmMod = $pdo->prepare("SELECT ID FROM modulo WHERE descripcion = 'mapa_general' LIMIT 1");
    if ($stmMod->execute() && ($row = $stmMod->fetch(PDO::FETCH_ASSOC))) {
        $modId = (int)$row['ID'];
    }
} catch (\Throwable $e) {
    echo "<p style='color:red'>Error obteniendo módulo: " . htmlspecialchars($e->getMessage()) . "</p>";
}

$stmt = $pdo->prepare("
    SELECT FK_entidad, FK_bus 
    FROM permiso_usuario 
    WHERE Fk_usuario = ? 
    AND Fk_modulo = ? 
    AND activo = 1
");
$stmt->execute([$userId, $modId]);
$permisos = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<h3>Permisos del Usuario:</h3>";
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Entidad ID</th><th>Nombre Entidad</th><th>Bus ID</th></tr>";

foreach ($permisos as $p) {
    $entId = $p['FK_entidad'] ?? 'NULL';
    $entName = 'N/A';
    
    // Buscar nombre de la entidad
    if ($entId !== 'NULL') {
        foreach ($ENT as $data) {
            if ($data[0] == $entId) {
                $entName = $data[1];
                break;
            }
        }
    }
    
    echo "<tr>";
    echo "<td>" . htmlspecialchars($entId) . "</td>";
    echo "<td>" . htmlspecialchars($entName) . "</td>";
    echo "<td>" . htmlspecialchars($p['FK_bus'] ?? 'NULL') . "</td>";
    echo "</tr>";
}

echo "</table>";
