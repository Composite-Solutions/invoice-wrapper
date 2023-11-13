<?php

namespace Composite\InvoiceWrapper\Traits;

use GuzzleHttp\Exception\GuzzleException;

trait BillingoHelper
{
    /**
     * @param array $invoiceResponse
     * @return array
     */
    private function formatInvoiceResponse(array $invoiceResponse): array
    {
        $items = array_map(function ($item) {
            return $this->formatItem($item);
        }, $invoiceResponse['items']);

        return [
            'partner' => $this->formatPartner($invoiceResponse['partner']),
            'invoice' => [
                'invoice_id' => $invoiceResponse['id'],
                'invoice_number' => $invoiceResponse['invoice_number'],
                'fulfillment_date' => $invoiceResponse['fulfillment_date'],
                'due_date' => $invoiceResponse['due_date'],
                'payment_method' => $invoiceResponse['payment_method'],
                'language' => $invoiceResponse['language'],
                'currency' => $invoiceResponse['currency'],
                'paid' => $invoiceResponse['currency'] === 'paid',
                'items' => $items,
                'comment' => $invoiceResponse['comment'],
            ],
        ];
    }

    /**
     * @param array $partner
     * @return array
     */
    private function formatPartner(array $partner): array
    {
        return [
            'id' => $partner['id'],
            'name' => $partner['name'],
            'address' => $partner['address'],
            'taxcode' => $partner['taxcode'],
            'email' => $partner['emails'][0] ?? '',
        ];
    }

    /**
     * @param array $item
     * @return array
     */
    private function formatItem(array $item): array
    {
        return [
            'name' => $item['name'],
            'unit_net_price' => $item['net_unit_amount'],
            'quantity' => $item['quantity'],
            'unit' => $item['unit'],
            'vat' => $item['vat'],
            'net_price' => $item['net_amount'],
            'vat_price' => $item['vat_amount'],
            'gross_price' => $item['gross_amount'],
        ];
    }

    /**
     * @param $vatRate
     * @return string
     */
    private function getVat($vatRate): string
    {
        return match ($vatRate) {
            '0', '5', '18', '27' => $vatRate . '%',
            default => '27%',
        };
    }


    /**
     * @param array $payload
     * @return array
     */
    private function createInvoiceItems(array $payload): array
    {
        $responseData = [];
        foreach ($payload as $item) {
            $vat = $this->getVat($item['vat']);
            $responseData[] = [
                'name' => $item['name'],
                'unit_price' => $item['unit_price'],
                'unit_price_type' => $item['unit_price_type'],
                'quantity' => $item['quantity'],
                'unit' => $item['unit'],
                'vat' => $vat,
            ];
        }
        return $responseData;
    }

    /**
     * @param array $payload
     * @return array
     * @throws GuzzleException
     */
    private function createOrUpdatePartner(array $payload): array
    {
        $partner = [
            'name' => $payload['partner']['name'],
            'address' => [
                'country_code' => $payload['partner']['address']['country_code'],
                'post_code' => $payload['partner']['address']['post_code'],
                'city' => $payload['partner']['address']['city'],
                'address' => $payload['partner']['address']['address'],
            ],
            'tax_type' => $payload['partner']['tax_type'],
            'taxcode' => $payload['partner']['taxcode'],
            'emails' => [$payload['partner']['email']],
        ];

        if ($payload['partner']['id'] === null) {
            $response = $this->client->createBillingoPartner($partner);
        } else {
            $response = $this->client->updateBillingoPartner($payload['partner']['id'], $partner);
        }

        return $response;
    }
}
