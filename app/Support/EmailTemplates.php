<?php

namespace App\Support;

use App\Mail\AngelDonationConfirmationMailer;
use App\Mail\AngelDonationMailer;
use App\Mail\CompTicketMailer;
use App\Mail\ContactMailer;
use App\Mail\CourseEnrollmentConfirmedMailer;
use App\Mail\CourseInquiryConfirmationMailer;
use App\Mail\CourseInquiryMailer;
use App\Mail\CourseMessageMailer;
use App\Mail\FlexPurchaseConfirmationMailer;
use App\Mail\FlexPurchaseMailer;
use App\Mail\FlexRedemptionVerifyMailer;
use App\Mail\MessageUsMailer;
use App\Mail\PaymentReminderMailer;
use App\Mail\PurchaseConfirmationMailer;
use App\Mail\ReservationReminderMailer;
use App\Mail\SoldOutNotificationMailer;
use App\Mail\TicketSaleMailer;
use App\Mail\VolunteerContactMailer;
use App\Models\Angel;
use App\Models\AngelLevel;
use App\Models\Contact;
use App\Models\EmailTemplate;
use App\Models\Patron;
use App\Models\PatronFlexPackage;
use App\Models\PaymentMethod;
use App\Models\Performance;
use App\Models\Show;
use App\Models\Skill;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\File;

/**
 * Every email the site sends, for the admin Email Templates page: what
 * sends it, who gets it, and its template.
 *
 * A template is a Blade view. Edits made on the page are saved as an
 * override in storage/app/email-templates (same relative path as the view),
 * which AppServiceProvider puts ahead of resources/views — so the original
 * in resources/views stays as the default, "Reset to default" just deletes
 * the override, and edits never touch files tracked in git.
 *
 * Each email can be switched off; a switched-off email is silently not
 * sent (App\Mail\TemplatedMailable::send()). Keys are view names.
 */
class EmailTemplates
{
    /** @var array<string, bool>|null */
    private static ?array $enabled = null;

    public static function overrideRoot(): string
    {
        return storage_path('app/email-templates');
    }

