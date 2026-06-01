<?php

namespace App\Services;

use App\Models\DataPackage;
use App\Models\Transaction;
use App\Models\User;
use App\Support\CustomDataQuote;
use App\Support\PaystackCustomerEmail;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class PaystackService
{
    public function initializePackagePayment(User $user, DataPackage $package): Transaction
    {
        $reference = 'TSN-'.strtoupper(Str::random(12));
        $pesewas = $package->amountPesewas();

        $transaction = Transaction::query()->create([
            'user_id' => $user->id,
            'type' => 'package',
            'package_slug' => $package->slug,
            'amount' => $package->price,
            'currency' => 'GHS',
            'amount_pesewas' => $pesewas,
            'paystack_reference' => $reference,
            'status' => 'pending',
            'metadata' => [
                'package_slug' => $package->slug,
                'package_name' => $package->name,
                'phone_number' => $user->phone_number,
            ],
        ]);

        $payload = [
            'email' => PaystackCustomerEmail::forUser($user),
            'amount' => $pesewas,
            'currency' => 'GHS',
            'reference' => $reference,
            'callback_url' => route(config('paystack.callback_route')),
            'channels' => ['mobile_money', 'card'],
            'metadata' => [
                'transaction_id' => $transaction->id,
                'user_id' => $user->id,
                'package_slug' => $package->slug,
                'custom_fields' => [
                    [
                        'display_name' => 'Phone',
                        'variable_name' => 'phone_number',
                        'value' => $user->phone_number,
                    ],
                    [
                        'display_name' => 'Package',
                        'variable_name' => 'package_name',
                        'value' => $package->name,
                    ],
                ],
            ],
        ];

        $response = $this->request('post', '/transaction/initialize', $payload);

        if (! ($response['status'] ?? false)) {
            $transaction->update(['status' => 'failed', 'paystack_response' => $response]);
            throw new \RuntimeException($response['message'] ?? 'Paystack initialization failed.');
        }

        $data = $response['data'];

        $transaction->update([
            'paystack_access_code' => $data['access_code'] ?? null,
            'paystack_response' => $response,
        ]);

        return $transaction->fresh();
    }

    public function initializeCustomDataPayment(User $user, CustomDataQuote $quote): Transaction
    {
        $reference = 'TSN-C-'.strtoupper(Str::random(10));
        $pesewas = (int) round($quote->amountGhs * 100);

        if ($pesewas < 100) {
            throw new \InvalidArgumentException('Minimum payment is GH¢1.00.');
        }

        $quoteMeta = $quote->toMetadata();

        $transaction = Transaction::query()->create([
            'user_id' => $user->id,
            'type' => 'custom_data',
            'package_slug' => 'custom',
            'amount' => $quote->amountGhs,
            'currency' => 'GHS',
            'amount_pesewas' => $pesewas,
            'paystack_reference' => $reference,
            'status' => 'pending',
            'metadata' => array_merge($quoteMeta, [
                'phone_number' => $user->phone_number,
            ]),
        ]);

        $payload = [
            'email' => PaystackCustomerEmail::forUser($user),
            'amount' => $pesewas,
            'currency' => 'GHS',
            'reference' => $reference,
            'callback_url' => route(config('paystack.callback_route')),
            'channels' => ['mobile_money', 'card'],
            'metadata' => [
                'transaction_id' => $transaction->id,
                'user_id' => $user->id,
                'type' => 'custom_data',
                'data_gb' => $quote->dataGb,
                'custom_fields' => [
                    [
                        'display_name' => 'Phone',
                        'variable_name' => 'phone_number',
                        'value' => $user->phone_number,
                    ],
                    [
                        'display_name' => 'Custom data',
                        'variable_name' => 'data_label',
                        'value' => $quote->dataLabel(),
                    ],
                ],
            ],
        ];

        $response = $this->request('post', '/transaction/initialize', $payload);

        if (! ($response['status'] ?? false)) {
            $transaction->update(['status' => 'failed', 'paystack_response' => $response]);
            throw new \RuntimeException($response['message'] ?? 'Paystack initialization failed.');
        }

        $data = $response['data'];

        $transaction->update([
            'paystack_access_code' => $data['access_code'] ?? null,
            'paystack_response' => $response,
        ]);

        return $transaction->fresh();
    }

    /**
     * @return array<string, mixed>
     */
    public function verify(string $reference): array
    {
        return $this->request('get', '/transaction/verify/'.$reference);
    }

    public function verifyWebhookSignature(string $payload, ?string $signature): bool
    {
        $secret = config('paystack.secret_key');

        if (! $secret || ! $signature) {
            return false;
        }

        return hash_equals(hash_hmac('sha512', $payload, $secret), $signature);
    }

    /**
     * @return array{ok: bool, configured: bool, message: string}
     */
    public function testConnection(): array
    {
        $public = config('paystack.public_key');
        $secret = config('paystack.secret_key');

        if (! $public || ! $secret) {
            return [
                'ok' => false,
                'configured' => false,
                'message' => 'Missing PAYSTACK keys — use manual payments or set PAYSTACK_PUBLIC_KEY and PAYSTACK_SECRET_KEY.',
            ];
        }

        try {
            $url = rtrim((string) config('paystack.base_url'), '/').'/balance';

            $response = Http::withToken($secret)
                ->acceptJson()
                ->timeout(10)
                ->get($url);

            if ($response->status() === 401) {
                return [
                    'ok' => false,
                    'configured' => true,
                    'message' => 'Paystack rejected the secret key (401 Unauthorized).',
                ];
            }

            $json = $response->json() ?? [];

            if (! ($json['status'] ?? false)) {
                return [
                    'ok' => false,
                    'configured' => true,
                    'message' => (string) ($json['message'] ?? 'Paystack API returned an error.'),
                ];
            }

            $currency = $json['data'][0]['currency'] ?? 'GHS';

            return [
                'ok' => true,
                'configured' => true,
                'message' => 'Paystack API reachable — balance check OK ('.$currency.').',
            ];
        } catch (\Throwable $exception) {
            return [
                'ok' => false,
                'configured' => true,
                'message' => 'Paystack API unreachable: '.$exception->getMessage(),
            ];
        }
    }

    /**
     * @param  array<string, mixed>  $body
     * @return array<string, mixed>
     */
    protected function request(string $method, string $path, array $body = []): array
    {
        $secret = config('paystack.secret_key');

        if (! $secret) {
            throw new \RuntimeException('Paystack secret key is not configured.');
        }

        $url = rtrim(config('paystack.base_url'), '/').$path;

        try {
            $pending = Http::withToken($secret)
                ->acceptJson()
                ->timeout(30);

            $response = match (strtolower($method)) {
                'get' => $pending->get($url),
                'post' => $pending->post($url, $body),
                default => throw new \InvalidArgumentException("Unsupported HTTP method [{$method}]."),
            };

            $response->throw();

            return $response->json() ?? [];
        } catch (RequestException $exception) {
            $json = $exception->response?->json() ?? [];

            throw new \RuntimeException(
                $json['message'] ?? $exception->getMessage(),
                $exception->response?->status() ?? 0,
                $exception
            );
        }
    }
}
