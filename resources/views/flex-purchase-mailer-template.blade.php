<div>
    <div>
        From: {{ $package->patron->first_name }} {{ $package->patron->last_name }}
    </div>
    <p>
        Email: <a href="mailto:{{ $package->patron->email }}">{{ $package->patron->email }}</a>
    </p>
    <p>
        Tickets Purchased: {{ $package->tickets_purchased }}
    </p>
    <p>
        Payment Method: {{ $package->paymentMethod?->label ?? 'Not specified' }}
    </p>
    <p>
        Season: {{ $package->season }}
    </p>
    <div>
        Purchased: {{ $package->purchased_at->format('M j, Y g:i A') }}
    </div>
</div>
