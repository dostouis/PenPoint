<?php
// models/ProductModel.php
// PenPoint Product & Vending Slot Inventory Management

require_once __DIR__ . '/../config/database.php';

class ProductModel {
    private $conn;

    public function __construct() {
        $this->conn = getConnection();
    }

    public function getAllProducts() {
        if (!$this->conn) {
            return $this->getDefaultProducts();
        }

        // Fallback gracefully if products table isn't created yet by returning standard 8-slot essentials
        $res = @$this->conn->query("SHOW TABLES LIKE 'products'");
        if (!$res || $res->num_rows === 0) {
            return $this->getDefaultProducts();
        }

        $result = $this->conn->query("SELECT * FROM products ORDER BY slot_number ASC");
        if (!$result || $result->num_rows === 0) {
            return $this->getDefaultProducts();
        }

        $products = [];
        while ($row = $result->fetch_assoc()) {
            $products[] = $row;
        }
        return $products;
    }

    public function getProductById($id) {
        $stmt = $this->conn->prepare("SELECT * FROM products WHERE id = ?");
        if (!$stmt) return null;
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $prod = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $prod;
    }

    public function getProductBySlot($slot) {
        if (!$this->conn) {
            foreach ($this->getDefaultProducts() as $p) {
                if ($p['slot_number'] == $slot) return $p;
            }
            return null;
        }
        $stmt = $this->conn->prepare("SELECT * FROM products WHERE slot_number = ?");
        if (!$stmt) return null;
        $stmt->bind_param("i", $slot);
        $stmt->execute();
        $prod = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $prod;
    }

    public function updateStock($slot, $newStock) {
        if (!$this->conn) return true;
        $stmt = $this->conn->prepare("UPDATE products SET stock = ? WHERE slot_number = ?");
        if (!$stmt) return false;
        $stmt->bind_param("ii", $newStock, $slot);
        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }

    public function quickRestock($slot, $addQty) {
        if (!$this->conn) return true;
        $stmt = $this->conn->prepare("UPDATE products SET stock = LEAST(max_capacity, stock + ?) WHERE slot_number = ?");
        if (!$stmt) return false;
        $stmt->bind_param("ii", $addQty, $slot);
        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }

    public function restockAllToMax() {
        if (!$this->conn) return true;
        $success = $this->conn->query("UPDATE products SET stock = max_capacity");
        return (bool)$success;
    }

    public function decrementStock($slot, $qty = 1) {
        $qty = max(1, intval($qty));
        if (!$this->conn) return true;
        $stmt = $this->conn->prepare("UPDATE products SET stock = GREATEST(0, stock - ?), dispensed_count = dispensed_count + ? WHERE slot_number = ? AND stock >= ?");
        if (!$stmt) return false;
        $stmt->bind_param("iiii", $qty, $qty, $slot, $qty);
        $success = $stmt->execute();
        $affected = $stmt->affected_rows;
        $stmt->close();
        return $success && $affected > 0;
    }

