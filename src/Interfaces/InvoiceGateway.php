<?php

namespace Composite\InvoiceWrapper\Interfaces;

interface InvoiceGateway
{
    public function issueInvoice(array $invoicePayload): array;
    public function getInvoice(string $invoiceId): array;
    public function downloadInvoice(int $invoiceId): array;
}
