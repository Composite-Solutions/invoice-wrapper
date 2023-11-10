<?php

namespace Composite\InvoiceWrapper;

use Composite\InvoiceWrapper\Interfaces\InvoiceGateway;

class InvoiceWrapper
{
    protected InvoiceGateway $invoiceGateway;
    public function __construct(InvoiceGateway $invoiceGateway)
    {
        $this->invoiceGateway = $invoiceGateway;
    }

    public function issueInvoice(array $invoice): array
    {
        return $this->invoiceGateway->issueInvoice($invoice);
    }
}