    public function getDefaultProducts() {
        return [
            [
                'id' => 1,
                'slot_number' => 1,
                'tier_number' => 1,
                'mechanism_type' => 'roller_feed',
                'name' => 'Long Bond Paper (Pack of 5)',
                'category' => 'Paper',
                'description' => 'Substance 20 long bond paper sheets (8.5 x 13 in) fed short-edge first via DC rubber roller.',
                'best_used_for' => 'Legal deeds, contracts, term papers & major project case studies',
                'specs' => '8.5 x 13 in • Substance 20 (75 GSM) • Pack of 5 Sheets',
                'price' => 15.00,
                'stock' => 18,
                'max_capacity' => 25,
                'low_stock_threshold' => 5,
                'sku' => 'PAP-BOND-LNG',
                'badge' => 'Roller-Feed',
                'image_url' => 'assets/img/bond-short.png',
                'is_active' => 1,
                'dispensed_count' => 34
            ],
            [
                'id' => 2,
                'slot_number' => 2,
                'tier_number' => 2,
                'mechanism_type' => 'roller_feed',
                'name' => 'A4 Bond Paper (Pack of 5)',
                'category' => 'Paper',
                'description' => 'Standard ISO A4 copy & report paper (210 x 297 mm) fed short-edge first via DC roller.',
                'best_used_for' => 'Departmental reports, thesis documentation, syllabus handouts & research articles',
                'specs' => '210 x 297 mm (ISO A4) • 80 GSM High Opacity • Pack of 5 Sheets',
                'price' => 15.00,
                'stock' => 20,
                'max_capacity' => 25,
                'low_stock_threshold' => 5,
                'sku' => 'PAP-BOND-A4',
                'badge' => 'Roller-Feed',
                'image_url' => 'assets/img/bond-a4.png',
                'is_active' => 1,
                'dispensed_count' => 42
            ],
            [
                'id' => 3,
                'slot_number' => 3,
                'tier_number' => 3,
                'mechanism_type' => 'roller_feed',
                'name' => 'Short Bond Paper (Pack of 5)',
                'category' => 'Paper',
                'description' => 'Letter size standard short bond paper (8.5 x 11 in) with adjustable gate thickness.',
                'best_used_for' => 'Daily class essays, quick quizzes, laboratory worksheets & homework exercises',
                'specs' => '8.5 x 11 in (Letter) • Substance 20 • Pack of 5 Sheets',
                'price' => 12.00,
                'stock' => 15,
                'max_capacity' => 25,
                'low_stock_threshold' => 5,
                'sku' => 'PAP-BOND-SHT',
                'badge' => 'Roller-Feed',
                'image_url' => 'assets/img/bond-short.png',
                'is_active' => 1,
                'dispensed_count' => 56
            ],
            [
                'id' => 4,
                'slot_number' => 4,
                'tier_number' => 4,
                'mechanism_type' => 'paddle_push',
                'name' => 'Panda Crystal Ballpen Black',
                'category' => 'Pens',
                'description' => 'Smooth oil-based black ballpen with durable tungsten carbide ball tip for crisp everyday writing.',
                'best_used_for' => 'Quick lecture notes, daily classroom activities, quiz answers & signatures',
                'specs' => '0.7mm Medium Point • Classic Clear Hexagonal Barrel • Black Ink',
                'price' => 10.00,
                'stock' => 18,
                'max_capacity' => 25,
                'low_stock_threshold' => 5,
                'sku' => 'PEN-PANDA-BLK',
                'badge' => 'Everyday Choice',
                'image_url' => 'assets/img/panda.png',
                'is_active' => 1,
                'dispensed_count' => 68
            ],
            [
                'id' => 5,
                'slot_number' => 5,
                'tier_number' => 4,
                'mechanism_type' => 'paddle_push',
                'name' => 'Mongol 2 Exam Pencil (Number 2)',
                'category' => 'Pencils',
                'description' => 'Standard No. 2 medium-soft graphite pencil with soft pink eraser, ideal for computerized OMR sheets.',
                'best_used_for' => 'Standardized exams, midterms, finals, OMR bubble sheets & engineering drafting',
                'specs' => 'Grade No. 2 Medium Graphite • High-Density Core • Built-in Eraser',
                'price' => 12.00,
                'stock' => 15,
                'max_capacity' => 20,
                'low_stock_threshold' => 4,
                'sku' => 'PCL-MGL-NO2',
                'badge' => 'Exam Essential',
                'image_url' => 'assets/img/pencil.png',
                'is_active' => 1,
                'dispensed_count' => 29
            ],
            [
                'id' => 6,
                'slot_number' => 6,
                'tier_number' => 4,
                'mechanism_type' => 'paddle_push',
                'name' => 'Panda Crystal Ballpen Red',
                'category' => 'Pens',
                'description' => 'Vibrant red ink ballpen with smooth glide tungsten ball tip for grading, checking, and highlighting.',
                'best_used_for' => 'Paper checking, editorial corrections, grading quizzes & note annotations',
                'specs' => '0.7mm Medium Point • Classic Clear Hexagonal Barrel • Vibrant Red Ink',
                'price' => 10.00,
                'stock' => 15,
                'max_capacity' => 20,
                'low_stock_threshold' => 4,
                'sku' => 'PEN-PANDA-RED',
                'badge' => 'Checking Pen',
                'image_url' => 'assets/img/pen-red.png',
                'is_active' => 1,
                'dispensed_count' => 0
            ]
        ];
    }
}
