<?php

namespace App\Database;

use PDO;
use App\Database\Connection;
use PDOException;

function getConnection(): PDO
{
  return Connection::getInstance();
}

function migrate()
{
  $pdo = getConnection();

  try {
    // product table
    $pdo->exec("
            CREATE TABLE IF NOT EXISTS products (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                description TEXT,
                price DECIMAL(10, 2) NOT NULL,
                stock INT NOT NULL,
                on_sale BOOLEAN NOT NULL DEFAULT FALSE,
                sale_price DECIMAL(10, 2) DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB;
        ");

    // orders table
    $pdo->exec("
            CREATE TABLE IF NOT EXISTS orders (
                id INT AUTO_INCREMENT PRIMARY KEY,
                customer_name VARCHAR(255) NOT NULL,
                status ENUM('pending', 'completed', 'cancelled') NOT NULL DEFAULT 'pending',
                total DECIMAL(10, 2) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB;
        ");

    // order_items table
    $pdo->exec("
            CREATE TABLE IF NOT EXISTS order_items (
                id INT AUTO_INCREMENT PRIMARY KEY,
                order_id INT NOT NULL,
                product_id INT NOT NULL,
                quantity INT NOT NULL,
                price DECIMAL(10, 2) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
                FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
            ) ENGINE=InnoDB;
        ");

    echo "Migration completed successfully.\n";
  } catch (PDOException $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
  }
}

function seed(): void
{
  $db = getConnection();

  $db->exec("DELETE FROM order_items");
  $db->exec("DELETE FROM orders");
  $db->exec("DELETE FROM products");

  $stmt = $db->prepare("
    INSERT INTO products (name, description, price, stock, on_sale, sale_price)
    VALUES (:name, :desc, :price, :stock, :on_sale, :sale_price)
  ");

  $products = [
    [
      'name'       => 'Flash Sale Laptop',
      'desc'       => 'High-performance laptop — limited flash sale stock!',
      'price'      => 15000000,
      'stock'      => 10,
      'on_sale'    => 1,
      'sale_price' => 7500000,
    ],
    [
      'name'       => 'Wireless Headphones',
      'desc'       => 'Premium noise-cancelling headphones',
      'price'      => 2500000,
      'stock'      => 50,
      'on_sale'    => 0,
      'sale_price' => null,
    ],
    [
      'name'       => 'Mechanical Keyboard',
      'desc'       => 'RGB mechanical keyboard with tactile switches',
      'price'      => 1200000,
      'stock'      => 30,
      'on_sale'    => 0,
      'sale_price' => null,
    ],
  ];

  foreach ($products as $p) {
    $stmt->execute([
      ':name'       => $p['name'],
      ':desc'       => $p['desc'],
      ':price'      => $p['price'],
      ':stock'      => $p['stock'],
      ':on_sale'    => $p['on_sale'],
      ':sale_price' => $p['sale_price'],
    ]);
  }

  echo "Seeded " . count($products) . " products\n";
  echo "Flash sale product (ID 1): stock=10, sale_price=7,500,000\n";
}

migrate();
seed();
