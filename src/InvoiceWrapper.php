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

    /**
     * @param array $invoice
     * @return array
     */
    public function issueInvoice(array $invoice): array
    {
        return $this->invoiceGateway->issueInvoice($invoice);
    }

    /**
     * @param string $invoiceId
     * @return array
     */
    public function getInvoice(string $invoiceId): array
    {
        return $this->invoiceGateway->getInvoice($invoiceId);
    }

    /**
     * @param string $invoiceId
     * @return mixed
     */
    public function downloadInvoice(string $invoiceId)
    {
        return $this->invoiceGateway->downloadInvoice($invoiceId);
    }
}
