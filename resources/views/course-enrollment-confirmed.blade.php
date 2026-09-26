<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; color: #333;">

    <div style="background-color: #2e7d32; padding: 24px 32px; border-radius: 4px 4px 0 0;">
        <h1 style="margin: 0; color: #ffffff; font-size: 22px; font-weight: 600;">
            Enrollment Confirmed
        </h1>
        <p style="margin: 6px 0 0; color: #c8e6c9; font-size: 14px;">
            {{ $data['course_name'] }}
        </p>
    </div>

    <div style="background-color: #f9f9f9; padding: 28px 32px; border: 1px solid #e0e0e0; border-top: none;">

        <p style="font-size: 15px; line-height: 1.6; margin: 0 0 20px;">
            This enrollment's payment has been confirmed — safe to add to your attendance list.
        </p>

        <h2 style="margin: 0 0 16px; font-size: 16px; color: #2e7d32; text-transform: uppercase; letter-spacing: 0.5px;">
            Attendee
        </h2>

        <table style="width: 100%; border-collapse: collapse; font-size: 15px;">
            <tr>
                <td style="padding: 8px 0; width: 130px; color: #666; vertical-align: top;">Name</td>
                <td style="padding: 8px 0; font-weight: 600;">{{ $data['first_name'] }} {{ $data['last_name'] }}</td>
            </tr>
            <tr>
                <td style="padding: 8px 0; color: #666; vertical-align: top;">Email</td>
                <td style="padding: 8px 0;">
                    <a href="mailto:{{ $data['email'] }}" style="color: #2e7d32;">{{ $data['email'] }}</a>
                </td>
            </tr>
            <tr>
                <td style="padding: 8px 0; color: #666; vertical-align: top;">Phone</td>
                <td style="padding: 8px 0;">{{ $data['phone'] }}</td>
            </tr>
        </table>

        @if (!empty($data['payment_method_label']))
            <hr style="border: none; border-top: 1px solid #e0e0e0; margin: 20px 0;">

            <h2 style="margin: 0 0 16px; font-size: 16px; color: #2e7d32; text-transform: uppercase; letter-spacing: 0.5px;">
                Payment Information
            </h2>

            <table style="width: 100%; border-collapse: collapse; font-size: 15px;">
                <tr>
                    <td style="padding: 8px 0; width: 130px; color: #666; vertical-align: top;">Payment Method</td>
                    <td style="padding: 8px 0;">{{ $data['payment_method_label'] }}</td>
                </tr>
                @if (!empty($data['transaction_id']))
                    <tr>
                        <td style="padding: 8px 0; color: #666; vertical-align: top;">Reference</td>
                        <td style="padding: 8px 0;">{{ $data['transaction_id'] }}</td>
                    </tr>
                @endif
            </table>
        @endif

    </div>

    <div style="background-color: #eeeeee; padding: 14px 32px; border-radius: 0 0 4px 4px; font-size: 12px; color: #888; text-align: center;">
        Reply directly to this email to reach {{ $data['first_name'] }}.
    </div>

</div>
