<?php

namespace Composite\InvoiceWrapper\Services\Billingo;

use Composite\InvoiceWrapper\Interfaces\InvoiceGateway;

class Billingo implements InvoiceGateway
{
    public function issueInvoice(array $invoice): array
    {
        return ["Billingo"];
    }

    public function getInvoice(int $invoiceId): array
    {
        return ["Billingo"];
    }

    public function downloadInvoice(int $invoiceId): array
    {
        return ["Billingo"];
    }
}
