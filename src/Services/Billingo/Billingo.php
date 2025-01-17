<?php

namespace Composite\InvoiceWrapper\Services\Billingo;

use Composite\InvoiceWrapper\Enums\BillingoDocumentTypes;
use Composite\InvoiceWrapper\Interfaces\InvoiceGateway;
use Composite\InvoiceWrapper\Services\Billingo\BillingoApiService\BillingoClient;
use Composite\InvoiceWrapper\Traits\BillingoHelper;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Arr;

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
        $prepareInvoice = $this->preparePayloadDataForDocument($invoicePayload, BillingoDocumentTypes::INVOICE->toString());

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

    private function getBlockIdByType(string $type): int
    {
        return match ($type) {
            'invoice' => (int)data_get($this->config, 'block_id', 0),
            'waybill' => (int)data_get($this->config, 'waybill_block_id', 0),
            default => 0,
        };
    }

    /**
     * @param array $payload
     * @param string $type
     * @return array
     * @throws GuzzleException
     */
    private function preparePayloadDataForDocument(array $payload, string $type): array
    {
        return [
            'partner_id' => $this->createOrUpdatePartner($payload)['id'],
            'block_id' => $this->getBlockIdByType($type),
            'type' => $type,
            'fulfillment_date' => Arr::get($payload, 'invoice.fulfillment_date'),
            'due_date' => Arr::get($payload, 'invoice.due_date'),
            'payment_method' => Arr::get($payload, 'invoice.payment_method'),
            'language' => Arr::get($payload, 'invoice.language'),
            'currency' => Arr::get($payload, 'invoice.currency'),
            'paid' => Arr::get($payload, 'invoice.paid'),
            'items' => $this->createInvoiceItems(Arr::get($payload, 'invoice.items')),
            'conversion_rate' => Arr::get($payload, 'invoice.conversion_rate'),
            'comment' => Arr::get($payload, 'invoice.comment'),
        ];
    }

    /**
     * @param array $waybillPayload
     * @return array
     * @throws GuzzleException
     */
    public function issueWayBill(array $waybillPayload): array
    {

        //Assemble the waybill payload
        $prepareWayBill = $this->preparePayloadDataForDocument($waybillPayload, BillingoDocumentTypes::WAYBILL->toString());

        //Set the settings for the waybill
        $settings = [
            "selected_type" => BillingoDocumentTypes::WAYBILL->toString(),
        ];
        $prepareWayBill['settings'] = $settings;

        //Create the waybill
        $wayBillResponse = $this->client->createDocument($prepareWayBill);


        return $this->formatInvoiceResponse($wayBillResponse);
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
     * @throws GuzzleException
     */
    public function downloadInvoice(string $invoiceId): void
    {
        $response = $this->client->downloadDocument((int)$invoiceId);
        $invoiceNumber = $this->getInvoice($invoiceId)['invoice']['invoice_number'];

        header('Content-type: application/pdf');
        header("Content-Disposition: attachment; filename={$invoiceNumber}.pdf");
        header('Pragma: no-cache');
        header('Expires: 0');

        echo $response;
    }
}
