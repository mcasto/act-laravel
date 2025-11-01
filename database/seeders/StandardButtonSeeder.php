<?php

namespace Database\Seeders;

use App\Models\StandardButton;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class StandardButtonSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $buttons = [
            [
                'label' => 'Pay by PayPal',
                'description' => "<p>PayPal requires a two-step process:<ol><li>Send $75 per Flex ticket to callanspappy@yahoo.com using the Friends and Family option.</li><li><a href='actseats@gmail.com'>Email us</a> with your name, WhatsApp, and email address and the subject line \"Just bought Flex tickets.\" We'll email acknowledgement of receipt.</li></ol></p>",
                'key' => 'paypal',
                'sort_order' => 0
            ],
            [
                'label' => "Pay by Ecuadorian Bank Transfer",
                'description' => "<p>Transfer $75 per ticket to:\n  <ul>\n    <li>Bradley Scott Laye</li>\n    <li>JEP Savings Account (Ahorros)</li>\n    <li>Account number 406139559101</li>\n    <li>ID: 593619919</li>\n  </ul>\n</p>\n<p>\n  Then click to email us\n  <a href='mailto:actseats@gmail.com'>actseats@gmail.com</a>\n  confirming you purchased Flex Tickets by bank transfer and the transfer date. Also include your name and phone number. We will reply to confirm receipt of payment.\n</p>",
                'key' => "transfer",
                'sort_order' => 1
            ],
            [
                'label' => "Message us with other questions",
                'description' => "Click to email us\n<a href=\"mailto:actseats@gmail.com\">actseats@gmail.com</a> with any questions or\nissues, and we will reply within 24 hours.",
                'key' => "questions",
                'sort_order' => 2
            ]
        ];

        foreach ($buttons as $button) {
            $html = $button['description'];
            unset($button['description']);
            Storage::disk('local')
                ->put("standard-buttons/{$button['key']}.html", $html);
            StandardButton::create($button);
        }
    }
}
