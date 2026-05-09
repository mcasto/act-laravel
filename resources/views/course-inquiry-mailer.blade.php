<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; color: #333;">

    <div style="background-color: #1a237e; padding: 24px 32px; border-radius: 4px 4px 0 0;">
        <h1 style="margin: 0; color: #ffffff; font-size: 22px; font-weight: 600;">
            New Course Inquiry
        </h1>
        <p style="margin: 6px 0 0; color: #c5cae9; font-size: 14px;">
            {{ $data['course_name'] }}
        </p>
    </div>

    <div style="background-color: #f9f9f9; padding: 28px 32px; border: 1px solid #e0e0e0; border-top: none;">

        <h2 style="margin: 0 0 16px; font-size: 16px; color: #1a237e; text-transform: uppercase; letter-spacing: 0.5px;">
            Contact Information
        </h2>

        <table style="width: 100%; border-collapse: collapse; font-size: 15px;">
            <tr>
                <td style="padding: 8px 0; width: 130px; color: #666; vertical-align: top;">Name</td>
                <td style="padding: 8px 0; font-weight: 600;">{{ $data['first_name'] }} {{ $data['last_name'] }}</td>
            </tr>
            <tr>
                <td style="padding: 8px 0; color: #666; vertical-align: top;">Email</td>
                <td style="padding: 8px 0;">
                    <a href="mailto:{{ $data['email'] }}" style="color: #1a237e;">{{ $data['email'] }}</a>
                </td>
            </tr>
            <tr>
                <td style="padding: 8px 0; color: #666; vertical-align: top;">Phone</td>
                <td style="padding: 8px 0;">{{ $data['phone'] }}</td>
            </tr>
        </table>

        @if (!empty($data['questions']))
            <hr style="border: none; border-top: 1px solid #e0e0e0; margin: 20px 0;">

            <h2 style="margin: 0 0 12px; font-size: 16px; color: #1a237e; text-transform: uppercase; letter-spacing: 0.5px;">
                Questions / Comments
            </h2>

            <div style="background-color: #ffffff; border-left: 4px solid #1a237e; padding: 14px 18px; font-size: 15px; line-height: 1.6; border-radius: 0 4px 4px 0;">
                {!! nl2br(e($data['questions'])) !!}
            </div>
        @endif

    </div>

    <div style="background-color: #eeeeee; padding: 14px 32px; border-radius: 0 0 4px 4px; font-size: 12px; color: #888; text-align: center;">
        Reply directly to this email to respond to {{ $data['first_name'] }}.
    </div>

</div>
