<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; color: #333;">

    <div style="background-color: #e8eaf6; padding: 24px 32px; border-radius: 4px 4px 0 0; border-bottom: 3px solid #1a237e;">
        <h1 style="margin: 0; color: #1a237e; font-size: 22px; font-weight: 600;">
            Flex Ticket Purchase Confirmed
        </h1>
        <p style="margin: 6px 0 0; color: #3949ab; font-size: 14px;">
            Azuay Community Theater
        </p>
    </div>

    <div style="background-color: #f9f9f9; padding: 28px 32px; border: 1px solid #e0e0e0; border-top: none;">

        <p style="font-size: 16px; margin: 0 0 20px;">
            Hello {{ $package->patron->first_name }},
        </p>

        <p style="font-size: 15px; line-height: 1.6; margin: 0 0 16px;">
            This confirms your purchase of <strong>{{ $package->tickets_purchased }} Flex ticket(s)</strong>
            for our {{ $package->season }} season.
        </p>

        <p style="font-size: 15px; line-height: 1.6; margin: 0 0 16px;">
            To use your Flex tickets, simply email
            <a href="mailto:{{ config('mail.admin_to.address') }}">{{ config('mail.admin_to.address') }}</a>
            with your reservation request(s) prior to any performance you'd like to attend. We'll keep track of
            your remaining balance for you.
        </p>

        <p style="font-size: 15px; line-height: 1.6; margin: 20px 0 0;">
            If you have any questions, feel free to reply to this email.
        </p>

    </div>

    <div style="background-color: #eeeeee; padding: 14px 32px; border-radius: 0 0 4px 4px; font-size: 12px; color: #888; text-align: center;">
        Azuay Community Theater &mdash; Cuenca, Ecuador
    </div>

</div>