    /**
     * @return array<string, array{name: string, audience: string, trigger: string, recipient: string, notes?: string, sample: \Closure}>
     */
    public static function all(): array
    {
        $ticket = fn (array $extra = []) => array_merge([
            'name'             => 'Jane Smith',
            'show_name'        => self::sampleShowName(),
            'num_tickets'      => 2,
            'performance_date' => now()->addWeek()->format('F j, Y'),
            'performance_time' => '3:00 PM',
            'reference_number' => '012, 013',
        ], $extra);

        return [
            // ── Patron emails ────────────────────────────────────────────
            'purchase-confirmation' => [
                'name'      => 'Ticket confirmation',
                'audience'  => 'Patron',
                'trigger'   => 'Automatic: a patron reserves tickets on the Purchase Tickets page (PayPal, Bank Transfer, …), or an admin adds a sale on Ticket Sales → New Ticket Sale with "Send email" on. Not sent for Flex (see Flex ticket confirmation) or credit card (Fixr sends its own).',
                'recipient' => 'The purchaser' . self::ccNote(),
                'notes'     => 'The payment method\'s section below (e.g. "Ticket confirmation — PayPal details") is inserted as $confirmation_body. Switching this off stops all ticket confirmations.',
                'sample'    => fn () => new PurchaseConfirmationMailer($ticket([
                    'view'              => 'purchase-confirmation',
                    'confirmation_body' => '<p><em>(The payment method\'s details section goes here.)</em></p>',
                ])),
            ],
            ...self::paymentSections($ticket),
            'flex-confirmation' => [
                'name'      => 'Flex ticket confirmation',
                'audience'  => 'Patron',
                'trigger'   => 'Automatic: a patron reserves with Flex tickets on the Purchase Tickets page (after confirming by email, when that\'s on), or an admin adds a Flex sale with "Send email" on.',
                'recipient' => 'The Flex holder' . self::ccNote(),
                'notes'     => 'The $confirmation_body section is edited on Admin → Flex Purchase.',
                'sample'    => fn () => new PurchaseConfirmationMailer($ticket([
                    'view'              => 'flex-confirmation',
                    'remaining_flex'    => 4,
                    'season'            => '26-27',
                    'confirmation_body' => '<p><em>(The Flex confirmation text from Admin → Flex Purchase goes here.)</em></p>',
                ])),
            ],
            'flex-redemption-verify' => [
                'name'      => 'Flex booking — confirm your request',
                'audience'  => 'Patron',
                'trigger'   => 'Automatic: a patron asks to use Flex tickets on the Purchase Tickets page while Flex email confirmation is on. The booking is held until they click the link.',
                'recipient' => 'The Flex holder\'s main email' . self::ccNote(),
                'sample'    => fn () => new FlexRedemptionVerifyMailer($ticket([
                    'view' => 'flex-redemption-verify',
                    'link' => url('/flex-confirm/sample-token'),
                ])),
            ],
            'comp-ticket-notice' => [
                'name'      => 'Comp ticket invitation',
                'audience'  => 'Patron',
                'trigger'   => 'Manual: an admin clicks Send on a comp in Admin → Comp Tickets.',
                'recipient' => 'The comp\'s email' . self::ccNote(),
                'sample'    => fn () => new CompTicketMailer(['view' => 'comp-ticket-notice', 'name' => 'Jane Smith', 'uid' => 'SAMPLE123']),
            ],
            'comp-ticket-confirm' => [
                'name'      => 'Comp ticket confirmation',
                'audience'  => 'Patron',
                'trigger'   => 'Automatic: a patron redeems a comp through its link, or an admin adds a comp sale on Ticket Sales → New Ticket Sale with "Send email" on.',
                'recipient' => 'The comp\'s email' . self::ccNote(),
                'sample'    => fn () => new CompTicketMailer($ticket(['view' => 'comp-ticket-confirm', 'pickup_name' => 'Jane Smith', 'reference_number' => '014'])),
            ],
            'reservation-reminder' => [
                'name'      => 'Reservation reminder',
                'audience'  => 'Patron',
                'trigger'   => 'Scheduled: daily at 10:00 AM for the next day\'s performances, and 4 hours before a performance for anyone who reserved after the day-before batch. Fully cancelled reservations are skipped.',
                'recipient' => 'Each purchaser with tickets for the performance' . self::ccNote(),
                'notes'     => 'Also only sent when the server setting RESERVATION_REMINDERS_SEND is on; otherwise reminders are just logged.',
                'sample'    => fn () => new ReservationReminderMailer($ticket(['day' => 'tomorrow'])),
            ],
            'payment-reminder' => [
                'name'      => 'Payment reminder',
                'audience'  => 'Patron',
                'trigger'   => 'Manual: Admin → Ticket Sales → the Pending (unpaid) list → Send reminders.',
                'recipient' => 'Each selected purchaser with an unpaid PayPal or Bank Transfer reservation' . self::ccNote(),
                'sample'    => fn () => new PaymentReminderMailer([
                    'name'         => 'Jane Smith',
                    'subject'      => 'Payment reminder',
                    'body'         => "Just a friendly reminder that we haven't received your payment yet.",
                    'reservations' => [$ticket(['payment_method' => 'PayPal'])],
                ]),
            ],
            'flex-purchase-confirmation-mailer-template' => [
                'name'      => 'Flex package confirmation',
                'audience'  => 'Patron',
                'trigger'   => 'Automatic: a patron buys a Flex package on the Flex page with PayPal or Bank Transfer (card purchases get Fixr\'s email).',
                'recipient' => 'The buyer' . self::ccNote(),
                'sample'    => fn () => new FlexPurchaseConfirmationMailer(self::sampleFlexPackage()),
            ],
            'angel-donation-confirmation-mailer-template' => [
                'name'      => 'Angel donation thank-you',
                'audience'  => 'Patron',
                'trigger'   => 'Automatic: a patron donates on the Angels page with PayPal or Bank Transfer.',
                'recipient' => 'The donor' . self::ccNote(),
                'sample'    => fn () => new AngelDonationConfirmationMailer(self::sampleAngel()),
            ],
            'course-inquiry-confirmation' => [
                'name'      => 'Class enrollment / inquiry confirmation',
                'audience'  => 'Patron',
                'trigger'   => 'Automatic: someone submits the enrollment or inquiry form on a class page.',
                'recipient' => 'The person who submitted the form' . self::ccNote(),
                'sample'    => fn () => new CourseInquiryConfirmationMailer(self::sampleCourse()),
            ],
            'course-message' => [
                'name'      => 'Class message to enrollees',
                'audience'  => 'Patron',
                'trigger'   => 'Manual: Admin → Classes → a class\'s enrollees → Email enrollees.',
                'recipient' => 'Each selected enrollee' . self::ccNote(ccAdmin: false),
                'sample'    => fn () => new CourseMessageMailer([
                    'name'            => 'Jane Smith',
                    'body'            => 'See you on Saturday — bring comfortable shoes!',
                    'course_name'     => 'Sample Class',
                    'instructor_name' => 'Sam Teacher',
                    'instructor_email' => 'teacher@example.com',
                    'subject'         => 'Sample Class',
                ]),
            ],

            // ── Staff emails ─────────────────────────────────────────────
            'ticket-sale-mailer-template' => [
                'name'      => 'New ticket sale',
                'audience'  => 'Staff',
                'trigger'   => 'Automatic: every ticket reservation on the site, every credit card (Fixr) ticket purchase, and admin sales with "Send email" on.',
                'recipient' => 'The box office (admin address)',
                'sample'    => fn () => new TicketSaleMailer([
                    'show'            => self::sampleShowName(),
                    'performance'     => now()->addWeek()->format('Y-m-d') . ' 15:00:00',
                    'first_name'      => 'Jane',
                    'last_name'       => 'Smith',
                    'email'           => 'jane@example.com',
                    'mobile_number'   => '+593 99 123 4567',
                    'payment_method'  => 'PayPal',
                    'quantity'        => 2,
                    'sold_at'         => now()->format('Y-m-d H:i:s'),
                    'special_request' => 'Aisle seats, please',
                    'ticket_numbers'  => ['012', '013'],
                ]),
            ],
            'flex-purchase-mailer-template' => [
                'name'      => 'New Flex package purchase',
                'audience'  => 'Staff',
                'trigger'   => 'Automatic: a Flex package is bought on the Flex page (any payment method, including card through Fixr).',
                'recipient' => 'The box office (admin address)',
                'sample'    => fn () => new FlexPurchaseMailer(self::sampleFlexPackage()),
            ],
            'angel-donation-mailer-template' => [
                'name'      => 'New Angel donation',
                'audience'  => 'Staff',
                'trigger'   => 'Automatic: a donation on the Angels page (any payment method, including card through Fixr).',
                'recipient' => 'The admin address',
                'sample'    => fn () => new AngelDonationMailer(self::sampleAngel()),
            ],
            'course-inquiry-mailer' => [
                'name'      => 'New class enrollment / inquiry',
                'audience'  => 'Staff',
                'trigger'   => 'Automatic: someone submits a class\'s enrollment or inquiry form, or pays for a class by card through Fixr.',
                'recipient' => 'The admin address',
                'sample'    => fn () => new CourseInquiryMailer(self::sampleCourse()),
            ],
            'course-enrollment-confirmed' => [
                'name'      => 'Class enrollment confirmed',
                'audience'  => 'Staff',
                'trigger'   => 'Automatic: an enrollment is confirmed — paid by card through Fixr, marked confirmed in Admin → Classes, or added there already confirmed.',
                'recipient' => 'The class\'s instructor',
                'sample'    => fn () => new CourseEnrollmentConfirmedMailer(self::sampleCourse()),
            ],
            'checklist-overdue' => [
                'name'      => 'Box office checklist overdue',
                'audience'  => 'Staff',
                'trigger'   => 'Scheduled: checked every 5 minutes. Sent when Box Office Checklist to-dos go past their due time without being checked off — once per to-do, all new ones in one email.',
                'recipient' => 'The box office admin (MAIL_BOX_OFFICE_ADDRESS)',
                'sample'    => fn () => new \App\Mail\ChecklistOverdueMailer([[
                    'show' => self::sampleShowName(), 'label' => 'Door sheet exported and printed',
                    'performance' => now()->format('M j, Y') . ' 3:00 PM', 'due_label' => now()->format('D, M j, Y') . ' 12:00 PM',
                ]], url('/admin/box-office-checklist')),
            ],
            'sold-out-notification-mailer-template' => [
                'name'      => 'Performance sold out',
                'audience'  => 'Staff',
                'trigger'   => 'Manual: after a performance is marked sold out in Admin → Shows → Performances, the admin is asked whether to send it.',
                'recipient' => 'The Sold Out Notification recipients (and the show\'s director by default)',
                'sample'    => fn () => new SoldOutNotificationMailer(self::samplePerformance()),
            ],
            'contact-mailer-template' => [
                'name'      => 'Contact form message',
                'audience'  => 'Staff',
                'trigger'   => 'Automatic: someone sends the Contact form.',
                'recipient' => 'The admin address',
                'sample'    => function () {
                    $contact = new Contact([
                        'name' => 'Jane Smith', 'email' => 'jane@example.com', 'phone' => '+593 99 123 4567',
                        'subject' => 'A question', 'body' => 'When do auditions start?',
                    ]);
                    $contact->created_at = now();

                    return new ContactMailer($contact);
                },
            ],
            'message-us-mailer-template' => [
                'name'      => 'Message Us (other questions)',
                'audience'  => 'Staff',
                'trigger'   => 'Automatic: a patron sends the "Message Us" form from the Purchase Tickets page.',
                'recipient' => 'The admin address',
                'sample'    => fn () => new MessageUsMailer('jane@example.com', 'Is the theater wheelchair accessible?'),
            ],
            'volunteer-contact-mailer' => [
                'name'      => 'Volunteer inquiry',
                'audience'  => 'Staff',
                'trigger'   => 'Automatic: someone sends the form on the Volunteer page.',
                'recipient' => 'The volunteer coordinator address',
                'sample'    => fn () => new VolunteerContactMailer(
                    'Jane Smith', 'jane@example.com', '+593 99 123 4567',
                    Skill::limit(2)->get()->whenEmpty(fn () => collect([new Skill(['name' => 'Stage Manager'])]))
                ),
            ],
        ];
    }

