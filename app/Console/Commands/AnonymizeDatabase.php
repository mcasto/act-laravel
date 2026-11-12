<?php

namespace App\Console\Commands;

use App\Models\Patron;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Random\Engine\Mt19937;
use Random\Randomizer;

/**
 * Replaces every person's name, email address and phone number with made-up
 * ones — for a copy of the production database (e.g. preview) that people
 * other than the admins will see.
 *
 * Replacements are consistent: the same original name, email or phone is
 * replaced with the same fake one everywhere it appears (patrons, door names,
 * tickets, comps, angels, enrollments, mail logs, change logs, Fixr payloads
 * …), so lookups by email and name still match up the way they do on prod.
 * Fake emails are on example.com (reserved, never delivered); "no-email+…"
 * stand-ins stay stand-ins. Show credits (writer/director) are left alone, as
 * are free-text fields like comments and messages.
 *
 * Admin users get a fake name and an email of user{id}@example.com (their
 * password is unchanged) unless listed with --keep-user.
 *
 * Everything runs in one transaction; --dry-run rolls it back.
 */
class AnonymizeDatabase extends Command
{
    protected $signature = 'db:anonymize
        {--dry-run : Show what would change, then roll everything back}
        {--force : Skip the confirmation prompt (not allowed in production)}
        {--seed= : Random seed, for repeatable output}
        {--keep-user=* : An admin user\'s email to leave unchanged so they can still log in (repeatable)}';

    protected $description = 'Replace all names, email addresses and phone numbers with fake ones (for non-production copies of the database)';

    private const FIRST_NAMES = [
        'Alice', 'Arthur', 'Beatrice', 'Bernard', 'Carmen', 'Charles', 'Clara', 'Daniel', 'Diana', 'Edgar',
        'Elena', 'Felix', 'Fiona', 'George', 'Grace', 'Harold', 'Helen', 'Isaac', 'Iris', 'Jack',
        'Julia', 'Kenneth', 'Laura', 'Leonard', 'Lucia', 'Martin', 'Maria', 'Nathan', 'Nora', 'Oscar',
        'Olivia', 'Patrick', 'Paula', 'Quentin', 'Rachel', 'Robert', 'Rosa', 'Samuel', 'Sarah', 'Thomas',
        'Teresa', 'Victor', 'Vera', 'Walter', 'Wendy', 'Albert', 'Agnes', 'Benjamin', 'Cecilia', 'Douglas',
        'Eleanor', 'Frank', 'Gloria', 'Henry', 'Irene', 'James', 'Joan', 'Louis', 'Margaret', 'Norman',
        'Pamela', 'Raymond', 'Ruth', 'Stanley', 'Sylvia', 'Theodore', 'Ursula', 'Vincent', 'Wanda', 'Yvonne',
    ];

    private const LAST_NAMES = [
        'Abbott', 'Alvarez', 'Barker', 'Bennett', 'Calloway', 'Castillo', 'Dawson', 'Delgado', 'Ellison', 'Espinoza',
        'Fairbanks', 'Fletcher', 'Garrison', 'Guerrero', 'Hartley', 'Holloway', 'Ingram', 'Jennings', 'Keller', 'Kingsley',
        'Lambert', 'Lindqvist', 'Mendoza', 'Merritt', 'Norwood', 'Ochoa', 'Pemberton', 'Prescott', 'Quintero', 'Ramsey',
        'Rutherford', 'Salazar', 'Sinclair', 'Thornton', 'Townsend', 'Underwood', 'Valencia', 'Vaughn', 'Whitaker', 'Winslow',
        'Yates', 'Zamora', 'Ashford', 'Blackwell', 'Crawford', 'Donovan', 'Everett', 'Fenwick', 'Galloway', 'Hawthorne',
        'Ibarra', 'Jacobs', 'Kendrick', 'Langford', 'Maldonado', 'Nash', 'Osborne', 'Pacheco', 'Redford', 'Stanton',
        'Tolliver', 'Vasquez', 'Wakefield', 'Whitmore', 'Aldridge', 'Brennan', 'Cordero', 'Duarte', 'Easton', 'Ferris',
    ];

    /** Fixr payload keys holding a person's data, by kind. */
    private const PAYLOAD_KEYS = [
        'first_name' => 'first', 'last_name' => 'last', 'email' => 'email',
        'mobile_number' => 'phone', 'phone' => 'phone', 'phone_number' => 'phone',
    ];

    /** change_logs field => kind of value. */
    private const CHANGE_LOG_FIELDS = [
        'first_name' => 'first', 'last_name' => 'last', 'door_first' => 'first', 'door_last' => 'last',
        'recognition_name' => 'full', 'pickup_name' => 'full', 'instructor_name' => 'full',
        'email' => 'emails', 'instructor_email' => 'emails',
        'ticket_email' => 'emails', 'contact_email' => 'emails', 'dev_email' => 'emails',
        'phone' => 'phone',
    ];

    /** change_logs models whose text isn't about people — only emails, phones and known names are replaced. */
    private const NON_PERSON_MODELS = [
        'Show', 'Course', 'CourseSession', 'Audition', 'Performance', 'GalleryImage', 'EmailTemplate',
        'PaymentMethod', 'StandardButton', 'SiteConfig', 'UserPermission', 'ShowAccessGrant', 'AppPairingCode',
    ];

    /** Common first names, so names that appear only in free text are still found. */
    private const COMMON_FIRST_NAMES = [
        'Aaron', 'Adam', 'Adrian', 'Alan', 'Albert', 'Alejandro', 'Alex', 'Alexander', 'Alfred', 'Alice', 'Alicia', 'Allan', 'Allen', 'Alma', 'Amanda', 'Amber', 'Amy', 'Ana', 'Andrea', 'Andrew', 'Andy', 'Angela', 'Angie', 'Anita', 'Ann', 'Anna', 'Anne', 'Annette', 'Annie', 'Anthony', 'Antonio', 'April', 'Arlene', 'Arthur', 'Audrey', 'Barb', 'Barbara', 'Barry', 'Becky', 'Ben', 'Benjamin', 'Bernard', 'Bernie', 'Beth', 'Betsy', 'Betty', 'Bev', 'Beverly', 'Bill', 'Billy', 'Bob', 'Bobbie', 'Bobby', 'Bonnie', 'Brad', 'Bradley', 'Brenda', 'Brian', 'Bruce', 'Bryan', 'Carl', 'Carla', 'Carlos', 'Carmen', 'Carol', 'Carole', 'Caroline', 'Carolyn', 'Carrie', 'Catherine', 'Cathie', 'Cathy', 'Cecilia', 'Charles', 'Charlie', 'Charlotte', 'Cheri', 'Cheryl', 'Chris', 'Christina', 'Christine', 'Christopher', 'Chuck', 'Cindy', 'Claire', 'Clara', 'Claudia', 'Colleen', 'Connie', 'Craig', 'Cristina', 'Curt', 'Cynthia', 'Dale', 'Dan', 'Dana', 'Daniel', 'Danny', 'Darlene', 'Dave', 'David', 'Dawn', 'Dean', 'Deana', 'Deb', 'Debbie', 'Deborah', 'Debra', 'Denise', 'Dennis', 'Diana', 'Diane', 'Dianne', 'Dick', 'Diego', 'Dolores', 'Don', 'Donald', 'Donna', 'Doris', 'Dorothy', 'Doug', 'Douglas', 'Duane', 'Earl', 'Ed', 'Eddie', 'Edward', 'Eileen', 'Elaine', 'Eleanor', 'Elena', 'Eliana', 'Elizabeth', 'Ellen', 'Emily', 'Emma', 'Eric', 'Erica', 'Ernest', 'Esther', 'Eugene', 'Eva', 'Evelyn', 'Fernando', 'Fran', 'Frances', 'Francisco', 'Frank', 'Fred', 'Gail', 'Gary', 'Gene', 'George', 'Gerald', 'Gina', 'Ginny', 'Glen', 'Glenn', 'Gloria', 'Gordon', 'Greg', 'Gregory', 'Gwen', 'Hal', 'Hank', 'Harold', 'Harry', 'Heather', 'Helen', 'Henry', 'Herb', 'Holly', 'Howard', 'Irene', 'Jack', 'Jackie', 'Jacqueline', 'James', 'Jan', 'Jane', 'Janet', 'Janice', 'Jason', 'Jean', 'Jeanne', 'Jeff', 'Jeffrey', 'Jennifer', 'Jenny', 'Jerry', 'Jessica', 'Jill', 'Jim', 'Jimmy', 'Jo', 'Joan', 'Joann', 'Joanne', 'Joe', 'Joel', 'John', 'Johnny', 'Jon', 'Jonathan', 'Jorge', 'Jose', 'Joseph', 'Josh', 'Joyce', 'Juan', 'Judith', 'Judy', 'Julia', 'Julie', 'Karen', 'Karin', 'Karyn', 'Kate', 'Katherine', 'Kathleen', 'Kathryn', 'Kathy', 'Katie', 'Keith', 'Kelly', 'Ken', 'Kenneth', 'Kenny', 'Kevin', 'Kim', 'Kimberly', 'Kris', 'Kristen', 'Kristin', 'Larry', 'Laura', 'Laurie', 'Lee', 'Len', 'Leonard', 'Leslie', 'Linda', 'Lisa', 'Liz', 'Lois', 'Loren', 'Lori', 'Lorraine', 'Louis', 'Louise', 'Lucia', 'Lucy', 'Luis', 'Lynn', 'Lynne', 'Marc', 'Marcia', 'Margaret', 'Margie', 'Maria', 'Marian', 'Marie', 'Marilyn', 'Marion', 'Marjorie', 'Mark', 'Marsha', 'Martha', 'Martin', 'Mary', 'Matt', 'Matthew', 'Maureen', 'Maurice', 'Melissa', 'Michael', 'Michele', 'Michelle', 'Mike', 'Mildred', 'Monica', 'Nancee', 'Nancy', 'Nathan', 'Neil', 'Nick', 'Nicole', 'Norma', 'Norman', 'Pablo', 'Pam', 'Pamela', 'Pat', 'Patricia', 'Patrick', 'Patty', 'Paul', 'Paula', 'Pedro', 'Peggy', 'Penny', 'Pete', 'Peter', 'Phil', 'Philip', 'Phyllis', 'Rachel', 'Ralph', 'Randall', 'Randy', 'Ray', 'Raymond', 'Rebecca', 'Rex', 'Rich', 'Richard', 'Rick', 'Rita', 'Rob', 'Robert', 'Roberta', 'Robin', 'Roger', 'Ron', 'Ronald', 'Rosa', 'Rosemary', 'Ross', 'Roy', 'Russ', 'Russell', 'Ruth', 'Sally', 'Sam', 'Samuel', 'Sandra', 'Sandy', 'Sara', 'Sarah', 'Scott', 'Sean', 'Sharon', 'Sheila', 'Shirley', 'Stan', 'Stephanie', 'Stephen', 'Steve', 'Steven', 'Sue', 'Susan', 'Susanna', 'Suzanne', 'Sylvia', 'Ted', 'Teresa', 'Teri', 'Terri', 'Terry', 'Theresa', 'Thomas', 'Tim', 'Timothy', 'Tina', 'Todd', 'Tom', 'Tommy', 'Tony', 'Tracy', 'Valerie', 'Vera', 'Vicki', 'Vickie', 'Victor', 'Victoria', 'Vince', 'Virginia', 'Walt', 'Walter', 'Wanda', 'Wayne', 'Wendy', 'Wes', 'William', 'Willie', 'Yolanda',
    ];

    /** Capitalized words that are never taken as names, even when they're also someone's name. */
    private const STOP_WORDS = [
        'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December',
        'Jan', 'Feb', 'Mar', 'Apr', 'Jun', 'Jul', 'Aug', 'Sep', 'Sept', 'Oct', 'Nov', 'Dec',
        'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun',
        'I', "I'm", "I'll", "I've", "I'd", 'A', 'An', 'The', 'This', 'That', 'These', 'Those', 'It', 'Is', 'Are', 'Was', 'We', 'You', 'He', 'She', 'They', 'My', 'Our', 'Your', 'His', 'Her', 'Their',
        'Hi', 'Hello', 'Hey', 'Dear', 'Thank', 'Thanks', 'Please', 'Regards', 'Best', 'Cheers', 'Sincerely', 'Yes', 'No', 'Ok', 'Okay',
        'Will', 'Can', 'Could', 'Would', 'Should', 'Might', 'Must', 'Do', 'Does', 'Did', 'Have', 'Has', 'Had', 'If', 'When', 'What', 'Where', 'Why', 'How', 'Who', 'Is', 'And', 'But', 'Or', 'So', 'Also', 'Since', 'Just', 'Last', 'Next', 'Today', 'Tomorrow', 'Tonight',
        'Hope', 'Grace', 'Faith', 'Joy', 'Art', 'Rose', 'Daisy', 'Iris', 'Ivy', 'Dawn', 'Sunny', 'Summer', 'Winter', 'Autumn', 'Holly', 'Christmas', 'Easter', 'God', 'Lord',
        'Mr', 'Mrs', 'Ms', 'Miss', 'Dr', 'Sir', 'Madam', 'Señor', 'Señora', 'San', 'Santa', 'Saint',
        'Angel', 'Angels', 'Flex', 'Comp', 'Comped', 'Venmo', 'PayPal', 'Zelle', 'Fixr', 'WhatsApp', 'Google', 'Apple', 'Chrome', 'Safari', 'Firefox', 'MacBook', 'iPad', 'iPhone', 'Pro', 'Windows',
        'Cuenca', 'Ecuador', 'Azuay', 'Quito', 'Guayaquil', 'Loja', 'Panama', 'Boquete', 'USA', 'America', 'American', 'Canada', 'Canadian', 'English', 'Spanish', 'Español',
        'Community', 'Theater', 'Theatre', 'Players', 'Center', 'Centre', 'Row', 'Front', 'Door', 'Box', 'Office', 'Stage', 'Booth', 'Tech', 'Camp', 'Class', 'Show', 'Play', 'Ticket', 'Tickets', 'Seat', 'Seats',
        'Sample', 'Test', 'Testing', 'Demo', 'Example',
    ];

    /** Models whose change_logs "name" field is a person's name (not a show's, course's, …). */
    private const PERSON_NAME_MODELS = ['CompTicket', 'SoldOutNotificationRecipient', 'User', 'Ticket', 'Volunteer', 'Contact'];

    private Randomizer $rng;

    /** normalized original full name => [first, last] */
    private array $names = [];

    /** normalized original first (or last) name, when seen alone => fake */
    private array $firsts = [];
    private array $lasts = [];

    /** fake full names already handed out (lowercase) => true */
    private array $usedNames = [];

    /** normalized original email => fake */
    private array $emails = [];

    /** every email address in the database before we start, plus fakes handed out */
    private array $takenEmails = [];

    /** digits of original phone => fake */
    private array $phones = [];

    private array $usedPhones = [];

    private array $counts = [];

    public function handle(): int
    {
        $seed = $this->option('seed');
        $this->rng = new Randomizer(new Mt19937($seed !== null ? (int) $seed : null));

        $db = DB::connection();
        $where = $db->getDatabaseName().' on '.$db->getConfig('host');

        if (app()->isProduction()) {
            $this->error("APP_ENV is production. This will permanently replace every name, email and phone in {$where}.");
            $typed = $this->ask('If this really is a copy (e.g. preview), type the database name to continue');
            if ($typed !== $db->getDatabaseName()) {
                $this->info('Cancelled.');
                return self::FAILURE;
            }
        } elseif (! $this->option('dry-run') && ! $this->option('force')
            && ! $this->confirm("Replace every name, email and phone in {$where}? This can't be undone.")) {
            $this->info('Cancelled.');
            return self::FAILURE;
        }

        $keepUsers = collect($this->option('keep-user'))->map(fn ($e) => Patron::normalizeEmail($e))->all();
        $userLogins = [];

        DB::beginTransaction();

        try {
            $this->collectExistingEmails();
            $this->collectNameDictionary();
            $this->registerSiteEmails();

            $this->patrons();
            $userLogins = $this->users($keepUsers);
            $this->simpleTables();
            $this->ticketSalesAndTickets();
            $this->angels();
            $this->courses();
            $this->mailLogs();
            $this->fixrPayloads();
            // Last: these replace names found anywhere in text, so every
            // name needs to have been seen first.
            $this->changeLogs();
            $this->freeText();

            DB::table('password_reset_tokens')->delete();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        $this->table(['Table', 'Rows changed'], collect($this->counts)->map(fn ($n, $t) => [$t, $n])->values());

        if ($this->option('dry-run')) {
            DB::rollBack();
            $this->showSamples();
            $this->warn('Dry run: nothing was changed.');
            return self::SUCCESS;
        }

        DB::commit();
        Cache::flush(); // cached lists hold the old names

        if ($userLogins) {
            $this->newLine();
            $this->info('Admin logins are now (passwords unchanged):');
            $this->table(['User', 'Email'], $userLogins);
        }

        $this->info('Done. Names, emails and phone numbers have been replaced.');
        return self::SUCCESS;
    }

    // ---------------------------------------------------------------- tables

    private function patrons(): void
    {
        // Email local parts are tied to patron ids so they're unique even
        // when two patron records share a (fake) name.
        $extra = [];
        foreach (DB::table('patron_emails')->orderBy('id')->get() as $row) {
            $orig = Patron::normalizeEmail($row->email);
            if (! isset($this->emails[$orig])) {
                $n = $extra[$row->patron_id] = ($extra[$row->patron_id] ?? 0) + 1;
                $this->patronEmailFor($row->patron_id, $orig, $n);
            }
        }

        foreach (DB::table('patrons')->get() as $p) {
            [$first, $last] = $this->fakeName($p->first_name, $p->last_name);

            $email = $p->email;
            if ($this->isEmailish($email)) {
                $orig = Patron::normalizeEmail($email);
                $email = $this->emails[$orig] ?? $this->patronEmailFor(
                    $p->id, $orig, $extra[$p->id] = ($extra[$p->id] ?? 0) + 1, $first, $last
                );
            } elseif (! blank($email)) {
                $email = $this->fakeFull($email); // a name typed into the email field
            }

            $this->update('patrons', $p->id, [
                'first_name' => $first,
                'last_name' => $last,
                'email' => $email,
                'phone' => $this->fakePhone($p->phone),
            ]);
        }

        // Rename patron_emails in two passes so a new address never collides
        // (unique index) with one that hasn't been renamed yet.
        $rows = DB::table('patron_emails')->get();
        foreach ($rows as $row) {
            DB::table('patron_emails')->where('id', $row->id)->update(['email' => "tmp-{$row->id}@anonymize.invalid"]);
        }
        foreach ($rows as $row) {
            $this->update('patron_emails', $row->id, ['email' => $this->fakeEmail($row->email)]);
        }
    }

    private function users(array $keep): array
    {
        $logins = [];
        $rows = DB::table('users')->get();

        foreach ($rows as $u) {
            if (! in_array(Patron::normalizeEmail($u->email), $keep, true)) {
                DB::table('users')->where('id', $u->id)->update(['email' => "tmp-{$u->id}@anonymize.invalid"]);
            }
        }

        foreach ($rows as $u) {
            if (in_array(Patron::normalizeEmail($u->email), $keep, true)) {
                continue;
            }
            $name = $this->fakeFull($u->name);
            $email = "user{$u->id}@example.com";
            $this->update('users', $u->id, ['name' => $name, 'email' => $email]);
            $logins[] = [$name, $email];
        }

        return $logins;
    }

    /** Tables with a name, email and/or phone and no special handling. */
    private function simpleTables(): void
    {
        $tables = [
            'contacts' => ['name' => 'full', 'email' => 'email', 'phone' => 'phone'],
            'volunteers' => ['name' => 'full', 'email' => 'email', 'phone' => 'phone'],
            'comp_tickets' => ['name' => 'full', 'pickup_name' => 'full', 'email' => 'email'],
            'course_contacts' => ['first_name' => 'first', 'last_name' => 'last', 'email' => 'email', 'phone' => 'phone'],
            'pending_flex_redemptions' => ['first_name' => 'first', 'last_name' => 'last', 'phone' => 'phone'],
            'sold_out_notification_recipients' => ['name' => 'full', 'email' => 'email'],
            'message_us_submissions' => ['email' => 'email'],
            'reservation_reminders' => ['email' => 'email'],
            'project_charters' => ['sponsor' => 'full', 'commitment_signature' => 'full'],
        ];

        foreach ($tables as $table => $columns) {
            foreach (DB::table($table)->get() as $row) {
                $this->update($table, $row->id, $this->fakeColumns($row, $columns));
            }
        }
    }

    private function ticketSalesAndTickets(): void
    {
        foreach (DB::table('ticket_sales')->get(['id', 'door_first', 'door_last']) as $row) {
            if (blank($row->door_first) && blank($row->door_last)) {
                continue;
            }
            [$first, $last] = $this->fakeName($row->door_first, $row->door_last);
            $this->update('ticket_sales', $row->id, [
                'door_first' => blank($row->door_first) ? $row->door_first : $first,
                'door_last' => blank($row->door_last) ? $row->door_last : $last,
            ]);
        }

        foreach (DB::table('tickets')->get(['id', 'name']) as $row) {
            // Some tickets are named by number ("002") — leave those.
            if (preg_match('/\pL/u', (string) $row->name)) {
                $this->update('tickets', $row->id, ['name' => $this->fakeFull($row->name)]);
            }
        }
    }

    private function angels(): void
    {
        foreach (DB::table('angels')->get() as $a) {
            [$first, $last] = $this->fakeName($a->first_name, $a->last_name);
            $this->update('angels', $a->id, [
                'first_name' => blank($a->first_name) ? $a->first_name : $first,
                'last_name' => blank($a->last_name) ? $a->last_name : $last,
                // Usually "First Last", which fakeFull() maps to the same fake
                // name; couples etc. get a name of their own.
                'recognition_name' => $this->fakeFull($a->recognition_name),
                'email' => $this->fakeEmail($a->email),
            ]);
        }
    }

    private function courses(): void
    {
        foreach (DB::table('courses')->get(['id', 'instructor_name', 'instructor_email']) as $c) {
            $this->update('courses', $c->id, [
                'instructor_name' => $this->fakeFull($c->instructor_name),
                'instructor_email' => $this->fakeEmailList($c->instructor_email),
            ]);
        }
    }

    private function mailLogs(): void
    {
        foreach (DB::table('mail_logs')->get(['id', 'to', 'cc', 'bcc']) as $m) {
            $this->update('mail_logs', $m->id, [
                'to' => $this->fakeEmailList($m->to),
                'cc' => $this->fakeEmailList($m->cc),
                'bcc' => $this->fakeEmailList($m->bcc),
            ]);
        }
    }

    private function changeLogs(): void
    {
        foreach (DB::table('change_logs')->get(['id', 'model_type', 'description', 'changes']) as $log) {
            $fields = self::CHANGE_LOG_FIELDS;
            if (in_array($log->model_type, self::PERSON_NAME_MODELS, true)) {
                $fields['name'] = 'full';
            }

            // Shows, courses etc. keep their text (cast lists and credits stay
            // real in those tables too) apart from emails, phones and known names.
            $guess = $log->model_type === null || ! in_array($log->model_type, self::NON_PERSON_MODELS, true);

            $changes = json_decode((string) $log->changes, true);

            $this->update('change_logs', $log->id, [
                'description' => $this->scrubText($log->description, $guess),
                'changes' => is_array($changes)
                    ? json_encode($this->scrubChanges($changes, $fields, $guess))
                    : $this->scrubText($log->changes, $guess),
            ]);
        }
    }

    /**
     * Walks a change log's JSON — {field: value}, {field: {old, new}}, or
     * nested snapshots like {old: {...}, new: {...}} and {sent: [emails]}.
     * Known person fields are faked by kind; any other text is scrubbed.
     */
    private function scrubChanges(array $data, array $fields, bool $guess, ?string $kind = null): array
    {
        foreach ($data as $key => $value) {
            $valueKind = is_string($key) && isset($fields[$key]) ? $fields[$key] : $kind;

            if (is_array($value)) {
                $data[$key] = $this->scrubChanges($value, $fields, $guess, $valueKind);
            } elseif (is_string($value)) {
                $data[$key] = $valueKind ? $this->fakeValue($value, $valueKind) : $this->scrubText($value, $guess);
            }
        }

        return $data;
    }

    /** Comments, messages and notes: emails, phone numbers and known names inside the text. */
    private function freeText(): void
    {
        $columns = [
            'patrons' => ['comments'],
            'ticket_sales' => ['comments', 'special_seating', 'reason_changed'],
            'angels' => ['comments'],
            'patron_flex_packages' => ['comments'],
            'contacts' => ['subject', 'body'],
            'message_us_submissions' => ['body'],
            'course_contacts' => ['questions'],
            'pending_flex_redemptions' => ['special_request'],
            'volunteers' => ['experience'],
            'mail_logs' => ['subject'],
            'reservation_reminders' => ['error'],
            'project_charters' => ['description', 'team_members', 'community_partners', 'leadership_reflection', 'admin_notes'],
        ];

        foreach ($columns as $table => $cols) {
            foreach (DB::table($table)->get(array_merge(['id'], $cols)) as $row) {
                $values = [];
                foreach ($cols as $col) {
                    $values[$col] = $this->scrubText($row->$col);
                }
                $this->update($table, $row->id, $values);
            }
        }
    }

    private ?string $namePattern = null;

    private array $nameLookup = [];

    /** lowercase first / last names that, capitalized in free text, are taken to be a person's */
    private array $firstNameSet = [];
    private array $lastNameSet = [];

    /** lowercase capitalized words never treated as names (show titles, months, places, …) */
    private array $stopWords = [];

    /**
     * Emails, phone numbers and names inside free text. Names are found two
     * ways: full names of anyone in the database (exact match), then — when
     * $guess — any capitalized word that is a known first name (from the
     * database or a list of common names; with the capitalized word after
     * it as a surname) or a surname from the database.
     */
    private function scrubText(?string $text, bool $guess = true): ?string
    {
        if (blank($text)) {
            return $text;
        }

        // Emails
        $text = $this->fakeEmailList($text);

        // Phone numbers: starts with + or 0, 9+ digits (dates and ids don't match)
        $text = preg_replace_callback(
            '/(?<![\w+])(?:\+|0)[\d\s().-]{7,}\d(?!\w)/',
            fn ($m) => strlen(preg_replace('/\D/', '', $m[0])) >= 9 ? $this->fakePhone($m[0]) : $m[0],
            $text
        );

        // Full names of anyone seen elsewhere in the database. Replacements
        // are parked behind placeholders so the guessing below can't re-fake them.
        if ($this->namePattern === null) {
            $this->nameLookup = [];
            foreach ($this->names as $orig => [$first, $last]) {
                if (mb_strlen($orig) >= 5 && str_contains($orig, ' ')) {
                    $this->nameLookup[$orig] = trim("$first $last");
                }
            }
            $alts = array_keys($this->nameLookup);
            usort($alts, fn ($a, $b) => mb_strlen($b) <=> mb_strlen($a)); // longest first
            $alts = array_map(fn ($n) => str_replace(' ', '\s+', preg_quote($n, '/')), $alts);
            $this->namePattern = $alts ? '/(?<!\pL)(?:'.implode('|', $alts).')(?!\pL)/iu' : '';
        }

        $parked = [];
        if ($this->namePattern !== '') {
            $text = preg_replace_callback($this->namePattern, function ($m) use (&$parked) {
                $parked[] = $this->nameLookup[$this->nameKey($m[0])] ?? $m[0];
                return "\x01".(count($parked) - 1)."\x02";
            }, $text);
        }

        if ($guess) {
            $text = $this->guessNames($text);
        }

        return preg_replace_callback("/\x01(\d+)\x02/", fn ($m) => $parked[$m[1]], $text);
    }

    private function guessNames(string $text): string
    {
        // Odd indexes are words, even indexes the text between them.
        $parts = preg_split("/(\pL[\pL'’-]*)/u", $text, -1, PREG_SPLIT_DELIM_CAPTURE);

        for ($i = 1; $i < count($parts); $i += 2) {
            [$word, $suffix] = $this->splitPossessive($parts[$i]);
            if (! $this->isCapitalized($word)) {
                continue;
            }
            $key = mb_strtolower($word);

            if (isset($this->firstNameSet[$key])) {
                // "Jill Sare", "Cathie  Armstrong-Moore" — first name + capitalized word after it
                $next = $parts[$i + 2] ?? null;
                if ($next !== null && $suffix === '' && preg_match('/^[ \t]+$/u', $parts[$i + 1])) {
                    [$nextWord, $nextSuffix] = $this->splitPossessive($next);
                    if ($this->isCapitalized($nextWord) && ! isset($this->stopWords[mb_strtolower($nextWord)])) {
                        [$first, $last] = explode(' ', $this->fakeFull("$word $nextWord"), 2) + [1 => ''];
                        $parts[$i] = $first;
                        $parts[$i + 2] = $last.$nextSuffix;
                        $i += 2;
                        continue;
                    }
                }
                $parts[$i] = $this->fakeSingle($word, 'first').$suffix;
            } elseif (isset($this->lastNameSet[$key])) {
                $parts[$i] = $this->fakeSingle($word, 'last').$suffix;
            }
        }

        return implode('', $parts);
    }

    /** "Sue's" => ["Sue", "'s"] */
    private function splitPossessive(string $word): array
    {
        return preg_match("/^(.+?)(['’]s)$/u", $word, $m) ? [$m[1], $m[2]] : [$word, ''];
    }

    /** "Jane", "O'Neil", "Armstrong-Moore" — not "jane", "ACT" or a stop word. */
    private function isCapitalized(string $word): bool
    {
        return mb_strlen($word) >= 2
            && preg_match('/^\p{Lu}/u', $word)
            && preg_match('/\p{Ll}/u', $word)
            && ! isset($this->stopWords[mb_strtolower($word)]);
    }

    /** First and last names as they were before anything changed, plus words that are never names. */
    private function collectNameDictionary(): void
    {
        $add = function (array &$set, ?string $value, int $min = 2) {
            foreach (preg_split("/[^\pL'’-]+/u", (string) $value, -1, PREG_SPLIT_NO_EMPTY) as $w) {
                $w = mb_strtolower(trim($w, "'’-"));
                if (mb_strlen($w) >= $min && ! in_array($w, ['and', 'the', 'of', 'de', 'del', 'la', 'van', 'von'], true)) {
                    $set[$w] = true;
                }
            }
        };

        foreach (self::COMMON_FIRST_NAMES as $n) {
            $this->firstNameSet[mb_strtolower($n)] = true;
        }

        $pairs = [
            ['patrons', 'first_name', 'last_name'], ['course_contacts', 'first_name', 'last_name'],
            ['ticket_sales', 'door_first', 'door_last'], ['angels', 'first_name', 'last_name'],
            ['pending_flex_redemptions', 'first_name', 'last_name'],
        ];
        foreach ($pairs as [$table, $f, $l]) {
            foreach (DB::table($table)->get([$f, $l]) as $row) {
                $add($this->firstNameSet, $row->$f);
                $add($this->lastNameSet, $row->$l, 3);
            }
        }

        // Single "name" columns: first word is a first name, last word a surname.
        $fulls = [
            'users' => 'name', 'comp_tickets' => 'pickup_name', 'sold_out_notification_recipients' => 'name',
            'contacts' => 'name', 'volunteers' => 'name', 'tickets' => 'name', 'courses' => 'instructor_name',
        ];
        foreach ($fulls as $table => $col) {
            foreach (DB::table($table)->whereNotNull($col)->pluck($col) as $name) {
                $words = preg_split('/\s+/', trim($name), -1, PREG_SPLIT_NO_EMPTY);
                if (count($words) >= 2) {
                    $add($this->firstNameSet, $words[0]);
                    $add($this->lastNameSet, end($words), 3);
                }
            }
        }

        // Words from titles and labels are never names ("Driving Miss Daisy", "Angel").
        foreach (self::STOP_WORDS as $w) {
            $this->stopWords[mb_strtolower($w)] = true;
        }
        $titles = [
            'shows' => 'name', 'courses' => 'name', 'payment_methods' => 'label', 'angel_levels' => 'label',
            'skills' => 'name', 'standard_buttons' => 'label', 'checklist_items' => 'label',
        ];
        foreach ($titles as $table => $col) {
            foreach (DB::table($table)->pluck($col) as $title) {
                $add($this->stopWords, $title);
            }
        }
        foreach (array_keys($this->stopWords) as $w) {
            unset($this->firstNameSet[$w], $this->lastNameSet[$w]);
        }
    }

    private function fixrPayloads(): void
    {
        foreach (DB::table('fixr_webhook_responses')->get(['id', 'payload']) as $r) {
            $payload = json_decode((string) $r->payload, true);
            if (! is_array($payload)) {
                continue;
            }
            $this->update('fixr_webhook_responses', $r->id, ['payload' => json_encode($this->scrubPayload($payload))]);
        }
    }

    /** Fakes person fields anywhere in a Fixr payload (e.g. ticket_holders[]). */
    private function scrubPayload(array $data): array
    {
        if (isset($data['first_name']) || isset($data['last_name'])) {
            [$first, $last] = $this->fakeName($data['first_name'] ?? '', $data['last_name'] ?? '');
            if (isset($data['first_name'])) {
                $data['first_name'] = $first;
            }
            if (isset($data['last_name'])) {
                $data['last_name'] = $last;
            }
        }
        if (array_key_exists('date_of_birth', $data) && $data['date_of_birth']) {
            $data['date_of_birth'] = '1970-01-01';
        }

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->scrubPayload($value);
            } elseif (in_array(self::PAYLOAD_KEYS[$key] ?? null, ['email', 'phone'], true)) {
                $data[$key] = $this->fakeValue($value, self::PAYLOAD_KEYS[$key] === 'email' ? 'emails' : 'phone');
            }
        }

        return $data;
    }

