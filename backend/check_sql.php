<?php
$sql = file_get_contents('sia_highschool_complete_database.sql');
preg_match_all('/CREATE TABLE `([a-z0-9_]+)`/i', $sql, $m);
echo "Found " . count($m[1]) . " tables in sia_highschool_complete_database.sql:\n";
foreach ($m[1] as $t) {
    preg_match('/INSERT INTO `' . $t . '` VALUES \((.+?)\);/s', $sql, $ins);
    $hasData = !empty($ins);
    echo "- $t " . ($hasData ? "(WITH DATA)" : "(NO DATA / EMPTY)") . "\n";
}
