<?php

namespace Composite\InvoiceWrapper\Services\Szamlazzhu;

require_once __DIR__ . '/sdk_autoloader.php';

use Composite\InvoiceWrapper\Interfaces\InvoiceGateway;
use Exception;
use SzamlaAgent\SzamlaAgent;
use SzamlaAgent\SzamlaAgentAPI;

class Szamlazzhu implements InvoiceGateway
{
    private SzamlaAgent $client;

    public function __construct(array $providerConfig)
    {
        $this->client = SzamlaAgentAPI::create($providerConfig['api_key']);
    }
    public function issueInvoice(array $invoicePayload): array
    {
        dd($invoicePayload);
        try {
            $this->client->setResponseType(SzamlaAgentResponse::RESULT_AS_XML);
            $invoice = new Invoice(Invoice::INVOICE_TYPE_E_INVOICE);

            $header = $invoice->getHeader();
            $header->setPaymentMethod($this->getPaymentMethod($invoicePayload['invoice']['payment_method']))
                ->setCurrency($this->getCurrency($invoicePayload['invoice']['currency']))
                ->setLanguage($this->getLanguage($invoicePayload['invoice']['language']) ?? Language::LANGUAGE_HU)
                ->setPaid($invoicePayload['invoice']['paid'])
                ->setFulfillment($invoicePayload['invoice']['fulfillment_date'])
                ->setPaymentDue($invoicePayload['invoice']['due_date'])
                ->setEuVat(false)
                ->setComment($invoicePayload['invoice']['comment']);

            $buyer = new Buyer(
                $invoicePayload['partner']['name'],
                $invoicePayload['partner']['address']['post_code'],
                $invoicePayload['partner']['address']['city'],
                $invoicePayload['partner']['address']['address']
            );

            $buyer->setPhone($invoicePayload['partner']['phone'] ?? '')
                ->setTaxPayer($this->getTaxPayer($invoicePayload['partner']['tax_type']))
                ->setTaxNumber($invoicePayload['partner']['taxcode'] ?? '')
                ->setEmail($invoicePayload['partner']['email'] ?? '')
                ->setSendEmail($invoicePayload['partner']['send_email'] ?? false);

            $invoice->setBuyer($buyer);

            foreach ($invoicePayload['invoice']['items'] as $item) {
                $netPrice = $item['unit_price'] * $item['quantity'];
                $vat = $this->getVat($item['vat']);
                $invoiceItem = new InvoiceItem($item['name'], $item['unit_price'], $item['quantity'], $item['unit'], $vat);
                $invoiceItem->setNetPrice($netPrice)
                    ->setVatAmount($netPrice * $vat / 100)
                    ->setGrossAmount($netPrice * (100 + $vat) / 100);
                $invoice->addItem($invoiceItem);
            }

            $response = $this->client->generateInvoice($invoice);
            if ($response->isSuccess()) {
                return $this->getInvoice($response->getData()['documentNumber']);
            }
        } catch (Exception $e) {
            $this->client->logError($e->getMessage());
        }
    }

    public function getInvoice(int $invoiceId): array
    {
        return ["Szamazz.hu"];
    }

    public function downloadInvoice(int $invoiceId): array
    {
        return ["Szamazz.hu"];
    }

    private function getPaymentMethod($method): string
    {
        return match ($method) {
            'bankcard' => Document::PAYMENT_METHOD_BANKCARD,
            'cash' => Document::PAYMENT_METHOD_CASH,
            'wire_transfer' => Document::PAYMENT_METHOD_TRANSFER,
            'cash_on_delivery' => Document::PAYMENT_METHOD_CASH_ON_DELIVERY,
            'paypal' => Document::PAYMENT_METHOD_PAYPAL,
            'szep_card' => Document::PAYMENT_METHOD_SZEP_CARD,
            default => Document::PAYMENT_METHOD_CASH,
        };
    }

    private function getCurrency($currency): string
    {
        return match ($currency) {
            'HUF' => Currency::CURRENCY_HUF,
            'EUR' => Currency::CURRENCY_EUR,
            default => Currency::CURRENCY_HUF,
        };
    }

    private function getLanguage($language): ?string
    {
        return match ($language) {
            'hu' => Language::LANGUAGE_HU,
            'en' => Language::LANGUAGE_EN,
            default => null,
        };
    }

    private function getTaxPayer($taxType): string
    {
        return match ($taxType) {
            'HAS_TAX_NUMBER' => TaxPayer::TAXPAYER_HAS_TAXNUMBER,
            default => TaxPayer::TAXPAYER_NO_TAXNUMBER,
        };
    }

    private function getVat($vatRate): string
    {
        return match ($vatRate) {
            '5', '18', '27', '0' => $vatRate,
            default => '27',
        };
    }


}
