<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; color: #333;">

    <div style="background-color: #e8eaf6; padding: 24px 32px; border-radius: 4px 4px 0 0; border-bottom: 3px solid #1a237e;">
        <h1 style="margin: 0; color: #1a237e; font-size: 22px; font-weight: 600;">
            Thank You for Enrolling
        </h1>
        <p style="margin: 6px 0 0; color: #3949ab; font-size: 14px;">
            Azuay Community Theater
        </p>
    </div>

    <div style="background-color: #f9f9f9; padding: 28px 32px; border: 1px solid #e0e0e0; border-top: none;">

        <p style="font-size: 16px; margin: 0 0 20px;">
            Hello {{ $data['first_name'] }},
        </p>

        <p style="font-size: 15px; line-height: 1.6; margin: 0 0 16px;">
            We've received your enrollment for <strong>{{ $data['course_name'] }}</strong> and have forwarded
            it to <strong>{{ $data['instructor_name'] }}</strong>, who will be in touch with you shortly.
        </p>

        <p style="font-size: 15px; line-height: 1.6; margin: 0 0 24px;">
            In the meantime, if you have any additional questions feel free to reply to this email.
        </p>

        @if (!empty($data['questions']))
            <hr style="border: none; border-top: 1px solid #e0e0e0; margin: 20px 0;">

            <h2 style="margin: 0 0 12px; font-size: 15px; color: #555; text-transform: uppercase; letter-spacing: 0.5px;">
                Your Message
            </h2>

            <div style="background-color: #ffffff; border-left: 4px solid #1a237e; padding: 14px 18px; font-size: 15px; line-height: 1.6; color: #555; border-radius: 0 4px 4px 0;">
                {!! nl2br(e($data['questions'])) !!}
            </div>
        @endif

        @if (!empty($data['payment_method_label']))
            <hr style="border: none; border-top: 1px solid #e0e0e0; margin: 20px 0;">

            <h2 style="margin: 0 0 12px; font-size: 15px; color: #555; text-transform: uppercase; letter-spacing: 0.5px;">
                Payment Information
            </h2>

            <table style="width: 100%; border-collapse: collapse; font-size: 15px;">
                <tr>
                    <td style="padding: 6px 0; width: 130px; color: #666; vertical-align: top;">Cost</td>
                    <td style="padding: 6px 0; font-weight: 600;">${{ $data['cost'] }}</td>
                </tr>
                <tr>
                    <td style="padding: 6px 0; color: #666; vertical-align: top;">Payment Method</td>
                    <td style="padding: 6px 0;">{{ $data['payment_method_label'] }}</td>
                </tr>
                @if (!empty($data['transfer_date']))
                    <tr>
                        <td style="padding: 6px 0; color: #666; vertical-align: top;">Transfer Date</td>
                        <td style="padding: 6px 0;">{{ $data['transfer_date'] }}</td>
                    </tr>
                @endif
                @if (!empty($data['transaction_id']))
                    <tr>
                        <td style="padding: 6px 0; color: #666; vertical-align: top;">Reference</td>
                        <td style="padding: 6px 0;">{{ $data['transaction_id'] }}</td>
                    </tr>
                @endif
            </table>

            @if (empty($data['confirmed']))
                <p style="font-size: 14px; line-height: 1.6; color: #d32f2f; margin: 16px 0 0;">
                    Your spot is reserved pending payment confirmation — we'll follow up once we've received it.
                </p>
            @endif
        @endif

    </div>

    <div style="background-color: #eeeeee; padding: 14px 32px; border-radius: 0 0 4px 4px; font-size: 12px; color: #888; text-align: center;">
        Azuay Community Theater &mdash; Cuenca, Ecuador
    </div>

</div>
