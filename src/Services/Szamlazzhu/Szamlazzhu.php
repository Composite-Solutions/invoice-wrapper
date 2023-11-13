<?php

namespace Composite\InvoiceWrapper\Services\Szamlazzhu;

require_once __DIR__ . '/sdk_autoloader.php';

use Composite\InvoiceWrapper\Interfaces\InvoiceGateway;
use Composite\InvoiceWrapper\Traits\SzamazzhuHelper;
use Exception;
use SzamlaAgent\Buyer;
use SzamlaAgent\Document\Invoice\Invoice;
use SzamlaAgent\Item\InvoiceItem;
use SzamlaAgent\Language;
use SzamlaAgent\Response\SzamlaAgentResponse;
use SzamlaAgent\SzamlaAgent;
use SzamlaAgent\SzamlaAgentAPI;
use SzamlaAgent\SzamlaAgentException;

class Szamlazzhu implements InvoiceGateway
{
    use SzamazzhuHelper;

    private SzamlaAgent $client;

    /**
     * @param array $providerConfig
     * @throws SzamlaAgentException
     */
    public function __construct(array $providerConfig)
    {
        $this->client = SzamlaAgentAPI::create($providerConfig['api_key']);
    }

    /**
     * @param array $invoicePayload
     * @return array
     * @throws SzamlaAgentException
     * @throws Exception
     */
    public function issueInvoice(array $invoicePayload): array
    {
        $this->client->setResponseType(SzamlaAgentResponse::RESULT_AS_XML);
        $invoice = new Invoice(Invoice::INVOICE_TYPE_E_INVOICE);

        $header = $invoice->getHeader();
        $header->setPaymentMethod($this->getPaymentMethod($invoicePayload['invoice']['payment_method']));
        $header->setCurrency($this->getCurrency($invoicePayload['invoice']['currency']));
        $header->setLanguage($this->getLanguage($invoicePayload['invoice']['language']) ?? Language::LANGUAGE_HU);
        $header->setPaid($invoicePayload['invoice']['paid']);
        $header->setFulfillment($invoicePayload['invoice']['fulfillment_date']);
        $header->setPaymentDue($invoicePayload['invoice']['due_date']);
        $header->setEuVat(false);
        $header->setComment($invoicePayload['invoice']['comment']);

        $buyer = new Buyer(
            name: $invoicePayload['partner']['name'],
            zipCode: $invoicePayload['partner']['address']['post_code'],
            city: $invoicePayload['partner']['address']['city'],
            address: $invoicePayload['partner']['address']['address'],
        );

        $buyer->setPhone($invoicePayload['partner']['phone'] ?? '');
        $buyer->setTaxNumber($invoicePayload['partner']['taxcode'] ?? '');
        $buyer->setTaxPayer($this->getTaxPayer($invoicePayload['partner']['tax_type']));
        $buyer->setEmail($invoicePayload['partner']['email'] ?? '');
        $buyer->setSendEmail(($invoicePayload['partner']['send_email'] && $invoicePayload['partner']['email']) ?? false);

        $invoice->setBuyer($buyer);

        foreach ($invoicePayload['invoice']['items'] as $item) {
            // Calculate VAT based on the item's VAT rate
            $vat = $this->getVat($item['vat']);

            // Determine net unit price depending on if the unit price type is 'net' or not
            $netUnitPrice = $item['unit_price_type'] == 'net'
                ? $item['unit_price']
                : $item['unit_price'] / (1 + $vat / 100);

            // Calculate the net price for the total item quantity
            $netPrice = $netUnitPrice * $item['quantity'];

            // Calculate the VAT amount depending on the unit price type
            $vatAmount = $item['unit_price_type'] == 'net'
                ? $netPrice * $vat / 100
                : $item['unit_price'] - $netUnitPrice;

            // Calculate the gross amount for the item
            $grossAmount = $netPrice + $vatAmount;

            // Create a new invoice item with the calculated values
            $invoiceItem = new InvoiceItem(
                name: $item['name'],
                netUnitPrice: $netUnitPrice,
                quantity: $item['quantity'],
                quantityUnit: $item['unit'],
                vat: $vat
            );

            // Set the net price, VAT amount, and gross amount for the invoice item
            $invoiceItem->setNetPrice($netPrice);
            $invoiceItem->setVatAmount($vatAmount);
            $invoiceItem->setGrossAmount($grossAmount);

            // Add the item to the invoice
            $invoice->addItem($invoiceItem);
        }

        $response = $this->client->generateInvoice($invoice);
        if ($response->isSuccess()) {
            return $this->getInvoice($response->getData()['documentNumber']);
        } else {
            throw new Exception($response->isFailed());
        }
    }

    /**
     * @param string $invoiceId
     * @return array
     * @throws SzamlaAgentException
     * @throws Exception
     */
    public function getInvoice(string $invoiceId): array
    {
        $this->client->setResponseType(SzamlaAgentResponse::RESULT_AS_XML);
        $response = $this->client->getInvoiceData($invoiceId);

        if (!$response->isSuccess()) {
            throw new Exception('Invoice data retrieval failed.');
        }

        return $this->formatInvoiceResponse($response->getData()['result']);
    }

    /**
     * @param string $invoiceId
     * @return void
     * @throws SzamlaAgentException
     * @throws Exception
     */
    public function downloadInvoice(string $invoiceId): void
    {
        $response = $this->client->getInvoicePdf($invoiceId);

        if (!$response->isSuccess()) {
            throw new Exception('Invoice download failed.');
        }
        $response->downloadPdf();
    }
}
