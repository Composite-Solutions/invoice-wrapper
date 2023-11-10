<?php

namespace Composite\InvoiceWrapper\Services\Szamlazzhu;

require_once __DIR__ . '/sdk_autoloader.php';

use Composite\InvoiceWrapper\Interfaces\InvoiceGateway;
use Exception;
use SzamlaAgent\Buyer;
use SzamlaAgent\Currency;
use SzamlaAgent\Document\Document;
use SzamlaAgent\Document\Invoice\Invoice;
use SzamlaAgent\Item\InvoiceItem;
use SzamlaAgent\Language;
use SzamlaAgent\Response\SzamlaAgentResponse;
use SzamlaAgent\SzamlaAgent;
use SzamlaAgent\SzamlaAgentAPI;
use SzamlaAgent\TaxPayer;

class Szamlazzhu implements InvoiceGateway
{
    private SzamlaAgent $client;

    public function __construct(array $providerConfig)
    {
        $this->client = SzamlaAgentAPI::create($providerConfig['api_key']);
    }

    public function issueInvoice(array $invoicePayload): array
    {
        try {
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
                $vat = $this->getVat($item['vat']);
                $netUnitPrice = $item['unit_price_type'] == 'net' ?
                    $item['unit_price'] :
                    $item['unit_price'] / (1 + $vat / 100);
                $netPrice = $netUnitPrice * $item['quantity'];
                $vatAmount = $item['unit_price_type'] == 'net' ?
                    $netPrice * $vat / 100 :
                    $item['unit_price'] - $netUnitPrice;
                $grossAmount = $netPrice + $vatAmount;
                $invoiceItem = new InvoiceItem(
                    name: $item['name'],
                    netUnitPrice: $netUnitPrice,
                    quantity: $item['quantity'],
                    quantityUnit: $item['unit'],
                    vat: $vat
                );
                $invoiceItem->setNetPrice($netPrice);
                $invoiceItem->setVatAmount($vatAmount);
                $invoiceItem->setGrossAmount($grossAmount);
                $invoice->addItem($invoiceItem);
            }


            $response = $this->client->generateInvoice($invoice);
            if ($response->isSuccess()) {
                return $this->getInvoice($response->getData()['documentNumber']);
            }
        } catch (Exception $e) {
            return [$e->getMessage()];
//            $this->client->logError($e->getMessage());
        }
        return ["error"];
    }

    public function getInvoice(string $invoiceId): array
    {
        $this->client->setResponseType(SzamlaAgentResponse::RESULT_AS_XML);

        $response = $this->client->getInvoiceData($invoiceId);

        if ($response->isSuccess()) {
            $invoiceResponse = $response->getData()['result'];
            $items = [];
            foreach ($invoiceResponse['tetelek']['tetel'] as $item) {
                $items[] = [
                    'name' => $item['nev'],
                    'unit_net_price' => $item['nettoegysegar'],
                    'quantity' => $item['mennyiseg'],
                    'unit' => $item['mennyisegiegyseg'],
                    'vat' => $item['afakulcs'], // '0%','5%','18%','27%','27%'
                    'net_price' => $item['netto'],
                    'vat_price' => $item['afa'],
                    'gross_price' => $item['brutto'],
                ];
            }
            $invoicePayload = [
                'partner' => [
                    'id' => $invoiceResponse['vevo']['id'],
                    'name' => $invoiceResponse['vevo']['nev'],
                    'address' => [
                        'country_code' => $this->getCountryCode($invoiceResponse['vevo']['cim']['orszag'] ?? ''),
                        'post_code' => $invoiceResponse['vevo']['cim']['irsz'],
                        'city' => $invoiceResponse['vevo']['cim']['telepules'],
                        'address' => $invoiceResponse['vevo']['cim']['cim'],
                    ],
                    'taxcode' => $invoiceResponse['vevo']['adoszam'],
                    'email' => $invoiceResponse['vevo']['email'],
                ],
                'invoice' => [
                    'fulfillment_date' => $invoiceResponse['alap']['telj'],
                    'due_date' => $invoiceResponse['alap']['fizh'],
                    'payment_method' => $this->getPaymentMethodType($invoiceResponse['alap']['fizmod']),
                    'language' => $invoiceResponse['alap']['nyelv'],
                    'currency' => $invoiceResponse['alap']['devizanem'],
//                    'paid' => true, // TODO: get from response
                    'items' => $items,
                    'comment' => $invoiceResponse['alap']['megjegyzes'],
                ],
            ];
            return $invoicePayload;
        }
        return ["error"];
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

    private function getPaymentMethodType($method): string
    {
        return match ($method) {
            Document::PAYMENT_METHOD_BANKCARD => 'bankcard',
            Document::PAYMENT_METHOD_CASH => 'cash',
            Document::PAYMENT_METHOD_TRANSFER => 'wire_transfer',
            Document::PAYMENT_METHOD_CASH_ON_DELIVERY => 'cash_on_delivery',
            Document::PAYMENT_METHOD_PAYPAL => 'paypal',
            Document::PAYMENT_METHOD_SZEP_CARD => 'szep_card',
            default => 'cash',
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

    private function getTaxPayer($taxType): int
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

    private function getCountryCode($countryName): string
    {
        return match ($countryName) {
            'Magyarország' => 'HU', // TODO: get from config, add more countries
            default => $countryName,
        };
    }

    private function getCountryName($countryCode): string
    {
        return match ($countryCode) {
            'HU' => 'Magyarország', // TODO: get from config, add more countries
            default => $countryCode,
        };
    }

}
