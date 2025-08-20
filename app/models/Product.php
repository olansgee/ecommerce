<?php

namespace App\Models;

use PDO;
use App\Core\Model;

class Product extends Model {
    public static function getAll() {
        $db = static::getDB();
        $stmt = $db->query('SELECT * FROM products ORDER BY name');
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function getById($id) {
        $db = static::getDB();
        $stmt = $db->prepare('SELECT * FROM products WHERE id = :id');
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public static function create($data) {
        $db = static::getDB();
        $sql = 'INSERT INTO products (name, description, price, stock, category, image) VALUES (:name, :description, :price, :stock, :category, :image)';
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':name', $data['name'], PDO::PARAM_STR);
        $stmt->bindValue(':description', $data['description'], PDO::PARAM_STR);
        $stmt->bindValue(':price', $data['price'], PDO::PARAM_STR);
        $stmt->bindValue(':stock', $data['stock'], PDO::PARAM_INT);
        $stmt->bindValue(':category', $data['category'], PDO::PARAM_STR);
        $stmt->bindValue(':image', $data['image'], PDO::PARAM_STR);
        return $stmt->execute();
    }

    public static function update($data) {
        $db = static::getDB();
        $sql = 'UPDATE products SET name = :name, description = :description, price = :price, stock = :stock, category = :category, image = :image WHERE id = :id';
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':id', $data['id'], PDO::PARAM_INT);
        $stmt->bindValue(':name', $data['name'], PDO::PARAM_STR);
        $stmt->bindValue(':description', $data['description'], PDO::PARAM_STR);
        $stmt->bindValue(':price', $data['price'], PDO::PARAM_STR);
        $stmt->bindValue(':stock', $data['stock'], PDO::PARAM_INT);
        $stmt->bindValue(':category', $data['category'], PDO::PARAM_STR);
        $stmt->bindValue(':image', $data['image'], PDO::PARAM_STR);
        return $stmt->execute();
    }

    public static function delete($id) {
        $db = static::getDB();
        $sql = 'DELETE FROM products WHERE id = :id';
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public static function updateStock($id, $quantity) {
        $db = static::getDB();
        $sql = 'UPDATE products SET stock = stock + :quantity WHERE id = :id';
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':quantity', $quantity, PDO::PARAM_INT);
        return $stmt->execute();
    }
}
