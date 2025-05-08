<?php
// admin/import_xml.php
require_once __DIR__.'/includes/header.php';
require_once __DIR__.'/includes/sidebar.php';
require_once __DIR__.'/../config/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_FILES['xml_file']['tmp_name'])) {
    libxml_use_internal_errors(true);
    $xml = simplexml_load_file($_FILES['xml_file']['tmp_name']);

    if (!$xml) {
        $_SESSION['error'] = "Geçersiz XML dosyası.";
    } else {
        $imported = $failed = 0;

        foreach ($xml->product as $node) {
            // örnek XML yapısı:
            // <products>
            //   <product>
            //     <title>Ürün Adı</title>
            //     <description>Açıklama</description>
            //     <price>49.99</price>
            //     <stock>100</stock>
            //     <category>Elektronik</category>
            //     <images>
            //       <image>https://site.com/img1.jpg</image>
            //       <image>https://site.com/img2.jpg</image>
            //     </images>
            //   </product>
            //   ...
            // </products>

            $title       = trim((string)$node->title);
            $desc        = trim((string)$node->description);
            $price       = (float) $node->price;
            $stock       = (int)   $node->stock;
            $catName     = trim((string)$node->category);

            // kategori ID’sini bul / yoksa oluştur
            $catStmt = $pdo->prepare("SELECT id FROM categories WHERE name = ?");
            $catStmt->execute([$catName]);
            $cat = $catStmt->fetch();
            if (!$cat) {
                $pdo->prepare("INSERT INTO categories (name, created_at) VALUES (?, NOW())")
                    ->execute([$catName]);
                $category_id = $pdo->lastInsertId();
            } else {
                $category_id = $cat['id'];
            }

            try {
                // ürünü ekle
                $pdo->beginTransaction();
                $ins = $pdo->prepare("
                    INSERT INTO products 
                      (title, description, price, stock, category_id, created_at)
                    VALUES (?, ?, ?, ?, ?, NOW())
                ");
                $ins->execute([$title, $desc, $price, $stock, $category_id]);
                $prodId = $pdo->lastInsertId();

                // varsa resimleri de ekle (uzaktan indir veya URL olarak sakla)
                if (isset($node->images->image)) {
                    foreach ($node->images->image as $imgUrl) {
                        // isterseniz URL’den indirip /public/productimg/ altına kaydedin
                        // burada biz sadece orijinal URL’i saklıyoruz
                        $insImg = $pdo->prepare("
                            INSERT INTO product_images (product_id, image_path, created_at)
                            VALUES (?, ?, NOW())
                        ");
                        $insImg->execute([$prodId, trim((string)$imgUrl)]);
                    }
                }

                $pdo->commit();
                $imported++;
            } catch (\Exception $e) {
                $pdo->rollBack();
                $failed++;
            }
        }

        $_SESSION['success'] = "Toplam {$imported} ürün yüklendi. {$failed} işlem başarısız.";
    }
}

// import tamamlandıktan sonra tekrar ürünler sayfasına dön
header('Location: products.php');
exit;
