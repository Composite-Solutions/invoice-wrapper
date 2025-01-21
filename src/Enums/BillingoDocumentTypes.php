<?php

namespace Composite\InvoiceWrapper\Enums;

enum BillingoDocumentTypes: string
{
    case INVOICE = 'invoice';
    case WAYBILL = 'waybill';

    public static function getDocumentType(string $type): string
    {
        return match ($type) {
            self::INVOICE => 'invoice',
            self::WAYBILL => 'waybill',
            default => throw new \Exception('Invalid document type'),
        };
    }

    public function toString()
    {
        return match ($this) {
            self::INVOICE => 'invoice',
            self::WAYBILL => 'waybill',
        };
    }
}
