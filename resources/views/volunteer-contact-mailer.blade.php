<div style="font-family: sans-serif; max-width: 600px; margin: 0 auto;">
    <h2 style="color: #333; border-bottom: 2px solid #e0e0e0; padding-bottom: 8px;">
        New Volunteer Inquiry
    </h2>

    <table style="width: 100%; border-collapse: collapse; margin-bottom: 24px;">
        <tr>
            <td style="padding: 8px 12px; font-weight: bold; width: 120px; color: #555;">Name</td>
            <td style="padding: 8px 12px;">
                <a href="mailto:{{ $email }}" style="color: #1976d2; text-decoration: none;">
                    {{ $name }}
                </a>
            </td>
        </tr>
        <tr style="background: #f5f5f5;">
            <td style="padding: 8px 12px; font-weight: bold; color: #555;">Email</td>
            <td style="padding: 8px 12px;">{{ $email }}</td>
        </tr>
        <tr>
            <td style="padding: 8px 12px; font-weight: bold; color: #555;">Phone</td>
            <td style="padding: 8px 12px;">{{ $phone }}</td>
        </tr>
    </table>

    @if($skills->isNotEmpty())
    <h3 style="color: #333; margin-bottom: 8px;">Areas of Interest</h3>
    <ul style="margin: 0; padding-left: 20px; line-height: 1.8;">
        @foreach($skills as $skill)
        <li>{{ $skill->name }}</li>
        @endforeach
    </ul>
    @endif
</div>
