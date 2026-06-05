<?php

declare(strict_types=1);

require_once dirname(__DIR__).'/lib/bootstrap.php';
require_once dirname(__DIR__).'/lib/paystack.php';

$raw = file_get_contents('php://input') ?: '';
$signature = (string) ($_SERVER['HTTP_X_PAYSTACK_SIGNATURE'] ?? '');

if (! hp_paystack_verify_signature($raw, $signature)) {
    http_response_code(401);
    echo 'Invalid signature';
    exit;
}

$payload = json_decode($raw, true);
if (! is_array($payload)) {
    http_response_code(400);
    echo 'Invalid payload';
    exit;
}

$event = (string) ($payload['event'] ?? '');
if ($event !== 'charge.success') {
    http_response_code(200);
    echo 'Ignored';
    exit;
}

$data = $payload['data'] ?? [];
if (! is_array($data)) {
    http_response_code(400);
    echo 'Invalid data';
    exit;
}

$reference = (string) ($data['reference'] ?? '');
if ($reference === '') {
    http_response_code(400);
    echo 'Missing reference';
    exit;
}

$customer = is_array($data['customer'] ?? null) ? $data['customer'] : [];
$buyerEmail = isset($customer['email']) ? (string) $customer['email'] : null;
$buyerPhone = isset($customer['phone']) ? (string) $customer['phone'] : null;

$db = hp_db();

try {
    hp_fulfill_payment($db, $reference, $buyerEmail, $buyerPhone);
} catch (Throwable $e) {
    http_response_code(500);
    echo 'Fulfillment error';
    exit;
}

http_response_code(200);
echo 'OK';
