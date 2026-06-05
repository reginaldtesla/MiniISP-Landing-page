<?php

declare(strict_types=1);

require_once dirname(__DIR__).'/lib/bootstrap.php';

$reference = trim((string) ($_GET['ref'] ?? $_GET['reference'] ?? ''));
if ($reference === '') {
    http_response_code(400);
    echo 'Missing payment reference.';
    exit;
}

$appUrl = rtrim((string) hp_setting('app_url', ''), '/');
hp_redirect($appUrl.'/success.php?ref='.urlencode($reference));
