<?php
// public/includes/functions.php

/**
 * @var PDO $pdo   // init.php içinde oluşturulmuş global PDO örneği
 */
function getCategories(): array {
    global $pdo;
    $stmt = $pdo->query("SELECT id, name FROM categories ORDER BY name");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getFeaturedProducts(int $limit = 8): array {
    global $pdo;
    $sql = "
      SELECT 
        p.id,
        p.title,
        p.price,
        (SELECT image_path 
           FROM product_images 
           WHERE product_id = p.id 
           ORDER BY id DESC
           LIMIT 1
        ) AS thumb
      FROM products p
      WHERE p.stock > 0
      ORDER BY p.created_at DESC
      LIMIT :lim
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getProductsByCategory(int $catId, int $limit = 8): array {
    global $pdo;
    $sql = "
      SELECT 
        p.id,
        p.title,
        p.price,
        (SELECT image_path 
           FROM product_images 
           WHERE product_id = p.id 
           ORDER BY id DESC
           LIMIT 1
        ) AS thumb
      FROM products p
      WHERE p.stock > 0
        AND p.category_id = :cat
      ORDER BY p.created_at DESC
      LIMIT :lim
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':cat', $catId, PDO::PARAM_INT);
    $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getAllProducts(int $limit = null): array {
    global $pdo;
    $sql = "
      SELECT 
        p.id,
        p.title,
        p.price,
        (SELECT image_path 
           FROM product_images 
           WHERE product_id = p.id 
           ORDER BY id DESC
           LIMIT 1
        ) AS thumb
      FROM products p
      WHERE p.stock > 0
      ORDER BY p.created_at DESC
    ";
    if ($limit) {
      $sql .= " LIMIT :lim";
      $stmt = $pdo->prepare($sql);
      $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
      $stmt->execute();
    } else {
      $stmt = $pdo->query($sql);
    }
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
