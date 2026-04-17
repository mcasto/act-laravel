<div>
    <p>Dear {{ $name }},</p>

    <p>
        As a member of the Cast or Crew you are entitled to one (1) Comp Ticket to any performance of the show.
        To redeem your Comp Ticket, click on <a href="{{ env('FRONTEND_URL') }}/comp/{{ $uid }}">this link</a>
        no less than 48 hours before the requested performance and fill out the form.
        If the link is not clickable, copy and paste the following URL into your browser:
        <br>{{ env('FRONTEND_URL') }}/comp/{{ $uid }}
    </p>

    <p>
        Tickets are issued based on availability and will not be issued to performances that are already SOLD OUT.
    </p>

    <p>
        Thank you for all of your hard work and <span style="color: red;"><strong>HAVE A GREAT SHOW</strong></span>.
    </p>
</div>
