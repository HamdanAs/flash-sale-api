<?php
namespace App\Controllers;

use App\Models\Product;
use App\Middleware\Json;

class ProductController
{
  private Product $productModel;

  public function __construct()
  {
    $this->productModel = new Product();
  }

  public function index(): never
  {
    $results = $this->productModel->all();
    Json::respond($results);
  }

  public function show(int $id): never
  {
    $result = $this->productModel->findById($id);
    Json::respond($result);
  }
}