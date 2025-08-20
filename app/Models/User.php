<?php

namespace App\Models;

use PDO;
use App\Core\Model;

class User extends Model {
    public static function findByEmailOrUsername($identifier) {
        $db = static::getDB();
        $stmt = $db->prepare('SELECT * FROM users WHERE username = :identifier OR email = :identifier');
        $stmt->execute([':identifier' => $identifier]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public static function findById($id) {
        $db = static::getDB();
        $stmt = $db->prepare('SELECT * FROM users WHERE id = :id');
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public static function create($data) {
        $db = static::getDB();
        $sql = 'INSERT INTO users (username, email, password, role, gender, age, address, phone, verification_token, token_expiry)
                VALUES (:username, :email, :password, :role, :gender, :age, :address, :phone, :verification_token, :token_expiry)';
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':username', $data['username'], PDO::PARAM_STR);
        $stmt->bindValue(':email', $data['email'], PDO::PARAM_STR);
        $stmt->bindValue(':password', $data['password'], PDO::PARAM_STR);
        $stmt->bindValue(':role', $data['role'], PDO::PARAM_STR);
        $stmt->bindValue(':gender', $data['gender'], PDO::PARAM_STR);
        $stmt->bindValue(':age', $data['age'], PDO::PARAM_INT);
        $stmt->bindValue(':address', $data['address'], PDO::PARAM_STR);
        $stmt->bindValue(':phone', $data['phone'], PDO::PARAM_STR);
        $stmt->bindValue(':verification_token', $data['verification_token'], PDO::PARAM_STR);
        $stmt->bindValue(':token_expiry', $data['token_expiry'], PDO::PARAM_STR);

        if ($stmt->execute()) {
            return $db->lastInsertId();
        }
        return false;
    }

    public static function verifyEmail($token) {
        $db = static::getDB();
        $stmt = $db->prepare("SELECT id FROM users WHERE verification_token = :token AND token_expiry > NOW()");
        $stmt->execute([':token' => $token]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            $update_stmt = $db->prepare("UPDATE users SET email_verified = 1, verification_token = NULL, token_expiry = NULL WHERE id = :id");
            return $update_stmt->execute([':id' => $user['id']]);
        }
        return false;
    }

    public static function createCashier($data) {
        $db = static::getDB();
        $sql = "INSERT INTO users (username, email, password, role, email_verified) VALUES (:username, :email, :password, 'cashier', 1)";
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':username', $data['username'], PDO::PARAM_STR);
        $stmt->bindValue(':email', $data['email'], PDO::PARAM_STR);
        $stmt->bindValue(':password', $data['password'], PDO::PARAM_STR);
        return $stmt->execute();
    }

    public static function getAll() {
        $db = static::getDB();
        $stmt = $db->query("SELECT id, username, email, role, created_at, email_verified FROM users");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
