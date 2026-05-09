<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; color: #333;">

    <div style="background-color: #e8eaf6; padding: 24px 32px; border-radius: 4px 4px 0 0; border-bottom: 3px solid #1a237e;">
        <h1 style="margin: 0; color: #1a237e; font-size: 22px; font-weight: 600;">
            Thank You for Your Inquiry
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
            We've received your inquiry about <strong>{{ $data['course_name'] }}</strong> and have forwarded
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

    </div>

    <div style="background-color: #eeeeee; padding: 14px 32px; border-radius: 0 0 4px 4px; font-size: 12px; color: #888; text-align: center;">
        Azuay Community Theater &mdash; Cuenca, Ecuador
    </div>

</div>
