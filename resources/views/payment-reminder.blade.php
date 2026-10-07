<div>
    <p>Hello {{ $name }},</p>

    {{-- Admin-typed plain text — escaped, with line breaks kept. --}}
    <p>{!! nl2br(e($body)) !!}</p>

    <p><strong>Your reservation{{ count($reservations) > 1 ? 's' : '' }}:</strong></p>
    <ul>
        @foreach ($reservations as $reservation)
            <li>
                {{ $reservation['show_name'] }} — {{ $reservation['performance_date'] }} at
                {{ $reservation['performance_time'] }}, {{ $reservation['num_tickets'] }} ticket(s),
                paying by {{ $reservation['payment_method'] }}
                @if (!empty($reservation['reference_number']))
                    (reference number {{ $reservation['reference_number'] }})
                @endif
            </li>
        @endforeach
    </ul>

    <p style="font-size: 12px; color: #757575;">
        Reference numbers are for our records. If you contact us about a reservation, including them helps us find it
        quickly.
    </p>
</div>
