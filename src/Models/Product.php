<?php
namespace App\Models;

use App\Database\Connection;
use PDO;

class Product
{
  private PDO $db;

  public function __construct()
  {
    $this->db = Connection::getInstance();
  }

  public function all(): array
  {
    $stmt = $this->db->query("SELECT * FROM products order by id");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  public function findById(int $id): ?array
  {
    $stmt = $this->db->prepare("select * from products where id = :id");
    $stmt->execute(['id' => $id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    return $product ?: null;
  }

  // Untuk operasi yang membutuhkan reservasi stok, metode ini untuk mengunci baris produk
  public function findByIdWithRowLock(int $id): ?array
  {
    $stmt = $this->db->prepare("select * from products where id = :id FOR UPDATE");
    $stmt->execute(['id' => $id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    return $product ?: null;
  }

  public function decrementStock(int $id, int $qty): bool
  {
    $stmt = $this->db->prepare("update products set stock = stock - :qty where id = :id and stock >= :qty");
    $stmt->execute(['id' => $id, 'qty' => $qty]);
    return $stmt->rowCount() > 0;
  }

  public function getPrice(int $id): ?float
  {
    $stmt = $this->db->prepare("select price, on_sale, sale_price from products where id = :id");
    $stmt->execute(['id' => $id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$product) {
      return null;
    }
    return $product['on_sale'] ? (float) $product['sale_price'] : (float) $product['price'];
  }
}