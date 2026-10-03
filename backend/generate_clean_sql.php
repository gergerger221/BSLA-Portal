<?php
$sql = file_get_contents('sia_highschool_complete_database.sql');

// Remove CREATE DATABASE and USE statements
$sql = preg_replace('/CREATE DATABASE.*?;/is', '', $sql);
$sql = preg_replace('/USE `.*?`;/is', '', $sql);

file_put_contents('backend/clean_infinityfree_database.sql', $sql);
echo "Generated backend/clean_infinityfree_database.sql (" . strlen($sql) . " bytes)\n";
