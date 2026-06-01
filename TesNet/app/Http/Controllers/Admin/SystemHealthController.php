<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\SystemHealthService;
use Illuminate\View\View;

class SystemHealthController extends Controller
{
    public function index(SystemHealthService $health): View
    {
        return view('admin.system-health.index', [
            'checks' => $health->checks(),
            'walledGardenNotes' => $this->walledGardenNotes(),
        ]);
    }

    /**
     * @return list<array{title: string, items: list<string>}>
     */
    protected function walledGardenNotes(): array
    {
        $appHost = parse_url(config('app.url'), PHP_URL_HOST) ?: 'YOUR_SERVER_IP';

        return [
            [
                'title' => 'Portal (required)',
                'items' => [
                    "Allow HTTP/HTTPS to your Laravel server: {$appHost}",
                    'Walled garden must include the exact host students use in the browser (IP or domain).',
                ],
            ],
            [
                'title' => 'Paystack (payments only — not full internet)',
                'items' => [
                    'api.paystack.co',
                    'checkout.paystack.com',
                    'standard.paystack.com',
                    'js.paystack.co',
                    '(Add any host shown in browser DevTools → Network when checkout fails.)',
                ],
            ],
            [
                'title' => 'Verify anti-leak',
                'items' => [
                    'Before login: only portal + Paystack hosts reachable.',
                    'After login, before purchase: still no general browsing.',
                    'After purchase + Connect: browsing works within plan limits.',
                ],
            ],
        ];
    }
}
