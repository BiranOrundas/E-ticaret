<?php
// import_csv_to_xml.php

// 1) CSV’yi aç
$csv = fopen('products.csv','r');
if (!$csv) {
    die("products.csv bulunamadı\n");
}

// 2) Başlık satırını oku
$headers = fgetcsv($csv);

// 3) Yeni XML kökü
$xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><products></products>');

// 4) Her CSV satırı için <product> oluştur
while($row = fgetcsv($csv)) {
    // Satır verilerini başlıklarla eşleştir
    $data = array_combine($headers, $row);

    $p = $xml->addChild('product');
    $p->addChild('title',       htmlspecialchars($data['title']));
    $p->addChild('description', htmlspecialchars($data['description']));
    $p->addChild('price',       $data['price']);
    $p->addChild('stock',       $data['stock']);
    $p->addChild('category',    htmlspecialchars($data['category']));

    // images altına image1,image2… sütunlarını ekle
    $imgs = $p->addChild('images');
    for ($i = 1; isset($data["image$i"]); $i++) {
        if (!empty($data["image$i"])) {
            $imgs->addChild('image', $data["image$i"]);
        }
    }
}

// 5) XML’i products.xml olarak kaydet
$xml->asXML('products.xml');

echo "products.xml başarıyla oluşturuldu.\n";