    /**
     * Who gets a copy (CC) of a patron email — see App\Helpers\PatronMail.
     * Spelled out with the actual settings.
     */
    private static function ccNote(bool $ccAdmin = true): string
    {
        $cc = \App\Helpers\PatronMail::ccFor(null, $ccAdmin);

        return $cc
            ? " — a copy is CC'd to " . implode(' and ', $cc)
            : ' — no copy is CC\'d';
    }

    /**
     * Patron purchase options (standard_buttons keys) whose reservations get
     * the Ticket confirmation email with a details section of their own —
     * standard-buttons/{key}-confirmation.blade.php. (Flex has its own email;
     * Message Us isn't a purchase.)
     */
    private const CONFIRMATION_SECTIONS = ['paypal', 'transfer'];

    /**
     * The confirmation-details section of each of those purchase options,
     * inserted into the Ticket confirmation email.
     */
    private static function paymentSections(\Closure $ticket): array
    {
        $labels = \App\Models\StandardButton::whereIn('key', self::CONFIRMATION_SECTIONS)->pluck('label', 'key');
        $sections = [];

        foreach (self::CONFIRMATION_SECTIONS as $buttonKey) {
            $label = $labels[$buttonKey] ?? ucfirst($buttonKey);

            $sections["standard-buttons.{$buttonKey}-confirmation"] = [
                'name'      => "Ticket confirmation — {$label} details",
                'audience'  => 'Patron',
                'trigger'   => "Part of the Ticket confirmation email, when the patron chose \"{$label}\".",
                'recipient' => 'The purchaser',
                'notes'     => "Inserted into the Ticket confirmation email as \$confirmation_body. Switching this off stops the Ticket confirmation email for {$label} reservations.",
                'section'   => true,
                'sample'    => fn () => new PurchaseConfirmationMailer($ticket([
                    'view'         => 'purchase-confirmation',
                    'section_view' => "standard-buttons.{$buttonKey}-confirmation",
                ])),
            ];
        }

        return $sections;
    }

