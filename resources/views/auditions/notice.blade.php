{{--
Plain (non-AI) audition page built from App\Support\AuditionNotice::data(),
colored by App\Support\PosterPalette ($palette) from the show's poster.
Also handed to Gemini as the content to restyle, so every fact lives here.
Nothing is indented on purpose: the admin editor turns pasted indentation
into non-breaking spaces, which breaks the CSS (see Audition::normalizeWhitespace).
--}}
<style>
.act-audition {
max-width: 900px;
margin: 0 auto;
padding: 0 16px 24px;
background: #ffffff;
color: #111111;
font-family: Georgia, "Times New Roman", Times, serif;
font-size: 17px;
line-height: 1.6;
box-sizing: border-box;
}
.act-audition *, .act-audition *::before, .act-audition *::after {
box-sizing: border-box;
}
.act-audition .act-banner {
background: {{ $palette['dark'] }};
color: {{ $palette['on_dark'] }};
text-align: center;
padding: 22px 16px 20px;
border-bottom: 6px solid {{ $palette['accent'] }};
}
.act-audition .act-kicker {
margin: 0 0 6px;
font-family: Arial, Helvetica, sans-serif;
font-size: 14px;
font-weight: bold;
letter-spacing: 3px;
text-transform: uppercase;
color: {{ $palette['on_dark_accent'] }};
}
.act-audition h1 {
margin: 0;
font-size: 38px;
line-height: 1.15;
font-weight: normal;
font-style: italic;
color: {{ $palette['on_dark'] }};
}
.act-audition .act-byline {
margin: 8px 0 0;
font-size: 18px;
}
.act-audition .act-poster {
text-align: center;
margin: 24px 0 8px;
}
.act-audition .act-poster img {
max-width: 100%;
width: 420px;
height: auto;
border: 1px solid {{ $palette['dark'] }};
}
.act-audition .act-keyinfo {
border: 3px solid {{ $palette['dark'] }};
margin: 24px 0;
padding: 20px;
text-align: center;
}
.act-audition h2 {
margin: 28px 0 8px;
font-size: 26px;
color: {{ $palette['dark'] }};
}
.act-audition .act-keyinfo h2 {
margin: 0 0 4px;
}
.act-audition .act-date {
margin: 12px 0 0;
font-family: Arial, Helvetica, sans-serif;
font-size: 24px;
font-weight: bold;
line-height: 1.25;
text-transform: uppercase;
}
.act-audition .act-time {
margin: 2px 0 4px;
font-family: Arial, Helvetica, sans-serif;
font-size: 21px;
font-weight: bold;
color: {{ $palette['accent'] }};
}
.act-audition .act-note {
margin: 0 0 8px;
font-style: italic;
}
.act-audition .act-callbacks {
margin: 14px 0;
font-family: Arial, Helvetica, sans-serif;
}
.act-audition .act-address {
margin: 14px 0 0;
}
.act-audition .act-actions {
margin: 20px 0 6px;
}
.act-audition a.act-button {
display: inline-block;
margin: 6px;
padding: 12px 22px;
border-radius: 4px;
font-family: Arial, Helvetica, sans-serif;
font-size: 16px;
font-weight: bold;
letter-spacing: 1px;
text-transform: uppercase;
text-decoration: none;
color: {{ $palette['on_accent'] }};
background: {{ $palette['accent'] }};
}
.act-audition a.act-button-dark {
color: {{ $palette['on_dark'] }};
background: {{ $palette['dark'] }};
}
.act-audition .act-small {
margin: 6px 0 0;
font-size: 15px;
}
.act-audition .act-role {
border-left: 4px solid {{ $palette['accent'] }};
margin: 16px 0;
padding: 2px 0 2px 14px;
}
.act-audition .act-role h3 {
margin: 0;
font-size: 21px;
text-transform: uppercase;
}
.act-audition .act-role h4 {
margin: 0 0 4px;
font-family: Arial, Helvetica, sans-serif;
font-size: 14px;
letter-spacing: 1px;
text-transform: uppercase;
color: #555555;
}
.act-audition .act-role p {
margin: 0;
}
.act-audition .act-perf {
font-family: Arial, Helvetica, sans-serif;
font-size: 20px;
font-weight: bold;
}
.act-audition .act-contact {
margin-top: 28px;
padding: 16px 20px;
background: {{ $palette['tint'] }};
}
.act-audition .act-contact h2 {
margin-top: 0;
}
.act-audition a {
color: {{ $palette['accent'] }};
}
@media (max-width: 600px) {
.act-audition h1 {
font-size: 30px;
}
.act-audition .act-date {
font-size: 20px;
}
}
</style>
<div class="act-audition">
<div class="act-banner">
<p class="act-kicker">Azuay Community Theater &middot; Call for Auditions</p>
<h1>{{ $title }}</h1>
<p class="act-byline">@if($playwright)by {{ $playwright }} &middot; @endif{{ '' }}Directed by {{ $director }}</p>
</div>
@if($poster_path)
<div class="act-poster">
<img src="{{ $poster_path }}" alt="{{ $poster_alt }}">
</div>
@endif
<div class="act-keyinfo">
<h2>Auditions</h2>
@foreach($sessions as $s)
<p class="act-date">{{ $s['date'] }}</p>
@if($s['time'])
<p class="act-time">{{ $s['time'] }}</p>
@endif
@if($s['note'])
<p class="act-note">{{ $s['note'] }}</p>
@endif
@endforeach
@if($callbacks)
<p class="act-callbacks"><strong>CALLBACKS:</strong>
@foreach($callbacks as $c)
{{ $c['date'] }}@if($c['time']), {{ $c['time'] }}@endif @if($c['note']) ({{ $c['note'] }})@endif @if(!$loop->last)<br>@endif
@endforeach
</p>
@endif
<p class="act-address">
@foreach($location as $line)
@if($loop->first)<strong>{{ $line }}</strong>@else{{ $line }}@endif @if(!$loop->last)<br>@endif
@endforeach
</p>
@if($registration_url || $script_url)
<div class="act-actions">
@if($registration_url)
<a class="act-button" href="{{ $registration_url }}" target="_blank" rel="noopener">Register to Audition</a>
@endif
@if($script_url)
<a class="act-button act-button-dark" href="{{ $script_url }}" target="_blank" rel="noopener">Script &amp; Audition Excerpts</a>
@endif
</div>
@endif
@if($registration_url && $registration_note)
<p class="act-small">{{ $registration_note }}</p>
@endif
</div>
@if($synopsis)
<section>
<h2>The Play, in Brief</h2>
@foreach($synopsis as $p)
<p>{{ $p }}</p>
@endforeach
</section>
@endif
@if($roles)
<section>
<h2>{{ $roles_heading }}</h2>
@foreach($roles as $r)
<div class="act-role">
<h3>{{ $r['name'] }}</h3>
@if($r['gender_age'])
<h4>{{ $r['gender_age'] }}</h4>
@endif
@if($r['description'])
<p>{{ $r['description'] }}</p>
@endif
</div>
@endforeach
</section>
@endif
@if($what_to_expect)
<section>
<h2>What to Expect at Auditions</h2>
@foreach($what_to_expect as $p)
<p>{{ $p }}</p>
@endforeach
</section>
@endif
@if($rehearsal_start || $rehearsal_schedule)
<section>
<h2>Rehearsals</h2>
@if($rehearsal_start)
<p>Begin <strong>{{ $rehearsal_start }}</strong>.</p>
@endif
@foreach($rehearsal_schedule as $p)
<p>{{ $p }}</p>
@endforeach
</section>
@endif
@if($performance_dates)
<section>
<h2>Performance Dates</h2>
<p class="act-perf">{{ $performance_dates }}</p>
</section>
@endif
@if($contacts)
<div class="act-contact">
<h2>Questions?</h2>
@foreach($contacts as $c)
<p>@if($c['name'])<strong>{{ $c['name'] }}@if($c['title']), {{ $c['title'] }}@endif</strong><br>@endif
@if($c['phone'])WhatsApp {{ $c['phone'] }}<br>@endif
@if($c['email'])<a href="mailto:{{ $c['email'] }}">{{ $c['email'] }}</a>@endif</p>
@endforeach
</div>
@endif
</div>
