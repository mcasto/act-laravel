<div style="font-family: Arial, sans-serif; max-width: 600px; color: #333;">
    <h2 style="color: #c62828; margin: 0 0 12px;">
        {{ count($items) === 1 ? 'A box office to-do is overdue' : count($items) . ' box office to-dos are overdue' }}
    </h2>

    <p>These haven't been checked off on the Box Office Checklist:</p>

    <table style="border-collapse: collapse; width: 100%; font-size: 14px;">
        <tr style="background: #ffebee;">
            <th style="text-align: left; padding: 6px; border-bottom: 1px solid #ddd;">To-do</th>
            <th style="text-align: left; padding: 6px; border-bottom: 1px solid #ddd;">Show</th>
            <th style="text-align: left; padding: 6px; border-bottom: 1px solid #ddd;">Was due</th>
        </tr>
        @foreach ($items as $item)
            <tr>
                <td style="padding: 6px; border-bottom: 1px solid #eee;">
                    {{ $item['label'] }}
                    @if (!empty($item['performance']))
                        <br><span style="color: #757575; font-size: 12px;">{{ $item['performance'] }} performance</span>
                    @endif
                </td>
                <td style="padding: 6px; border-bottom: 1px solid #eee;">{{ $item['show'] }}</td>
                <td style="padding: 6px; border-bottom: 1px solid #eee; color: #c62828;">{{ $item['due_label'] }}</td>
            </tr>
        @endforeach
    </table>

    <p style="margin-top: 16px;">
        <a href="{{ $link }}" style="color: #1565c0;">Open the Box Office Checklist</a>
    </p>
</div>
