-- 1) Test için bir kullanıcı var mı bakalım (örneğin id = 2):
SELECT id, name FROM users LIMIT 1;

-- 2) Test için bir ürün var mı bakalım (örneğin id = 3, fiyat = 100):
SELECT id, title, price FROM products LIMIT 1;

-- 3) Şimdi fake siparişi ekleyelim:
INSERT INTO orders (user_id, created_at, total_amount, status)
VALUES 
  (
    2,                              -- mevcut bir user_id
    NOW(),                          -- bugün
    100.00,                         -- toplam tutar
    'pending'                       -- başlangıç durumu
  );

-- 4) Sipariş kalemlerini ekleyelim (orders.id değeri AUTO_INCREMENT ile eklendi, son id'yi alıyoruz):
SET @last_order_id = LAST_INSERT_ID();

INSERT INTO order_items (order_id, product_id, quantity, unit_price)
VALUES 
  (@last_order_id, 3, 1, 100.00);
