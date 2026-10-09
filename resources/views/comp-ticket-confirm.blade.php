<div>
    <p>Dear {{ $name }},</p>

    <p>
        Your Comp Ticket request has been received. Your ticket will be held at the box office
        under the name <strong>{{ $pickup_name }}</strong> for the performance on
        <strong>{{ $performance_date }}</strong> at <strong>{{ $performance_time }}</strong>.
    </p>

    <p>
        Thank you for all of your hard work and <span style="color: red;"><strong>HAVE A GREAT SHOW</strong></span>.
    </p>

    @if (!empty($reference_number))
        <p>
            <strong>Reference number:</strong> {{ $reference_number }}<br>
            <span style="font-size: 12px; color: #757575;">For our records. If you contact us about this reservation, including it helps us find it quickly.</span>
        </p>
    @endif
</div>
