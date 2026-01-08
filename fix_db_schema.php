<?php
require_once 'classes/Includes.php';

echo "Checking Database Schema for 'invoices' table...\n";

$db = new Database();
$pdo = $db->getConnection();

// Helper function to check column
function columnExists($pdo, $table, $column) {
    $stmt = $pdo->prepare("SHOW COLUMNS FROM `$table` LIKE ?");
    $stmt->execute([$column]);
    return $stmt->fetch() !== false;
}

// Helper to add column
function addColumn($pdo, $table, $column, $definition) {
    echo "Adding column '$column' to '$table'...\n";
    try {
        $pdo->exec("ALTER TABLE `$table` ADD COLUMN `$column` $definition");
        echo "Success.\n";
    } catch (PDOException $e) {
        echo "Error adding column: " . $e->getMessage() . "\n";
    }
}

// Check account_id
if (!columnExists($pdo, 'invoices', 'account_id')) {
    echo "Column 'account_id' missing.\n";
    addColumn($pdo, 'invoices', 'account_id', "INT(11) NULL DEFAULT NULL AFTER `payment_date`");
} else {
    echo "Column 'account_id' exists.\n";
}

// Check bill_type
if (!columnExists($pdo, 'invoices', 'bill_type')) {
    echo "Column 'bill_type' missing.\n";
    addColumn($pdo, 'invoices', 'bill_type', "ENUM('cash','credit') NOT NULL DEFAULT 'cash' AFTER `account_id`");
} else {
    echo "Column 'bill_type' exists.\n";
}

echo "Done.\n";
