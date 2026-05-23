FLASH SALE API
1. Copy file .env.example menjadi .env dan isi ENV sesuai dengan informasi database yang telah dibuat
2. Database akan otomatis di migrate saat pertama kali menjalankan API
3. Jalankan php -S 0.0.0.0:8000 -t public untuk memulai service API
4. Jalankan php tests/RaceConditionTest.php untuk memulai testing

List API:
1. GET /products - Menampilkan semua product
2. GET /orders - Menampilkan semua pesanan
3. POST /order - untuk membuat pesanan
  {
    "customer_name": "Customer",
    "items": [
      {
        "product_id": 1,
        "quantity": 1
      }
    ]
  }