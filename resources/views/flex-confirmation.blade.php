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
            This is to confirm the redemption of {{ $num_tickets }} FLEX ticket(s) to our {{ $performance_date }}
            performance of {{ $show_name }}, starting at {{ $performance_time }}. You have {{ $remaining_flex }} Flex
            ticket(s) remaining to be used during our {{ $season }} season.
        </p>

        <p>
            The theater has limited seating capacity so we ask that you provide us 48 hours notice if you should need to
            cancel your reservation, or your ticket for this performance will be considered used. ACT does not allow pets
            at our performances.
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