    // ------------------------------------------------------------- mapping

    private function fakeColumns(object $row, array $columns): array
    {
        $out = [];

        // first/last pairs are mapped together so "Jane" + "Smith" matches "Jane Smith" elsewhere.
        $firstCol = array_search('first', $columns, true);
        $lastCol = array_search('last', $columns, true);
        if ($firstCol !== false && $lastCol !== false) {
            [$first, $last] = $this->fakeName($row->$firstCol, $row->$lastCol);
            $out[$firstCol] = blank($row->$firstCol) ? $row->$firstCol : $first;
            $out[$lastCol] = blank($row->$lastCol) ? $row->$lastCol : $last;
        }

        foreach ($columns as $col => $kind) {
            if (! array_key_exists($col, $out)) {
                $out[$col] = $this->fakeValue($row->$col, $kind === 'email' ? 'emails' : $kind);
            }
        }

        return $out;
    }

    private function fakeValue(mixed $value, string $kind): mixed
    {
        if (! is_string($value) || trim($value) === '') {
            return $value;
        }

        return match ($kind) {
            'full' => $this->fakeFull($value),
            'first' => $this->fakeSingle($value, 'first'),
            'last' => $this->fakeSingle($value, 'last'),
            'emails' => $this->fakeEmailList($value),
            'phone' => $this->fakePhone($value),
        };
    }

