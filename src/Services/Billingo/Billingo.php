<?php

namespace Composite\InvoiceWrapper\Services\Billingo;

use Composite\InvoiceWrapper\Interfaces\InvoiceGateway;
use Composite\InvoiceWrapper\Services\Billingo\BillingoApiService\BillingoClient;
use Composite\InvoiceWrapper\Traits\BillingoHelper;
use GuzzleHttp\Exception\GuzzleException;

class Billingo implements InvoiceGateway
{
    use BillingoHelper;

    private BillingoClient $client;
    private array $config;

    /**
     * @param array $providerConfig
     */
    public function __construct(array $providerConfig)
    {
        $this->client = new BillingoClient($providerConfig);
        $this->config = $providerConfig;
    }

    /**
     * @param array $invoicePayload
     * @return array
     * @throws GuzzleException
     */
    public function issueInvoice(array $invoicePayload): array
    {
        $prepareInvoice = [
            'partner_id' => $this->createOrUpdatePartner($invoicePayload)['id'],
            'block_id' => (int)$this->config['block_id'] ?? 0,
            'type' => $invoicePayload['invoice']['type'] ?? 'invoice',
            'fulfillment_date' => $invoicePayload['invoice']['fulfillment_date'],
            'due_date' => $invoicePayload['invoice']['due_date'],
            'payment_method' => $invoicePayload['invoice']['payment_method'],
            'language' => $invoicePayload['invoice']['language'],
            'currency' => $invoicePayload['invoice']['currency'],
            'paid' => $invoicePayload['invoice']['paid'],
            'items' => $this->createInvoiceItems($invoicePayload['invoice']['items']),
            'conversion_rate' => $invoicePayload['invoice']['conversion_rate'],
            'comment' => $invoicePayload['invoice']['comment'],
        ];

        // $settings = [ // TODO: implement settings and rounding routines
        // 	"round" => "five",
        // ];

        // if ($payload['invoice']["payment_method"] == "cash" || $payload['invoice']["payment_method"] == "cash_on_delivery") {
        // 	$prepareInvoice["settings"] = $settings;
        // }

        $invoiceResponse = $this->client->createDocument($prepareInvoice);

        if (isset($invoicePayload['partner']['email']) && $invoicePayload['partner']['send_email']) {
            $this->client->sendInvoice((int)$invoiceResponse['id']);
        }

        return $this->formatInvoiceResponse($invoiceResponse);
    }

    /**
     * @param string $invoiceId
     * @return array
     * @throws GuzzleException
     */
    public function getInvoice(string $invoiceId): array
    {
        $response = $this->client->getDocument((int)$invoiceId);
        return $this->formatInvoiceResponse($response);
    }

    /**
     * @param string $invoiceId
     * @return void
     */
    public function downloadInvoice(string $invoiceId): void
    {
        // ...
    }
}
