<?php
// Migration script for database schema changes

// Load database configuration
require_once __DIR__ . '/../config/database.php';

// Connect to the database
$database = new Database();
$pdo = $database->connect();

// Execute the task progress tracking migration
echo "Executing task progress tracking migration...\n";
$taskProgressMigration = file_get_contents(__DIR__ . '/migrations/add_task_progress_tracking.sql');

try {
    $pdo->exec($taskProgressMigration);
    echo "Task progress tracking migration completed successfully.\n";
} catch (PDOException $e) {
    echo "Error executing task progress tracking migration: " . $e->getMessage() . "\n";
}

// Execute the attendance check-in/out migration
echo "Executing attendance check-in/out migration...\n";
$attendanceMigration = file_get_contents(__DIR__ . '/migrations/add_check_in_out_to_attendance.sql');

try {
    $pdo->exec($attendanceMigration);
    echo "Attendance check-in/out migration completed successfully.\n";
} catch (PDOException $e) {
    echo "Error executing attendance check-in/out migration: " . $e->getMessage() . "\n";
}

// Execute the site manager assignment migration
echo "Executing site manager assignment migration...\n";
$siteManagerMigration = file_get_contents(__DIR__ . '/migrations/add_site_manager_to_projects.sql');

try {
    $pdo->exec($siteManagerMigration);
    echo "Site manager assignment migration completed successfully.\n";
} catch (PDOException $e) {
    echo "Error executing site manager assignment migration: " . $e->getMessage() . "\n";
}

// Run PHP-based migrations
echo "Running PHP-based migrations...\n";
require_once __DIR__ . '/migrations/add_site_manager_to_projects.php';