    /** [first, last] for a person, the same every time the same name comes up. */
    private function fakeName(?string $first, ?string $last): array
    {
        $key = $this->nameKey(trim("{$first} {$last}"));

        if ($key === '') {
            return [$first, $last];
        }

        if (! isset($this->names[$key])) {
            $this->names[$key] = $this->newName();
            // Remember the halves, for fields that hold only one of them.
            $this->firsts[$this->nameKey($first)] ??= $this->names[$key][0];
            $this->lasts[$this->nameKey($last)] ??= $this->names[$key][1];
        }

        return $this->names[$key];
    }

    private function fakeFull(?string $name): ?string
    {
        if (blank($name)) {
            return $name;
        }

        $key = $this->nameKey($name);
        if (! isset($this->names[$key])) {
            // Split "Jane Q. Smith" as first="Jane Q.", last="Smith" so the halves line up with first/last fields.
            $parts = preg_split('/\s+/', trim($name));
            $last = count($parts) > 1 ? array_pop($parts) : '';

            return implode(' ', array_filter($this->fakeName(implode(' ', $parts), $last)));
        }

        return implode(' ', $this->names[$key]);
    }

    private function fakeSingle(string $value, string $which): string
    {
        $map = $which === 'first' ? 'firsts' : 'lasts';
        $key = $this->nameKey($value);

        if (isset($this->{$map}[$key])) {
            return $this->{$map}[$key];
        }

        // Prefer a fake no other name has been given, so two people in
        // one comment ("Nancee & Kevin") don't come out with the same name.
        $list = $which === 'first' ? self::FIRST_NAMES : self::LAST_NAMES;
        $taken = array_flip($this->{$map});
        for ($i = 0; $i < 50; $i++) {
            $fake = $list[$this->rng->getInt(0, count($list) - 1)];
            if (! isset($taken[$fake])) {
                break;
            }
        }

        return $this->{$map}[$key] = $fake;
    }

