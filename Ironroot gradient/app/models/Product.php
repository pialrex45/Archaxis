<?php
// Product model for ecommerce products

require_once __DIR__ . '/../../config/database.php';

class Product {
    private $pdo;

    public function __construct() {
        $database = new Database();
        $this->pdo = $database->connect();
    }

    // Get all products
    public function getAll() {
        try {
            $stmt = $this->pdo->prepare("SELECT p.*, s.name as supplier_name FROM products p JOIN suppliers s ON p.supplier_id = s.id WHERE p.status = 'active' ORDER BY p.created_at DESC");
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return false;
        }
    }

    // Get products by supplier
    public function getBySupplier($supplierId) {
        try {
            $stmt = $this->pdo->prepare("SELECT p.*, s.name as supplier_name FROM products p JOIN suppliers s ON p.supplier_id = s.id WHERE p.supplier_id = ? AND p.status = 'active' ORDER BY p.created_at DESC");
            $stmt->execute([$supplierId]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return false;
        }
    }

    // Get product by ID
    public function getById($id) {
        try {
            $stmt = $this->pdo->prepare("SELECT p.*, s.name as supplier_name FROM products p JOIN suppliers s ON p.supplier_id = s.id WHERE p.id = ?");
            $stmt->execute([$id]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            return false;
        }
    }

    // Create product
    public function create($data) {
        $name = trim($data['name'] ?? '');
        $supplier_id = isset($data['supplier_id']) ? (int)$data['supplier_id'] : 0;
        $unit_price = isset($data['unit_price']) && is_numeric($data['unit_price']) ? (float)$data['unit_price'] : null;
        $unit = trim($data['unit'] ?? '');
        $stock = isset($data['stock']) && is_numeric($data['stock']) ? (int)$data['stock'] : 0;
        $description = trim($data['description'] ?? '');
        $status = $data['status'] ?? 'active';
        if ($name === '' || $supplier_id <= 0 || $unit_price === null || $unit === '') {
            return ['success' => false, 'message' => 'Required fields: name, supplier, unit price, unit'];
        }
        try {
            $stmt = $this->pdo->prepare("INSERT INTO products (name, supplier_id, description, unit_price, unit, stock, status, created_at) VALUES (?,?,?,?,?,?,?, NOW())");
            $stmt->execute([$name, $supplier_id, $description, $unit_price, $unit, $stock, $status]);
            return ['success' => true, 'id' => (int)$this->pdo->lastInsertId(), 'message' => 'Product created'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'DB error creating product'];
        }
    }

    // Update product
    public function update($id, $data) {
        $id = (int)$id; if ($id <= 0) return ['success' => false, 'message' => 'Invalid ID'];
        $name = trim($data['name'] ?? '');
        $supplier_id = isset($data['supplier_id']) ? (int)$data['supplier_id'] : 0;
        $unit_price = isset($data['unit_price']) && is_numeric($data['unit_price']) ? (float)$data['unit_price'] : null;
        $unit = trim($data['unit'] ?? '');
        $stock = isset($data['stock']) && is_numeric($data['stock']) ? (int)$data['stock'] : 0;
        $description = trim($data['description'] ?? '');
        $status = $data['status'] ?? 'active';
        if ($name === '' || $supplier_id <= 0 || $unit_price === null || $unit === '') {
            return ['success' => false, 'message' => 'Required fields: name, supplier, unit price, unit'];
        }
        try {
            $stmt = $this->pdo->prepare("UPDATE products SET name=?, supplier_id=?, description=?, unit_price=?, unit=?, stock=?, status=?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$name, $supplier_id, $description, $unit_price, $unit, $stock, $status, $id]);
            return ['success' => true, 'message' => 'Product updated'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'DB error updating product'];
        }
    }

    // Delete product
    public function delete($id) {
        $id = (int)$id; if ($id <= 0) return ['success' => false, 'message' => 'Invalid ID'];
        try {
            $stmt = $this->pdo->prepare("DELETE FROM products WHERE id = ?");
            $stmt->execute([$id]);
            return ['success' => true, 'message' => 'Product deleted'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'DB error deleting product'];
        }
    }
}