    // ── Template files ──────────────────────────────────────────────────

    public static function exists(string $key): bool
    {
        return array_key_exists($key, self::all());
    }

    private static function relativePath(string $key): string
    {
        return str_replace('.', '/', $key) . '.blade.php';
    }

    public static function defaultPath(string $key): string
    {
        return resource_path('views/' . self::relativePath($key));
    }

    public static function overridePath(string $key): string
    {
        return self::overrideRoot() . '/' . self::relativePath($key);
    }

    public static function isCustomized(string $key): bool
    {
        return File::exists(self::overridePath($key));
    }

    public static function defaultSource(string $key): string
    {
        $path = self::defaultPath($key);

        return File::exists($path) ? File::get($path) : '';
    }

    /** The template in use: the saved edit, otherwise the default. */
    public static function source(string $key): string
    {
        return self::isCustomized($key) ? File::get(self::overridePath($key)) : self::defaultSource($key);
    }

    public static function save(string $key, string $source): void
    {
        File::ensureDirectoryExists(dirname(self::overridePath($key)));
        File::put(self::overridePath($key), $source);
    }

    public static function reset(string $key): void
    {
        File::delete(self::overridePath($key));
    }

    // ── On / off ────────────────────────────────────────────────────────

    public static function isEnabled(string $key): bool
    {
        self::$enabled ??= EmailTemplate::pluck('enabled', 'key')->map(fn ($v) => (bool) $v)->all();

        return self::$enabled[$key] ?? true;
    }