    /** A first/last pair no one else has been given. */
    private function newName(): array
    {
        for ($i = 0; $i < 1000; $i++) {
            $first = self::FIRST_NAMES[$this->rng->getInt(0, count(self::FIRST_NAMES) - 1)];
            $last = self::LAST_NAMES[$this->rng->getInt(0, count(self::LAST_NAMES) - 1)];
            $full = strtolower("$first $last");
            if (! isset($this->usedNames[$full])) {
                $this->usedNames[$full] = true;
                return [$first, $last];
            }
        }

        // Out of unique combinations — add a middle initial.
        return [$first.' '.chr(65 + $this->rng->getInt(0, 25)).'.', $last];
    }

    private function patronEmailFor(int $patronId, string $orig, int $n, ?string $first = null, ?string $last = null): string
    {
        if (str_starts_with($orig, 'no-email+')) {
            return $this->emails[$orig] = $this->uniqueEmail('no-email+patron'.$patronId.($n > 1 ? "-$n" : ''), 'address.com');
        }

        if ($first === null) {
            $p = DB::table('patrons')->where('id', $patronId)->first(['first_name', 'last_name']);
            [$first, $last] = $p ? $this->fakeName($p->first_name, $p->last_name) : $this->newName();
        }

        $local = $this->slug($first).'.'.$this->slug($last).'.'.$patronId.($n > 1 ? ".$n" : '');

        return $this->emails[$orig] = $this->uniqueEmail($local);
    }

