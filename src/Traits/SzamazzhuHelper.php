<?php

namespace Composite\InvoiceWrapper\Traits;

use SzamlaAgent\Currency;
use SzamlaAgent\Document\Document;
use SzamlaAgent\Language;
use SzamlaAgent\TaxPayer;

trait SzamazzhuHelper
{
    /**
     * @param array $invoiceResponse
     * @return array
     */
    private function formatInvoiceResponse(array $invoiceResponse): array
    {
        $items = array_map(function ($item) {
            return $this->formatItem($item);
        }, $invoiceResponse['tetelek']);

        return [
            'partner' => $this->formatPartner($invoiceResponse['vevo']),
            'invoice' => [
                'invoice_id' => $invoiceResponse['alap']['szamlaszam'],
                'invoice_number' => $invoiceResponse['alap']['szamlaszam'],
                'fulfillment_date' => $invoiceResponse['alap']['telj'],
                'due_date' => $invoiceResponse['alap']['fizh'],
                'payment_method' => $this->getPaymentMethodType($invoiceResponse['alap']['fizmod']),
                'language' => $invoiceResponse['alap']['nyelv'],
                'currency' => $invoiceResponse['alap']['devizanem'],
                // 'paid' => $this->getPaymentStatus($invoiceResponse), // Assuming a method to determine this
                'items' => $items,
                'comment' => $invoiceResponse['alap']['megjegyzes'],
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
            'id' => (int)$partner['id'],
            'name' => $partner['nev'],
            'address' => $this->formatAddress($partner['cim']),
            'taxcode' => $partner['adoszam'],
            'email' => $partner['email'],
        ];
    }

    /**
     * @param array $address
     * @return array
     */
    private function formatAddress(array $address): array
    {
        return [
            'country_code' => $this->getCountryCode($address['orszag'] ?? ''),
            'post_code' => $address['irsz'],
            'city' => $address['telepules'],
            'address' => $address['cim'],
        ];
    }

    /**
     * @param array $item
     * @return array
     */
    private function formatItem(array $item): array
    {
        return [
            'name' => $item['nev'],
            'unit_net_price' => $item['nettoegysegar'],
            'quantity' => $item['mennyiseg'],
            'unit' => $item['mennyisegiegyseg'],
            'vat' => $item['afakulcs'],
            'net_price' => $item['netto'],
            'vat_price' => $item['afa'],
            'gross_price' => $item['brutto'],
        ];
    }

    /**
     * @param $method
     * @return string
     */
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

    /**
     * @param $method
     * @return string
     */
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

    /**
     * @param $currency
     * @return string
     */
    private function getCurrency($currency): string
    {
        return match ($currency) {
            'HUF' => Currency::CURRENCY_HUF,
            'EUR' => Currency::CURRENCY_EUR,
            default => Currency::CURRENCY_HUF,
        };
    }

    /**
     * @param $language
     * @return string|null
     */
    private function getLanguage($language): ?string
    {
        return match ($language) {
            'hu' => Language::LANGUAGE_HU,
            'en' => Language::LANGUAGE_EN,
            default => null,
        };
    }

    /**
     * @param $taxType
     * @return int
     */
    private function getTaxPayer($taxType): int
    {
        return match ($taxType) {
            'HAS_TAX_NUMBER' => TaxPayer::TAXPAYER_HAS_TAXNUMBER,
            default => TaxPayer::TAXPAYER_NO_TAXNUMBER,
        };
    }

    /**
     * @param $vatRate
     * @return string
     */
    private function getVat($vatRate): string
    {
        return match ($vatRate) {
            '5', '18', '27', '0' => $vatRate,
            default => '27',
        };
    }

    /**
     * @param $countryName
     * @return string
     */
    private function getCountryCode($countryName): string
    {
        return match ($countryName) {
            'Magyarország' => 'HU', // TODO: get from config, add more countries
            default => $countryName,
        };
    }

    /**
     * @param $countryCode
     * @return string
     */
    private function getCountryName($countryCode): string
    {
        return match ($countryCode) {
            'HU' => 'Magyarország', // TODO: get from config, add more countries
            default => $countryCode,
        };
    }
}
