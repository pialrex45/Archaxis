<?php
// PurchaseOrder model for ecommerce purchase orders

require_once __DIR__ . '/../../config/database.php';

class PurchaseOrder {
    private $pdo;

    public function __construct() {
        $database = new Database();
        $this->pdo = $database->connect();
    }

    // Get all purchase orders (basic)
    public function getAll() {
        try {
            $stmt = $this->pdo->prepare("SELECT po.*, s.name as supplier_name, u.name as created_by_name, p.name as project_name FROM purchase_orders po JOIN suppliers s ON po.supplier_id = s.id JOIN users u ON po.created_by = u.id JOIN projects p ON po.project_id = p.id ORDER BY po.created_at DESC");
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return false;
        }
    }

    // Get purchase order by ID
    public function getById($id) {
        try {
            $stmt = $this->pdo->prepare("SELECT po.*, s.name as supplier_name, u.name as created_by_name, p.name as project_name FROM purchase_orders po JOIN suppliers s ON po.supplier_id = s.id JOIN users u ON po.created_by = u.id JOIN projects p ON po.project_id = p.id WHERE po.id = ?");
            $stmt->execute([$id]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            return false;
        }
    }

    // Get items for a purchase order
    public function getItems($purchaseOrderId) {
        try {
            $stmt = $this->pdo->prepare("SELECT poi.*, p.name as product_name, p.unit FROM purchase_order_items poi JOIN products p ON poi.product_id = p.id WHERE poi.purchase_order_id = ?");
            $stmt->execute([$purchaseOrderId]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return false;
        }
    }

    // Create a purchase order with items
    // $items = [ [product_id, quantity, unit_price?], ... ]
    public function create($projectId, $supplierId, $createdBy, $items) {
        try {
            // Basic validations
            if (!is_array($items) || count($items) === 0) {
                return ['success' => false, 'message' => 'At least one item is required'];
            }

            $this->pdo->beginTransaction();

            // Insert PO row
            $stmtPo = $this->pdo->prepare("INSERT INTO purchase_orders (project_id, supplier_id, created_by, status, total_amount, created_at, updated_at) VALUES (?, ?, ?, 'pending', 0, NOW(), NOW())");
            $stmtPo->execute([$projectId, $supplierId, $createdBy]);
            $poId = (int)$this->pdo->lastInsertId();

            $totalAmount = 0.0;

            // Prepare statements
            $stmtProduct = $this->pdo->prepare("SELECT id, supplier_id, unit_price FROM products WHERE id = ? AND status = 'active'");
            $stmtItem = $this->pdo->prepare("INSERT INTO purchase_order_items (purchase_order_id, product_id, quantity, unit_price) VALUES (?, ?, ?, ?)");

            foreach ($items as $item) {
                $productId = (int)($item['product_id'] ?? 0);
                $qty = (int)($item['quantity'] ?? 0);
                $unitPrice = isset($item['unit_price']) ? (float)$item['unit_price'] : null;

                if ($productId <= 0 || $qty <= 0) {
                    $this->pdo->rollBack();
                    return ['success' => false, 'message' => 'Invalid product or quantity'];
                }

                // Validate product and supplier match
                $stmtProduct->execute([$productId]);
                $product = $stmtProduct->fetch();
                if (!$product) {
                    $this->pdo->rollBack();
                    return ['success' => false, 'message' => 'Product not found or inactive'];
                }
                if ((int)$product['supplier_id'] !== (int)$supplierId) {
                    $this->pdo->rollBack();
                    return ['success' => false, 'message' => 'Product supplier mismatch'];
                }

                // Default unit price from product if not provided
                if ($unitPrice === null) {
                    $unitPrice = (float)$product['unit_price'];
                }

                $stmtItem->execute([$poId, $productId, $qty, $unitPrice]);
                $totalAmount += $qty * $unitPrice;
            }

            // Update total amount
            $stmtUpd = $this->pdo->prepare("UPDATE purchase_orders SET total_amount = ? WHERE id = ?");
            $stmtUpd->execute([$totalAmount, $poId]);

            $this->pdo->commit();

            return [
                'success' => true,
                'message' => 'Purchase order created successfully',
                'purchase_order_id' => $poId,
                'total_amount' => $totalAmount
            ];
        } catch (PDOException $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            return ['success' => false, 'message' => 'Purchase order creation error: ' . $e->getMessage()];
        }
    }

    private function allowedTransitions() {
        return [
            'draft'    => ['pending', 'cancelled'],
            'pending'  => ['approved', 'rejected', 'cancelled'],
            'approved' => ['ordered', 'cancelled'],
            'ordered'  => ['delivered', 'cancelled'],
            'delivered'=> [],
            'rejected' => [],
            'cancelled'=> []
        ];
    }

    public function updateStatus($id, $newStatus) {
        try {
            $stmt = $this->pdo->prepare("SELECT status FROM purchase_orders WHERE id = ?");
            $stmt->execute([$id]);
            $row = $stmt->fetch();
            if (!$row) return ['success' => false, 'message' => 'Purchase order not found'];

            $current = $row['status'];
            $allowed = $this->allowedTransitions();
            if (!isset($allowed[$current]) || !in_array($newStatus, $allowed[$current], true)) {
                return ['success' => false, 'message' => 'Invalid status transition'];
            }

            $upd = $this->pdo->prepare("UPDATE purchase_orders SET status = ?, updated_at = NOW() WHERE id = ?");
            $ok = $upd->execute([$newStatus, $id]);
            if ($ok && $upd->rowCount() > 0) {
                return ['success' => true, 'message' => 'Status updated'];
            }
            return ['success' => false, 'message' => 'No change'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Status update error: ' . $e->getMessage()];
        }
    }
}
