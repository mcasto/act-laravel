<div>
    <div>
        From: {{ $angel->recognition_name }} ({{ $angel->first_name }} {{ $angel->last_name }})
    </div>
    <p>
        Level: {{ $angel->angelLevel->label }}
    </p>
    <p>
        Amount: ${{ number_format($angel->donation_amount, 2) }}
    </p>
    <p>
        Payment Method: {{ $angel->paymentMethod?->label ?? 'Not specified' }}
    </p>
    <p>
        Season: {{ $angel->season }}
    </p>
    <div>
        Submitted: {{ $angel->created_at->format('M j, Y g:i A') }}
    </div>
</div>