    /** One email (keeps original casing rules out of it — fakes are lowercase). */
    private function fakeEmail(?string $email): ?string
    {
        if (blank($email)) {
            return $email;
        }

        $orig = Patron::normalizeEmail($email);

        if (! isset($this->emails[$orig])) {
            if (str_starts_with($orig, 'no-email+')) {
                return $this->emails[$orig] = $this->uniqueEmail('no-email+'.$this->slug(implode(' ', $this->newName())), 'address.com');
            }
            [$first, $last] = $this->newName();
            $this->emails[$orig] = $this->uniqueEmail($this->slug($first).'.'.$this->slug($last));
        }

        return $this->emails[$orig];
    }

    /** A field that may hold several addresses ("a@x.com, b@y.com"). */
    private function fakeEmailList(?string $value): ?string
    {
        if (blank($value)) {
            return $value;
        }

        return preg_replace_callback(
            '/[^\s,;<>"\']+@[^\s,;<>"\']+/',
            fn ($m) => $this->fakeEmail($m[0]),
            $value
        );
    }

    private function uniqueEmail(string $local, string $domain = 'example.com'): string
    {
        $email = "{$local}@{$domain}";
        for ($i = 2; isset($this->takenEmails[$email]); $i++) {
            $email = "{$local}{$i}@{$domain}";
        }
        $this->takenEmails[$email] = true;

        return $email;
    }

