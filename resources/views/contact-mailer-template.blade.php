<div>
    <div>
        From: <a href="mailto:{{ $contact->email }}"> {{ $contact->name }} <{{ $contact->email }}></a>
    </div>
    <p>
        Phone: {{ $contact->phone }}
    </p>
    <p>
        Subject: {{ $contact->subject }}
    </p>
    <p>
        {{ $contact->body }}
    </p>
    <div>
        Received: {{ $contact->created_at->format('M j, Y g:i A') }}
    </div>
</div>