    public static function setEnabled(string $key, bool $enabled): void
    {
        EmailTemplate::updateOrCreate(['key' => $key], ['enabled' => $enabled]);
        self::$enabled = null;
    }

    // ── Preview ─────────────────────────────────────────────────────────

    /**
     * The sample email rendered with $source (or the template in use) in
     * place of this template. Throws if the template doesn't render.
     *
     * @return array{subject: string, html: string}
     */
    public static function preview(string $key, ?string $source = null): array
    {
        /** @var Mailable $mailable */
        $mailable = self::all()[$key]['sample']();
        $source ??= self::source($key);

        $content = $mailable->content();
        $data = array_merge($mailable->buildViewData(), $content->with ?? []);

        $sectionView = $data['section_view'] ?? null;
        if ($sectionView === $key) {
            // A purchase option's section: render it, then the whole
            // Ticket confirmation around it.
            $data['confirmation_body'] = Blade::render($source, $data, true);
            $html = Blade::render(self::source($content->view), $data, true);
        } elseif ($sectionView) {
            $data['confirmation_body'] = Blade::render(self::source($sectionView), $data, true);
            $html = Blade::render($source, $data, true);
        } else {
            $html = Blade::render($source, $data, true);
        }

        return ['subject' => $mailable->envelope()->subject ?? '', 'html' => $html];
    }

    // ── Sample data ─────────────────────────────────────────────────────

    private static function sampleShowName(): string
    {
        return Show::latest('ticket_sales_start')->value('name') ?? 'Sample Show';
    }

    private static function samplePerformance(): Performance
    {
        return Performance::with('show')->latest('date')->first()
            ?? (new Performance(['date' => now()->addWeek()->toDateString(), 'start_time' => '15:00:00']))
                ->setRelation('show', new Show(['name' => 'Sample Show']));
    }

    private static function samplePatron(): Patron
    {
        return new Patron(['first_name' => 'Jane', 'last_name' => 'Smith', 'email' => 'jane@example.com', 'phone' => '+593 99 123 4567']);
    }

    private static function sampleFlexPackage(): PatronFlexPackage
    {
        $package = new PatronFlexPackage(['season' => '26-27', 'tickets_purchased' => 6]);
        $package->purchased_at = now();
        $package->setRelation('patron', self::samplePatron());
        $package->setRelation('paymentMethod', PaymentMethod::where('value', 'paypal')->first() ?? new PaymentMethod(['label' => 'PayPal']));

        return $package;
    }

    private static function sampleAngel(): Angel
    {
        $angel = new Angel([
            'first_name' => 'Jane', 'last_name' => 'Smith', 'email' => 'jane@example.com',
            'recognition_name' => 'The Smith Family', 'donation_amount' => 250, 'season' => '26-27',
        ]);
        $angel->created_at = now();
        $angel->setRelation('patron', self::samplePatron());
        $angel->setRelation('angelLevel', AngelLevel::orderBy('min_amount')->first() ?? new AngelLevel(['label' => 'Sample']));
        $angel->setRelation('paymentMethod', PaymentMethod::where('value', 'paypal')->first() ?? new PaymentMethod(['label' => 'PayPal']));

        return $angel;
    }

    private static function sampleCourse(): array
    {
        return [
            'course_name'          => 'Sample Class',
            'instructor_name'      => 'Sam Teacher',
            'first_name'           => 'Jane',
            'last_name'            => 'Smith',
            'email'                => 'jane@example.com',
            'phone'                => '+593 99 123 4567',
            'cost'                 => 40,
            'payment_method_label' => 'PayPal',
            'transaction_id'       => 'SAMPLE-1',
            'transfer_date'        => null,
            'questions'            => 'Is this suitable for beginners?',
            'confirmed'            => false,
        ];
    }
}
