<?php
class Product {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function getAll($categorySlug = null) {
        $query = "SELECT p.*, c.name as category_name, c.slug as category_slug 
                  FROM products p 
                  LEFT JOIN categories c ON p.category_id = c.id ";
        
        $params = [];
        if ($categorySlug) {
            $query .= " WHERE c.slug = :slug ";
            $params[':slug'] = $categorySlug;
        }
        
        $query .= " ORDER BY p.created_at DESC";
        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById($id) {
        $query = "SELECT p.*, c.name as category_name 
                  FROM products p 
                  LEFT JOIN categories c ON p.category_id = c.id 
                  WHERE p.id = :id LIMIT 1";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
