<div>
    {{-- The salutation lives here, outside the admin-editable confirmation
         body, so a custom body can't leave patrons without one. Skipped only
         when that body already opens with its own greeting. --}}
    @if (empty($confirmation_body) || !preg_match('/^\s*(hello|hi|dear|greetings)\b/iu', html_entity_decode(strip_tags($confirmation_body))))
        <p>Hello {{ $name }},</p>
    @endif

    @if (!empty($confirmation_body))
        {!! $confirmation_body !!}
    @else
        <p>
            This is to confirm your purchase of {{ $num_tickets }} ticket(s) to our {{ $performance_date }} performance of
            {{ $show_name }}, starting at {{ $performance_time }}.
        </p>

        <p>
            All sales are final. Cancellations and refunds are not allowed, but performance dates may be changed for the
            same
            production; subject to availability of the new date desired. Change requests must be made no later than 48 hours
            before the originally purchased performance date. ACT does not allow pets at our performances.
        </p>

        <p>
            The Lobby opens for our Social Hour 1 hour prior to the start of the show. We feature wine, beer, Coke and
            bottled water. We also have a variety of snacks (both sweet or salty) available.
        </p>

        <p>
            We are located on Antonio Vega Munoz between Coronel Talbot and Estevez Toral. Look for the ACT volunteer
            outside our location for directions to the theater entrance.
        </p>
    @endif

    @if (!empty($reference_number))
        <p>
            <strong>Reference number{{ str_contains($reference_number, ',') ? 's' : '' }}:</strong> {{ $reference_number }}<br>
            <span style="font-size: 12px; color: #757575;">For our records. If you contact us about this reservation, including {{ str_contains($reference_number, ',') ? 'them' : 'it' }} helps us find it quickly.</span>
        </p>
    @endif
</div>