    private function fakePhone(?string $phone): ?string
    {
        $digits = preg_replace('/\D/', '', (string) $phone);
        if ($digits === '') {
            return $phone;
        }

        if (! isset($this->phones[$digits])) {
            do {
                $fake = sprintf('+593 99 %03d %04d', $this->rng->getInt(0, 999), $this->rng->getInt(0, 9999));
            } while (isset($this->usedPhones[$fake]));
            $this->usedPhones[$fake] = true;
            $this->phones[$digits] = $fake;
        }

        return $this->phones[$digits];
    }

    // -------------------------------------------------------------- setup

    /** Every address already in the database, so a fake one never collides with a not-yet-replaced original. */
    private function collectExistingEmails(): void
    {
        $sources = [
            'patrons' => ['email'], 'patron_emails' => ['email'], 'users' => ['email'], 'angels' => ['email'],
            'comp_tickets' => ['email'], 'contacts' => ['email'], 'volunteers' => ['email'],
            'course_contacts' => ['email'], 'sold_out_notification_recipients' => ['email'],
            'message_us_submissions' => ['email'], 'reservation_reminders' => ['email'],
        ];

        foreach ($sources as $table => $columns) {
            foreach ($columns as $col) {
                foreach (DB::table($table)->whereNotNull($col)->pluck($col) as $e) {
                    $this->takenEmails[Patron::normalizeEmail($e)] = true;
                }
            }
        }
    }