// Additive: Attendance approvals enhancements (non-destructive)
echo "Applying attendance approvals enhancements...\n";
try {
    // Add approved_by column if missing
    $stmt = $pdo->query("SHOW COLUMNS FROM attendance LIKE 'approved_by'");
    if ($stmt->rowCount() === 0) {
        $pdo->exec("ALTER TABLE attendance ADD COLUMN approved_by INT NULL");
        echo "Added approved_by column to attendance table.\n";
    } else {
        echo "approved_by column already exists in attendance table.\n";
    }

    // Add approved_at column if missing
    $stmt = $pdo->query("SHOW COLUMNS FROM attendance LIKE 'approved_at'");
    if ($stmt->rowCount() === 0) {
        $pdo->exec("ALTER TABLE attendance ADD COLUMN approved_at DATETIME NULL");
        echo "Added approved_at column to attendance table.\n";
    } else {
        echo "approved_at column already exists in attendance table.\n";
    }

    // Create attendance_approvals table if it doesn't exist
    $pdo->exec("CREATE TABLE IF NOT EXISTS attendance_approvals (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        attendance_id INT UNSIGNED NOT NULL,
        approver_id INT UNSIGNED NOT NULL,
        role VARCHAR(50) NULL,
        action VARCHAR(20) NOT NULL,
        remarks TEXT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_attendance_id (attendance_id),
        INDEX idx_approver_id (approver_id),
        CONSTRAINT fk_attendance_approvals_attendance FOREIGN KEY (attendance_id) REFERENCES attendance(id) ON DELETE CASCADE,
        CONSTRAINT fk_attendance_approvals_users FOREIGN KEY (approver_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "Created or confirmed attendance_approvals table exists.\n";
} catch (PDOException $e) {
    echo "Error applying attendance approvals enhancements: " . $e->getMessage() . "\n";
}

// Optional standardization: align attendance_approvals schema (role/created_at), backfill from legacy level/approved_at
echo "Standardizing attendance_approvals schema (optional)...\n";
try {
    // Ensure 'role' column exists; backfill from 'level' if present
    $hasRole = $pdo->query("SHOW COLUMNS FROM attendance_approvals LIKE 'role'");
    if ($hasRole && $hasRole->rowCount() === 0) {
        $pdo->exec("ALTER TABLE attendance_approvals ADD COLUMN role VARCHAR(50) NULL AFTER approver_id");
        echo "Added role column to attendance_approvals.\n";
    } else {
        echo "role column already present in attendance_approvals.\n";
    }
    $hasLevel = $pdo->query("SHOW COLUMNS FROM attendance_approvals LIKE 'level'");
    if ($hasLevel && $hasLevel->rowCount() > 0) {
        // Backfill role from level where role is null or empty
        $pdo->exec("UPDATE attendance_approvals SET role = level WHERE (role IS NULL OR role = '')");
        echo "Backfilled role from level.\n";
    }

    // Ensure 'created_at' column exists; backfill from 'approved_at' if present
    $hasCreatedAt = $pdo->query("SHOW COLUMNS FROM attendance_approvals LIKE 'created_at'");
    if ($hasCreatedAt && $hasCreatedAt->rowCount() === 0) {
        $pdo->exec("ALTER TABLE attendance_approvals ADD COLUMN created_at DATETIME NULL AFTER remarks");
        echo "Added created_at column to attendance_approvals.\n";
    } else {
        echo "created_at column already present in attendance_approvals.\n";
    }
    $hasApprovedAt = $pdo->query("SHOW COLUMNS FROM attendance_approvals LIKE 'approved_at'");
    if ($hasApprovedAt && $hasApprovedAt->rowCount() > 0) {
        // Backfill created_at from approved_at where created_at is null
        $pdo->exec("UPDATE attendance_approvals SET created_at = approved_at WHERE created_at IS NULL");
        echo "Backfilled created_at from approved_at.\n";
    }

    // Keep legacy columns if they exist (non-destructive); no drops.
} catch (PDOException $e) {
    echo "Error standardizing attendance_approvals schema: " . $e->getMessage() . "\n";
}

// ---- Additive: Tax module objects (payments table, tax_audit, stored procedure) ----
try {
    echo "Applying tax module additive objects...\n";
    // payments table (generic payments, separate from worker_payments)
    $pdo->exec("CREATE TABLE IF NOT EXISTS payments (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        user_id INT UNSIGNED NOT NULL,
        project_id INT UNSIGNED NOT NULL,
        amount DECIMAL(12,2) NOT NULL,
        status VARCHAR(50) NOT NULL DEFAULT 'approved',
        date DATE NOT NULL,
        INDEX idx_user_id (user_id),
        INDEX idx_project_id (project_id),
        INDEX idx_status (status),
        INDEX idx_date (date),
        CONSTRAINT fk_payments_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        CONSTRAINT fk_payments_project FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "Created or confirmed payments table exists.\n";

    // tax_audit table for traceability
    $pdo->exec("CREATE TABLE IF NOT EXISTS tax_audit (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        requested_by INT UNSIGNED NULL,
        role_filter VARCHAR(50) NULL,
        from_date DATE NULL,
        to_date DATE NULL,
        rows_returned INT UNSIGNED NOT NULL DEFAULT 0,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_requested_by (requested_by),
        INDEX idx_created_at (created_at),
        CONSTRAINT fk_tax_audit_user FOREIGN KEY (requested_by) REFERENCES users(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "Created or confirmed tax_audit table exists.\n";

    // Stored procedure: drop if exists then create
    $pdo->exec("DROP PROCEDURE IF EXISTS sp_get_tax_breakdown_by_role");
    $proc = "CREATE PROCEDURE sp_get_tax_breakdown_by_role(
        IN p_role VARCHAR(50),
        IN p_from DATE,
        IN p_to DATE
    )
    BEGIN
        SELECT 
            p.id AS payment_id,
            u.name AS user_name,
            u.role AS user_role,
            pr.name AS project_name,
            pr.project_type,
            p.amount AS base_amount,
            CASE WHEN pr.project_type = 'Government' THEN 0.10 ELSE 0.15 END AS vat_rate,
            ROUND(p.amount * (CASE WHEN pr.project_type = 'Government' THEN 0.10 ELSE 0.15 END), 2) AS vat_amount,
            CASE WHEN u.role = 'logistic_officer' THEN 0.00 ELSE 0.05 END AS ait_rate,
            ROUND(p.amount * (CASE WHEN u.role = 'logistic_officer' THEN 0.00 ELSE 0.05 END), 2) AS ait_amount,
            ROUND(p.amount - (p.amount * (CASE WHEN pr.project_type = 'Government' THEN 0.10 ELSE 0.15 END))
                 - (p.amount * (CASE WHEN u.role = 'logistic_officer' THEN 0.00 ELSE 0.05 END)), 2) AS net_payable
        FROM payments p
        JOIN users u ON p.user_id = u.id
        JOIN projects pr ON p.project_id = pr.id
        WHERE p.status = 'approved'
          AND p.date BETWEEN p_from AND p_to
          AND (p_role IS NULL OR p_role = '' OR u.role = p_role)
        ORDER BY p.date DESC, p.id DESC;
    END";
    $pdo->exec($proc);
    echo "Created stored procedure sp_get_tax_breakdown_by_role.\n";
} catch (PDOException $e) {
    echo "Error applying tax module objects: " . $e->getMessage() . "\n";
}

echo "All migrations completed.\n";
