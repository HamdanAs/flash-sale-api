<?php
namespace App\Models;

use App\Database\Connection;
use PDO;
use PDOException;

class Order {
  private PDO $db;

  public function __construct()
  {
    $this->db = Connection::getInstance();
  }

  public function all(): array
  {
    $stmt = $this->db->query("SELECT * FROM orders order by id");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  public function findById(int $id): ?array
  {
    $stmt = $this->db->prepare("select * from orders where id = :id");
    $stmt->execute(['id' => $id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    return $order ?: null;
  }

  public function getItems(int $orderId): array
  {
    $stmt = $this->db->prepare("
      SELECT oi.*, p.name 
      FROM order_items oi 
      JOIN products p ON oi.product_id = p.id 
      WHERE oi.order_id = :orderId
    ");
    $stmt->execute(['orderId' => $orderId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  /**
   * Membuat pesanan dengan reservasi stok produk secara atomik
   * Berikut adalah langkah langkah untuk menangani race condition saat membuat pesanan:
   * 1. Mulai transaksi database
   * 2. Untuk setiap item dalam pesanan, kurangi stok produk menggunakan query stok >= qty (Gagal jika stok tidak mencukupi)
   * 3. Insert data pesanan ke tabel orders
   * 4. Commit transaksi jika semua langkah berhasil, atau rollback jika terjadi error
   * 
   * @param string $customerName
   * @param array $items Array of items, setiap item harus mempunyai 'product_id'
   * @return array ['success' => bool, 'message' => string, 'order' => array|null]
   */
  public function createWithStockReservation(string $customerName, array $items): array
  {
    $productModel = new Product();
    
    $resolvedItems = [];
    foreach ($items as $item) {
      $product = $productModel->findByIdWithRowLock($item['product_id']);
      if (!$product) {
        return ['success' => false, 'message' => "Product ID {$item['product_id']} not found", 'order' => null];
      }

      $price = $productModel->getPrice($item['product_id']);

      $resolvedItems[] = [
        'product' => $product,
        'product_id' => $item['product_id'],
        'quantity' => $item['quantity'],
        'price' => $price,
      ];
    }

    // Urutkan berdasarkan product_id untuk menghindari deadlock saat terjadi banyak transaksi bersamaan
    usort($resolvedItems, fn($a, $b) => $a['product_id'] <=> $b['product_id']);

    try {
      $this->db->beginTransaction();

      $total = 0;
      $stockResults = [];

      foreach ($resolvedItems as $item) {
        $success = $productModel->decrementStock($item['product_id'], $item['quantity']);
        if (!$success) {
          $this->db->rollBack();
          return ['success' => false, 'message' => "Insufficient stock for product ID {$item['product_id']}", 'order' => null];
        }
        $total += $item['price'] * $item['quantity'];
        $stockResults[] = $item;
      }

      $stmt = $this->db->prepare("INSERT INTO orders (customer_name, status, total) VALUES (:customer_name, :status, :total)");
      $stmt->execute(['customer_name' => $customerName, 'status' => 'completed', 'total' => $total]);
      $orderId = (int) $this->db->lastInsertId();

      foreach ($stockResults as $item) {
        $stmt = $this->db->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (:order_id, :product_id, :quantity, :price)");
        $stmt->execute([
          'order_id' => $orderId,
          'product_id' => $item['product_id'],
          'quantity' => $item['quantity'],
          'price' => $item['price'],
        ]);
      }

      $this->db->commit();
      return ['success' => true, 'message' => "Order created successfully", 'order' => $this->findById($orderId)];
    } catch (PDOException $e) {
      if ($this->db->inTransaction()) {
        $this->db->rollBack();
      }
      
      return ['success' => false, 'message' => "Failed to create order: " . $e->getMessage(), 'order' => null];
    }
  }
}