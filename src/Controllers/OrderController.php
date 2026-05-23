<?php
namespace App\Controllers;

use App\Middleware\Json;
use App\Models\Order;

class OrderController
{
  private Order $orderModel;

  public function __construct()
  {
    $this->orderModel = new Order();
  }

  public function index(): never
  {
    $results = $this->orderModel->all();
    Json::respond($results);
  }

  public function show(int $id): never
  {
    $result = $this->orderModel->findById($id);
    Json::respond($result);
  }

  public function store(): never
  {
    $data = Json::parseBody();
    Json::requiredFields($data, ['customer_name', 'items']);

    $customer = trim((string) $data['customer_name']);
    if (strlen($customer) < 2) {
      Json::error('Customer name must be at least 2 characters long', 422);
    }

    $items = $data['items'];
    if (!is_array($items) || empty($items)) {
      Json::error('Items must be a non-empty array', 422);
    }

    foreach ($items as $index => $item) {
      if (!isset($item['product_id']) || !is_int($item['product_id'])) {
        Json::error("Item at index $index is missing a valid 'product_id'", 422);
      }
      if (!isset($item['quantity']) || !is_int($item['quantity']) || $item['quantity'] <= 0) {
        Json::error("Item at index $index has an invalid 'quantity'", 422);
      }
    }

    $result = $this->orderModel->createWithStockReservation($customer, $items);
    if (!$result['success']) {
      // 409 Konflik jika masalahnya adalah stok tidak mencukupi, 400 untuk error lainnya
      $statusCode = str_contains($result['message'] ?? '', 'Insufficient stock') ? 409 : 500;
      Json::error($result['message'], $statusCode);
    }

    Json::respond([
      'message' => 'Order created successfully',
      'order' => $result['order']
    ], 201);
  }
}