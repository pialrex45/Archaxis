<?php
// Supplier model for ecommerce suppliers

require_once __DIR__ . '/../../config/database.php';

class Supplier {
    private $pdo;

    public function __construct() {
        $database = new Database();
        $this->pdo = $database->connect();
    }

    // Get all suppliers
    public function getAll() {
        try {
            // Order by id for broader schema compatibility (older DBs may not have created_at)
            $stmt = $this->pdo->prepare("SELECT * FROM suppliers ORDER BY id DESC");
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return false;
        }
    }

    // Get supplier by ID
    public function getById($id) {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM suppliers WHERE id = ?");
            $stmt->execute([$id]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            return false;
        }
    }

    // Create supplier
    public function create($data) {
        $name = trim($data['name'] ?? '');
        if ($name === '') {
            return ['success' => false, 'message' => 'Name is required'];
        }
        $email = trim($data['email'] ?? '');
        $phone = trim($data['phone'] ?? '');
        $address = trim($data['address'] ?? '');
        $rating = isset($data['rating']) && is_numeric($data['rating']) ? (float)$data['rating'] : null;

        try {
            $stmt = $this->pdo->prepare(
                "INSERT INTO suppliers (name, email, phone, address, rating, created_at) VALUES (?,?,?,?,?, NOW())"
            );
            $stmt->execute([$name, $email, $phone, $address, $rating]);
            return ['success' => true, 'id' => (int)$this->pdo->lastInsertId(), 'message' => 'Supplier created'];
        } catch (PDOException $e) {
            $debug = function_exists('env') ? (env('APP_DEBUG', false) || in_array(env('APP_ENV', ''), ['local','development','dev'])) : false;
            $msg = $debug ? ('DB error creating supplier: ' . $e->getMessage()) : 'DB error creating supplier';
            return ['success' => false, 'message' => $msg];
        }
    }

    // Update supplier
    public function update($id, $data) {
        $id = (int)$id;
        if ($id <= 0) return ['success' => false, 'message' => 'Invalid ID'];

        $name = trim($data['name'] ?? '');
        if ($name === '') {
            return ['success' => false, 'message' => 'Name is required'];
        }
        $email = trim($data['email'] ?? '');
        $phone = trim($data['phone'] ?? '');
        $address = trim($data['address'] ?? '');
        $rating = isset($data['rating']) && is_numeric($data['rating']) ? (float)$data['rating'] : null;

        try {
            $stmt = $this->pdo->prepare(
                "UPDATE suppliers SET name=?, email=?, phone=?, address=?, rating=?, updated_at = NOW() WHERE id = ?"
            );
            $stmt->execute([$name, $email, $phone, $address, $rating, $id]);
            return ['success' => true, 'message' => 'Supplier updated'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'DB error updating supplier'];
        }
    }

    // Delete supplier
    public function delete($id) {
        $id = (int)$id;
        if ($id <= 0) return ['success' => false, 'message' => 'Invalid ID'];
        try {
            $stmt = $this->pdo->prepare("DELETE FROM suppliers WHERE id = ?");
            $stmt->execute([$id]);
            return ['success' => true, 'message' => 'Supplier deleted'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'DB error deleting supplier'];
        }
    }
}
