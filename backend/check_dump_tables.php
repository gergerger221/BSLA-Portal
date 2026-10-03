<?php
$f = 'backend/clean_infinityfree_database.sql';
$content = file_get_contents($f);
echo "File size: " . strlen($content) . " bytes\n";
echo "First 200 bytes (hex):\n";
echo bin2hex(substr($content, 0, 100)) . "\n";
echo "First 200 chars:\n" . substr($content, 0, 200) . "\n";
preg_match_all('/CREATE TABLE `(.*?)`/i', $content, $m);
echo "Tables found: " . count($m[1]) . "\n";
print_r($m[1]);
