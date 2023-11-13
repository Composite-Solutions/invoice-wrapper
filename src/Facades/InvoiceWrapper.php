<?php

namespace Composite\InvoiceWrapper\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static issueInvoice(array[] $array)
 * @method static getInvoice($withoutTax)
 * @method static downloadInvoice(string $withoutTax)
 */
class InvoiceWrapper extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'invoice-wrapper';
    }
}
