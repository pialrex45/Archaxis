<?php
// Migration script to safely add new fields to tasks table
require_once __DIR__ . '/../config/database.php';

// Create database connection
$database = new Database();
$conn = $database->connect();

echo "Starting migration...\n";

// Check if columns exist in tasks table before adding them
$tasksColumns = $conn->query("SHOW COLUMNS FROM tasks")->fetchAll(PDO::FETCH_COLUMN);

// Add completion_percentage column if it doesn't exist
if (!in_array('completion_percentage', $tasksColumns)) {
    try {
        $conn->exec("ALTER TABLE tasks ADD COLUMN completion_percentage INT DEFAULT 0");
        echo "Added completion_percentage column to tasks table\n";
    } catch (PDOException $e) {
        echo "Error adding completion_percentage column: " . $e->getMessage() . "\n";
    }
} else {
    echo "Column completion_percentage already exists in tasks table\n";
}

// Add progress_notes column if it doesn't exist
if (!in_array('progress_notes', $tasksColumns)) {
    try {
        $conn->exec("ALTER TABLE tasks ADD COLUMN progress_notes TEXT");
        echo "Added progress_notes column to tasks table\n";
    } catch (PDOException $e) {
        echo "Error adding progress_notes column: " . $e->getMessage() . "\n";
    }
} else {
    echo "Column progress_notes already exists in tasks table\n";
}

// Add task_photos column if it doesn't exist
if (!in_array('task_photos', $tasksColumns)) {
    try {
        $conn->exec("ALTER TABLE tasks ADD COLUMN task_photos JSON");
        echo "Added task_photos column to tasks table\n";
    } catch (PDOException $e) {
        echo "Error adding task_photos column: " . $e->getMessage() . "\n";
    }
} else {
    echo "Column task_photos already exists in tasks table\n";
}

// Check if columns exist in attendance table before adding them
$attendanceColumns = $conn->query("SHOW COLUMNS FROM attendance")->fetchAll(PDO::FETCH_COLUMN);

// Add check_in_time column if it doesn't exist
if (!in_array('check_in_time', $attendanceColumns)) {
    try {
        $conn->exec("ALTER TABLE attendance ADD COLUMN check_in_time DATETIME");
        echo "Added check_in_time column to attendance table\n";
    } catch (PDOException $e) {
        echo "Error adding check_in_time column: " . $e->getMessage() . "\n";
    }
} else {
    echo "Column check_in_time already exists in attendance table\n";
}

// Add check_out_time column if it doesn't exist
if (!in_array('check_out_time', $attendanceColumns)) {
    try {
        $conn->exec("ALTER TABLE attendance ADD COLUMN check_out_time DATETIME");
        echo "Added check_out_time column to attendance table\n";
    } catch (PDOException $e) {
        echo "Error adding check_out_time column: " . $e->getMessage() . "\n";
    }
} else {
    echo "Column check_out_time already exists in attendance table\n";
}

// Create task_photos table if it doesn't exist
try {
    $conn->exec("
    CREATE TABLE IF NOT EXISTS task_photos (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        task_id INT UNSIGNED NOT NULL,
        photo_path VARCHAR(255) NOT NULL,
        uploaded_at DATETIME NOT NULL,
        FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE
    )
    ");
    echo "Created or confirmed task_photos table exists\n";
} catch (PDOException $e) {
    echo "Error with task_photos table: " . $e->getMessage() . "\n";
}

echo "Migration completed!\n";
