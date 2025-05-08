<?php
// admin/import_products.php

require_once __DIR__.'/includes/header.php';
require_once __DIR__.'/includes/sidebar.php';
require_once __DIR__.'/../config/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_FILES['upload_file']['tmp_name'])) {
    $tmpFile  = $_FILES['upload_file']['tmp_name'];
    $origName = $_FILES['upload_file']['name'];
    $ext      = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
    $products = [];

    if ($ext === 'xml') {
        // XML yükle ve hata kontrolü
        libxml_use_internal_errors(true);
        $xml = simplexml_load_file($tmpFile);
        if (!$xml) {
            $_SESSION['error'] = 'Geçersiz XML dosyası.';
            header('Location: products.php');
            exit;
        }

        // İki farklı XML yapısını destekle
        if (isset($xml->product)) {
            // <products><product>… formatı
            foreach ($xml->product as $node) {
                $products[] = [
                    'title'       => (string) ($node->title       ?? ''),
                    'description' => (string) ($node->description ?? ''),
                    'price'       => (float)  ($node->price       ?? 0),
                    'stock'       => (int)    ($node->stock       ?? 0),
                    'category'    => (string) ($node->category    ?? ''),
                    'images'      => array_map('strval', iterator_to_array($node->images->image ?? []))
                ];
            }
        } elseif (isset($xml->CD)) {
            // CD kataloğu formatı <CATALOG><CD>…</CD></CATALOG>
            foreach ($xml->CD as $cd) {
                $products[] = [
                    'title'       => (string) ($cd->TITLE   ?? ''),
                    'description' => trim("Sanatçı: {$cd->ARTIST}; Firma: {$cd->COMPANY}; Yıl: {$cd->YEAR}"),
                    'price'       => (float)  ($cd->PRICE ?? 0),
                    'stock'       => 10,  // varsayılan stok
                    'category'    => (string) ($cd->COUNTRY),
                    'images'      => []    // CD’lerde resim yok; istersen sabit ekle
                ];
            }
        } else {
            $_SESSION['error'] = 'XML içinde <product> veya <CD> düğümleri bulunamadı.';
            header('Location: products.php');
            exit;
        }

    } elseif ($ext === 'csv') {
        // CSV parse
        if (($fp = fopen($tmpFile, 'r')) === false) {
            $_SESSION['error'] = 'CSV dosyası açılamadı.';
            header('Location: products.php');
            exit;
        }
        $headers = fgetcsv($fp);
        while ($row = fgetcsv($fp)) {
            $data = array_combine($headers, $row);
            // images1, images2, ... dizisi oluştur
            $imgs = [];
            for ($i = 1; isset($data["image$i"]); $i++) {
                if (!empty($data["image$i"])) {
                    $imgs[] = $data["image$i"];
                }
            }
            $products[] = [
                'title'       => $data['title']       ?? '',
                'description' => $data['description'] ?? '',
                'price'       => (float) ($data['price']  ?? 0),
                'stock'       => (int)   ($data['stock']  ?? 0),
                'category'    => $data['category']    ?? '',
                'images'      => $imgs
            ];
        }
        fclose($fp);

    } else {
        $_SESSION['error'] = 'Sadece XML veya CSV formatı desteklenir.';
        header('Location: products.php');
        exit;
    }

    // --- Ürünleri veritabanına ekleme ---
    $imported = $failed = 0;
    foreach ($products as $prd) {
        try {
            $pdo->beginTransaction();

            // 1) Kategori (varsa al, yoksa oluştur)
            $cStmt = $pdo->prepare("SELECT id FROM categories WHERE name = ?");
            $cStmt->execute([$prd['category']]);
            $catId = $cStmt->fetchColumn();
            if (!$catId) {
                $insCat = $pdo->prepare("INSERT INTO categories (name, created_at) VALUES (?, NOW())");
                $insCat->execute([$prd['category']]);
                $catId = $pdo->lastInsertId();
            }

            // 2) Ürün kaydı
            $insProd = $pdo->prepare("
              INSERT INTO products 
                (title, description, price, stock, category_id, created_at)
              VALUES (?, ?, ?, ?, ?, NOW())
            ");
            $insProd->execute([
                $prd['title'],
                $prd['description'],
                $prd['price'],
                $prd['stock'],
                $catId
            ]);
            $newProdId = $pdo->lastInsertId();

            // 3) Resimler
            $insImg = $pdo->prepare("
              INSERT INTO product_images 
                (product_id, image_path, created_at)
              VALUES (?, ?, NOW())
            ");
            foreach ($prd['images'] as $url) {
                $insImg->execute([$newProdId, $url]);
            }

            $pdo->commit();
            $imported++;
        } catch (\Exception $e) {
            $pdo->rollBack();
            $failed++;
        }
    }

    $_SESSION['success'] = "İçe Aktarılan: {$imported}, Başarısız: {$failed}";
    header('Location: products.php');
    exit;
}

// Eğer doğrudan import_products.php’ye gelinmişse
header('Location: products.php');
exit;
