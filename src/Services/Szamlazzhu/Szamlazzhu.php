<?php

namespace Composite\InvoiceWrapper\Services\Szamlazzhu;

use Composite\InvoiceWrapper\Interfaces\InvoiceGateway;

class Szamlazzhu implements InvoiceGateway
{

    public function issueInvoice(array $invoice): array
    {
        return ["Szamazz.hu"];
    }

    public function getInvoice(int $invoiceId): array
    {
        return ["Szamazz.hu"];
    }

    public function downloadInvoice(int $invoiceId): array
    {
        return ["Szamazz.hu"];
    }
}
