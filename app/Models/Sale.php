<?php

namespace App\Models;

use PDO;
use App\Core\Model;

class Sale extends Model {
    public static function create($data) {
        $db = static::getDB();
        $sql = "INSERT INTO sales (customer_id, customer_name, customer_email, total_amount, payment_status, delivery_status)
                VALUES (:customer_id, :customer_name, :customer_email, :total_amount, :payment_status, :delivery_status)";
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':customer_id', $data['customer_id'], PDO::PARAM_INT);
        $stmt->bindValue(':customer_name', $data['customer_name'], PDO::PARAM_STR);
        $stmt->bindValue(':customer_email', $data['customer_email'], PDO::PARAM_STR);
        $stmt->bindValue(':total_amount', $data['total_amount'], PDO::PARAM_STR);
        $stmt->bindValue(':payment_status', $data['payment_status'], PDO::PARAM_STR);
        $stmt->bindValue(':delivery_status', $data['delivery_status'], PDO::PARAM_STR);

        if ($stmt->execute()) {
            return $db->lastInsertId();
        }
        return false;
    }

    public static function addSaleItem($data) {
        $db = static::getDB();
        $sql = "INSERT INTO sale_items (sale_id, product_id, product_name, quantity, price)
                VALUES (:sale_id, :product_id, :product_name, :quantity, :price)";
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':sale_id', $data['sale_id'], PDO::PARAM_INT);
        $stmt->bindValue(':product_id', $data['product_id'], PDO::PARAM_INT);
        $stmt->bindValue(':product_name', $data['product_name'], PDO::PARAM_STR);
        $stmt->bindValue(':quantity', $data['quantity'], PDO::PARAM_INT);
        $stmt->bindValue(':price', $data['price'], PDO::PARAM_STR);
        return $stmt->execute();
    }

    public static function getById($id) {
        $db = static::getDB();
        $stmt = $db->prepare("SELECT * FROM sales WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $sale = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($sale) {
            $stmt = $db->prepare("SELECT * FROM sale_items WHERE sale_id = :id");
            $stmt->execute([':id' => $id]);
            $sale['items'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        return $sale;
    }

    public static function getAll() {
        $db = static::getDB();
        $stmt = $db->query("
            SELECT s.*, u.username as customer_username
            FROM sales s
            LEFT JOIN users u ON s.customer_id = u.id
            ORDER BY s.sale_date DESC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function getRecent($limit = 5, $customer_id = null) {
        $db = static::getDB();
        if ($customer_id) {
            $query = "SELECT s.*, COUNT(i.id) as items_count
                      FROM sales s
                      JOIN sale_items i ON s.id = i.sale_id
                      WHERE s.customer_id = :customer_id
                      GROUP BY s.id
                      ORDER BY s.sale_date DESC
                      LIMIT :limit";
            $stmt = $db->prepare($query);
            $stmt->bindValue(':customer_id', $customer_id, PDO::PARAM_INT);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        } else {
            $query = "SELECT s.*, COUNT(i.id) as items_count
                      FROM sales s
                      JOIN sale_items i ON s.id = i.sale_id
                      GROUP BY s.id
                      ORDER BY s.sale_date DESC
                      LIMIT :limit";
            $stmt = $db->prepare($query);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function confirmPayment($sale_id) {
        $db = static::getDB();
        $db->beginTransaction();
        try {
            $stmt = $db->prepare("UPDATE sales SET payment_status = 'completed' WHERE id = :sale_id");
            $stmt->execute([':sale_id' => $sale_id]);

            $stmt = $db->prepare("SELECT * FROM sale_items WHERE sale_id = :sale_id");
            $stmt->execute([':sale_id' => $sale_id]);
            $sale_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($sale_items as $item) {
                $update = $db->prepare("UPDATE products SET stock = stock - :quantity WHERE id = :product_id");
                $update->execute([':quantity' => $item['quantity'], ':product_id' => $item['product_id']]);
            }
            $db->commit();
            return true;
        } catch (\Exception $e) {
            $db->rollBack();
            return false;
        }
    }

    public static function updateDeliveryStatus($sale_id, $status) {
        $db = static::getDB();
        $stmt = $db->prepare("UPDATE sales SET delivery_status = :status WHERE id = :sale_id");
        return $stmt->execute([':status' => $status, ':sale_id' => $sale_id]);
    }
}
