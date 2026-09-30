<?php

namespace App\Services\Payments;

use App\Exceptions\AppException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Thin adapter over FawryPay staging API.
 *
 * NOTE: endpoint paths, field names and signature field order follow the
 * public FawryPay conventions and MUST be confirmed against the merchant
 * integration docs before going live. All Fawry specifics live ONLY here
 * so adjustments never touch PaymentService.
 */
class FawryService
{
    /**
     * @see https://atfawry.fawrystaging.com (merchant docs)
     */
    private const CHARGE_PATH = '/ECommerceWebService/rest/charge';

    private const STATUS_PATH = '/ECommerceWebService/rest/chargeStatus';

    public static function signature(string ...$parts): string
    {
        return hash('sha256', implode('', $parts).((string) config('fawry.security_key')));
    }

    /**
     * @param  array{merchant_ref: string, amount: string, currency: string, customer_name: string, customer_email: string, customer_mobile: string, description: string}  $order
     * @return array{reference_number: string, raw: array}
     */
    public function createChargeCode(array $order): array
    {
        $response = $this->post(self::CHARGE_PATH, [
            'merchantCode' => config('fawry.merchant_code'),
            'merchantRefNum' => $order['merchant_ref'],
            'customerMobile' => $order['customer_mobile'],
            'customerEmail' => $order['customer_email'],
            'customerName' => $order['customer_name'],
            'amount' => $order['amount'],
            'currencyCode' => $order['currency'],
            'description' => $order['description'],
            'paymentMethod' => 'PAYATFAWRY',
            'signature' => self::signature(
                (string) config('fawry.merchant_code'),
                $order['merchant_ref'],
                $order['customer_mobile'],
                $order['customer_email'],
                $order['amount'],
            ),
        ]);

        return [
            'reference_number' => (string) ($response['referenceNumber'] ?? $response['merchantRefNumber'] ?? ''),
            'raw' => $response,
        ];
    }

    /**
     * @param  array{merchant_ref: string, amount: string, currency: string, customer_name: string, customer_email: string, customer_mobile: string, description: string}  $order
     * @return array{payment_url: string, reference_number: string, raw: array}
     */
    public function createChargeCard(array $order): array
    {
        $response = $this->post(self::CHARGE_PATH, [
            'merchantCode' => config('fawry.merchant_code'),
            'merchantRefNum' => $order['merchant_ref'],
            'customerMobile' => $order['customer_mobile'],
            'customerName' => $order['customer_name'],
            'customerEmail' => $order['customer_email'],
            'amount' => $order['amount'],
            'currencyCode' => $order['currency'],
            'description' => $order['description'],
            'paymentMethod' => 'CARD',
            'signature' => self::signature(
                (string) config('fawry.merchant_code'),
                $order['merchant_ref'],
                $order['amount'],
            ),
        ]);

        return [
            'payment_url' => (string) ($response['paymentUrl'] ?? ''),
            'reference_number' => (string) ($response['referenceNumber'] ?? $response['merchantRefNumber'] ?? ''),
            'raw' => $response,
        ];
    }

    /**
     * @return array{status: string, raw: array} status: PAID|PENDING|FAILED|EXPIRED
     */
    public function getChargeStatus(string $merchantRef): array
    {
        $response = $this->post(self::STATUS_PATH, [
            'merchantCode' => config('fawry.merchant_code'),
            'merchantRefNumber' => $merchantRef,
            'signature' => self::signature(
                (string) config('fawry.merchant_code'),
                $merchantRef,
            ),
        ]);

        return [
            'status' => $this->normalizeStatus((string) ($response['orderStatus'] ?? $response['status'] ?? '')),
            'raw' => $response,
        ];
    }

    public function verifyWebhookSignature(array $payload): bool
    {
        $expected = self::signature(
            (string) ($payload['merchantRefNum'] ?? ''),
            (string) ($payload['fawryRefNumber'] ?? ''),
            (string) ($payload['orderAmount'] ?? ''),
            (string) ($payload['orderStatus'] ?? ''),
        );

        return hash_equals($expected, (string) ($payload['signature'] ?? ''));
    }

    public function normalizeStatus(string $fawryStatus): string
    {
        return match (strtoupper($fawryStatus)) {
            'PAID', 'SUCCESS', 'SETTLED' => 'PAID',
            'FAILED', 'CANCELLED', 'CANCELED', 'REJECTED' => 'FAILED',
            'EXPIRED' => 'EXPIRED',
            default => 'PENDING',
        };
    }

    public static function merchantRef(): string
    {
        return 'EDU-'.strtoupper(Str::random(12));
    }

    /**
     * @throws AppException
     */
    private function post(string $path, array $body): array
    {
        try {
            $response = Http::baseUrl((string) config('fawry.base_url'))
                ->acceptJson()
                ->timeout(20)
                ->post($path, $body);
        } catch (\Throwable $e) {
            throw AppException::fromKey('messages.payments.fawry_error', 'FAWRY_ERROR', 502);
        }

        if ($response->failed()) {
            throw AppException::fromKey('messages.payments.fawry_error', 'FAWRY_ERROR', 502);
        }

        $data = $response->json();

        return is_array($data) ? $data : [];
    }
}
