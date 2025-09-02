<?php
echo "Error Log: " . ini_get('error_log') . "\n";
echo "Log Errors: " . ini_get('log_errors') . "\n";
echo "Display Errors: " . ini_get('display_errors') . "\n";
echo "Error Reporting: " . error_reporting() . "\n";

// Test log
error_log("TEST LOG MESSAGE FROM DEBUG");
echo "Test log message sent.\n";
?>
