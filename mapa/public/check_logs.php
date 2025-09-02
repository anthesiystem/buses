<?php
// Mostrar errores recientes de PHP
header('Content-Type: text/plain; charset=utf-8');

$log_files = [
    'C:/Wemp/logs/php_error.log',
    'C:/Windows/Temp/php-errors.log',
    'C:/php/logs/php_error.log',
    ini_get('error_log')
];

foreach ($log_files as $log_file) {
    if ($log_file && file_exists($log_file)) {
        echo "=== Log file: $log_file ===\n";
        
        // Leer las últimas 20 líneas
        $lines = file($log_file);
        if ($lines) {
            $recent_lines = array_slice($lines, -20);
            foreach ($recent_lines as $line) {
                if (strpos($line, date('Y-m-d')) !== false || strpos($line, 'registrar') !== false) {
                    echo $line;
                }
            }
        }
        echo "\n\n";
    }
}

echo "=== Configuración PHP Error Log ===\n";
echo "error_log: " . ini_get('error_log') . "\n";
echo "log_errors: " . ini_get('log_errors') . "\n";
echo "display_errors: " . ini_get('display_errors') . "\n";
?>
