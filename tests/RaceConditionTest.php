<?php

$url = 'http://localhost:8000/order';

$totalRequests = 100;

$multiHandle = curl_multi_init();

$curlHandles = [];

for ($i = 0; $i < $totalRequests; $i++) {

    $payload = json_encode([
        'customer_name' => "Customer {$i}",
        'items' => [
            [
                'product_id' => 1,
                'quantity' => 1
            ]
        ]
    ]);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_multi_add_handle($multiHandle, $ch);

    $curlHandles[] = $ch;
}

$running = null;

do {

    curl_multi_exec($multiHandle, $running);

} while ($running);

$success = 0;
$failed = 0;

foreach ($curlHandles as $ch) {

    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if ($status === 201) {
        $success++;
        $response = curl_multi_getcontent($ch);
        echo "Request succeeded: {$response}\n";
    } else {
        $failed++;
        $response = curl_multi_getcontent($ch);
        echo "Request failed with status {$status}: {$response}\n";
    }

    curl_multi_remove_handle($multiHandle, $ch);
}

curl_multi_close($multiHandle);

echo "SUCCESS: {$success}\n";
echo "FAILED: {$failed}\n";