<?php
// Migration to add site_manager_id column to the projects table
require_once __DIR__ . '/../../config/database.php';

try {
    $db = new Database();
    $conn = $db->connect();
    
    // Check if site_manager_id column already exists
    $checkColumnSql = "SELECT COLUMN_NAME 
                       FROM INFORMATION_SCHEMA.COLUMNS 
                       WHERE TABLE_SCHEMA = DATABASE() 
                       AND TABLE_NAME = 'projects' 
                       AND COLUMN_NAME = 'site_manager_id'";
                       
    $stmt = $conn->query($checkColumnSql);
    
    if ($stmt->rowCount() == 0) {
        // Add site_manager_id column
        $addColumnSql = "ALTER TABLE projects 
                         ADD COLUMN site_manager_id INT UNSIGNED NULL AFTER owner_id,
                         ADD CONSTRAINT fk_projects_site_manager 
                         FOREIGN KEY (site_manager_id) 
                         REFERENCES users(id) ON DELETE SET NULL";
                         
        $conn->exec($addColumnSql);
        echo "Migration successful: Added site_manager_id column to projects table.\n";
    } else {
        echo "Migration skipped: site_manager_id column already exists in projects table.\n";
    }
    
} catch (PDOException $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
}