    /** The theater's own addresses get recognizable fakes (and stay consistent in mail logs). */
    private function registerSiteEmails(): void
    {
        $fakes = ['ticket_email' => 'tickets', 'contact_email' => 'contact', 'dev_email' => 'dev'];

        foreach (DB::table('site_configs')->get() as $row) {
            $update = [];
            foreach ($fakes as $col => $local) {
                if (blank($row->$col)) {
                    continue;
                }
                $orig = Patron::normalizeEmail($row->$col);
                $this->emails[$orig] ??= $this->uniqueEmail($local);
                $update[$col] = $this->emails[$orig];
            }
            $this->update('site_configs', $row->id, $update);
        }
    }

    // ------------------------------------------------------------- helpers

    private function update(string $table, int $id, array $values): void
    {
        if (! $values) {
            return;
        }

        $changed = DB::table($table)->where('id', $id)->update($values);
        $this->counts[$table] = ($this->counts[$table] ?? 0) + $changed;
    }

    private function isEmailish(?string $value): bool
    {
        return str_contains((string) $value, '@');
    }

    private function nameKey(?string $name): string
    {
        return strtolower(preg_replace('/\s+/', ' ', trim((string) $name)));
    }

    private function slug(string $value): string
    {
        return trim(preg_replace('/[^a-z0-9]+/', '-', strtolower($value)), '-');
    }

    private function showSamples(): void
    {
        $this->newLine();
        $this->info('Sample name replacements:');
        $this->table(['Original', 'Fake'], collect($this->names)->take(8)->map(fn ($v, $k) => [$k, implode(' ', $v)])->values());
        $this->info('Sample email replacements:');
        $this->table(['Original', 'Fake'], collect($this->emails)->take(8)->map(fn ($v, $k) => [$k, $v])->values());
    }
}
