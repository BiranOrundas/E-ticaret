<?php
// admin/import_products.php
require_once __DIR__.'/includes/header.php';
require_once __DIR__.'/includes/sidebar.php';
require_once __DIR__.'/../config/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_FILES['upload_file']['tmp_name'])) {
    $fileTmp  = $_FILES['upload_file']['tmp_name'];
    $fileName = $_FILES['upload_file']['name'];
    $ext      = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

    $imported = $failed = 0;

    if ($ext === 'xml') {
        // --- XML parser (önceden yaptığımız) ---
        libxml_use_internal_errors(true);
        $xml = simplexml_load_file($fileTmp);
        if (!$xml) {
            $_SESSION['error'] = 'Geçersiz XML dosyası.';
            header('Location: products.php'); exit;
        }
        $products = [];
        foreach ($xml->product as $node) {
            $products[] = [
                'title'       => (string)$node->title,
                'description' => (string)$node->description,
                'price'       => (float)$node->price,
                'stock'       => (int)$node->stock,
                'category'    => (string)$node->category,
                'images'      => array_map('strval', iterator_to_array($node->images->image))
            ];
        }

    } elseif ($ext === 'csv') {
        // --- CSV parser ---
        if (($csv = fopen($fileTmp, 'r')) === false) {
            $_SESSION['error'] = 'CSV dosyası açılamadı.';
            header('Location: products.php'); exit;
        }
        $headers = fgetcsv($csv);
        $products = [];
        while ($row = fgetcsv($csv)) {
            $data = array_combine($headers, $row);
            // images1..n sütunlarını topla
            $imgs = [];
            for ($i = 1; isset($data["image$i"]); $i++) {
                if (!empty($data["image$i"])) {
                    $imgs[] = $data["image$i"];
                }
            }
            $products[] = [
                'title'       => $data['title'],
                'description' => $data['description'],
                'price'       => (float)$data['price'],
                'stock'       => (int)$data['stock'],
                'category'    => $data['category'],
                'images'      => $imgs
            ];
        }
        fclose($csv);

    } else {
        $_SESSION['error'] = 'Desteklenmeyen dosya formatı. Sadece XML veya CSV yükleyebilirsiniz.';
        header('Location: products.php'); exit;
    }

    // --- Ortak veri tabanı kaydetme döngüsü ---
    foreach ($products as $prd) {
        try {
            $pdo->beginTransaction();
            // kategori
            $catStmt = $pdo->prepare("SELECT id FROM categories WHERE name = ?");
            $catStmt->execute([$prd['category']]);
            if (!$catStmt->fetchColumn()) {
                $pdo->prepare("INSERT INTO categories (name, created_at) VALUES (?, NOW())")
                    ->execute([$prd['category']]);
                $category_id = $pdo->lastInsertId();
            } else {
                $category_id = $catStmt->fetchColumn();
            }
            // ürün
            $ins = $pdo->prepare("
              INSERT INTO products 
                (title, description, price, stock, category_id, created_at)
              VALUES (?, ?, ?, ?, ?, NOW())
            ");
            $ins->execute([
              $prd['title'],
              $prd['description'],
              $prd['price'],
              $prd['stock'],
              $category_id
            ]);
            $prodId = $pdo->lastInsertId();
            // resimler
            foreach ($prd['images'] as $url) {
                $pdo->prepare("
                  INSERT INTO product_images 
                    (product_id, image_path, created_at)
                  VALUES (?, ?, NOW())
                ")->execute([$prodId, $url]);
            }
            $pdo->commit();
            $imported++;
        } catch (Exception $e) {
            $pdo->rollBack();
            $failed++;
        }
    }

    $_SESSION['success'] = "Başarılı: {$imported} ürün. Başarısız: {$failed} ürün.";
    header('Location: products.php');
    exit;
}
