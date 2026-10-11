<div>
    <p>Hello {{ $name }},</p>

    <p>
        We received a request to use {{ $num_tickets }} of your FLEX ticket(s) for our {{ $performance_date }}
        performance of {{ $show_name }}, starting at {{ $performance_time }}.
    </p>

    <p>
        <strong>Your tickets are not reserved yet.</strong> To confirm this request, please
        <a href="{{ $link }}">click here</a> and then press the Confirm button.
    </p>

    <p style="text-align: center; margin: 24px 0;">
        <a href="{{ $link }}" style="color: #1976d2; font-weight: bold;">
            Confirm My Flex Tickets
        </a>
    </p>

    <p>
        If the link is not clickable, copy and paste the following URL into your browser:
        <br>{{ $link }}
    </p>

    <p>
        This link expires in 48 hours, or when the performance starts, whichever comes first.
    </p>

    <p>
        If you didn't make this request, you can ignore this email. No tickets will be used unless you confirm.
    </p>
</div>
