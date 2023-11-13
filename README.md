# 🧾✨ Invoice Magic Wand for Laravel

Unleash the alchemy of billing with Invoice Magic Wand for Laravel! 🪄📜

This mystical package conjures up invoices with a flick of your developer's wrist, transforming the mundane task of invoice generation into a delightful display of digital wizardry. Whether you're billing with **Billingo** or summoning statements with **Szamlazz.hu**, this enchanting toolkit makes the numbers dance to your tune. No more grunt work, just sparkling invoices popping out like rabbits from a magician's hat! 🐇💫

## 📦 Installation

To install the package, run the following command in your Laravel project:

```bash
composer require composite/invoice-wrapper
```
Publish the configuration file with:

```bash
php artisan vendor:publish --provider="Composite\InvoiceWrapper\InvoiceWrapperServiceProvider"
```

## ⚙️ Configuration
After publishing the config file, you should set your environment variables in your .env file:
```dotenv
INVOICING_PROVIDER=billingo

# Billingo Configuration
BILLINGO_BASE_URL=https://api.billingo.hu/v3/
BILLINGO_API_KEY=your-billingo-api-key
BILLINGO_BLOCK_ID=your-billingo-block-id

# Szamlazz.hu Configuration
SZAMLAZZHU_API_KEY=your-szamlazzhu-api-key
```
Make sure to replace your-billingo-api-key, your-billingo-block-id, and your-szamlazzhu-api-key with your actual API keys and configuration details.

## 🛠 Usage
To issue an invoice, you can use the InvoiceWrapper facade with the desired invoice payload. Here's an example:

```php
use Composite\InvoiceWrapper\Facades\InvoiceWrapper;
use Carbon\Carbon;

$invoicePayload = [
    // Your invoice payload here
];

// Issue the invoice using the selected provider
$invoice = InvoiceWrapper::issueInvoice($invoicePayload);
```

Sample invoice payload:

```php
$invoicePayload = [
    'partner' => [
        'id' => null, // for billingo required
        'name' => 'Test Partner',
        'address' => [
            'country_code' => 'HU',
            'post_code' => '1111',
            'city' => 'Budapest',
            'address' => 'Test utca 1.',
        ],
        'tax_type' => 'NO_TAX_NUMBER', //"HAS_TAX_NUMBER" : "NO_TAX_NUMBER",
        'taxcode' => 'HU29168950',
        'email' => 'btamba@composite.hu', // if email sending is true it is required
        'send_email' => true,
    ],
    'invoice' => [
        // 'type' => 'invoice', // not handled yet
        'fulfillment_date' => Carbon::now()->format('Y-m-d'),
        'due_date' => Carbon::now()->format('Y-m-d'),
        'payment_method' => 'bankcard', //'bankcard','cash','wire_transfer','cash_on_delivery','paypal','szep_card',
        'language' => 'hu', // 'hu', 'en'
        'currency' => 'HUF', // 'HUF', 'EUR'
        'paid' => false,
        'items' => [
            [
                'name' => 'Test product',
                'unit_price' => 1000,
                'unit_price_type' => 'net', // net, gross
                'quantity' => 1,
                'unit' => 'db',
                'vat' => '5', // '0%','5%','18%','27%','27%'
            ],
            [
                'name' => 'Test product 2',
                'unit_price' => 2000,
                'unit_price_type' => 'gross', // net, gross
                'quantity' => 1,
                'unit' => 'db',
                'vat' => '27', // '0%','5%','18%','27%','27%'
            ],
            [
                'name' => 'Test product 3',
                'unit_price' => 2000,
                'unit_price_type' => 'net', // net, gross
                'quantity' => 2,
                'unit' => 'db',
                'vat' => '27', // '0%','5%','18%','27%','27%'
            ]
        ],
        // 'conversion_rate' => 1, // Not handled yet
        'comment' => 'It is a comment',
    ],
];
```

To retrieve an invoice:

```php
$invoiceId = 'invoice-id-here';
$invoice = InvoiceWrapper::getInvoice($invoiceId);
```

Make sure to handle any exceptions that may be thrown due to API errors or configuration issues.

## 📝✨To-Do List for Future Enhancements
Here's a whimsical checklist of tasks for the Invoice Wrapper's journey ahead:

- [x] 🧙‍️ Creating the Invoice Wrapper - Done!
- [x] 🧞‍ Implementing Szamlazz.hu Service - Done!
- [ ] 🧞‍ Implementing Billingo Service - "The Genie is out of the bottle!"
- [ ] 📩 Downloading Invoice - Awaiting the magic spell!
- [ ] 🔗 Getting Download Links - Soon to be summoned from the digital ether!

## 🤝 Support
For issues, questions, and contributions, please use the GitHub issues section of this repository.

## 🌟 Credits
This package is proudly brought to you by:

- 🏢 [Composite Solutions](https://github.com/Composite-Solutions) - For innovative solutions and dedicated support.
- 👨‍💻 [Bence Tamba](https://github.com/tambabence) - The mastermind behind the code magic.

A huge shout-out to all contributors and supporters! Your feedback and contributions make this project better every day. 🚀🙌

Thank you for being part of this journey! 🎉👏

## 📜 Changelog
All notable changes to this project will be documented in the [CHANGELOG.md](CHANGELOG.md) file.

## 📜 License
This package is open-source software licensed under the [MIT license](https://opensource.org/licenses/MIT).
