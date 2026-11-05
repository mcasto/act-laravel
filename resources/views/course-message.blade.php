<div>
    <p>Hello {{ $name }},</p>

    {{-- Admin-written HTML from the editor, already sanitized by CourseController::sanitizeEmailHtml(). --}}
    <div>{!! $body !!}</div>

    <p style="font-size: 12px; color: #757575;">
        You're receiving this because you're enrolled in {{ $course_name }}. Reply to this email to reach
        the instructor, {{ $instructor_name }}.
    </p>
</div>
