<div>
    <h2>New Ticket Sale</h2>

    <div>
        <strong>Show:</strong> {{ $ticketData['show'] }}
    </div>
    <div>
        <strong>Performance Date/Time:</strong>
        @if ($ticketData['performance'])
            {{ \Carbon\Carbon::parse($ticketData['performance'])->format('l, F j, Y \a\t g:i A T') }}
        @else
            Not available
        @endif
    </div>

    <p></p>

    <h3>Customer Information</h3>
    <div>
        <strong>Name:</strong> {{ $ticketData['first_name'] }} {{ $ticketData['last_name'] }}
    </div>
    <div>
        <strong>Email:</strong> <a href="mailto:{{ $ticketData['email'] }}">{{ $ticketData['email'] }}</a>
    </div>
    <div>
        <strong>Phone:</strong> {{ $ticketData['mobile_number'] }}
    </div>

    <div>
        <strong>Sold At:</strong> {{ \Carbon\Carbon::parse($ticketData['sold_at'])->format('M j, Y g:i A') }}
    </div>
</div>
