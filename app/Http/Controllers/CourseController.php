<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\CourseContact;
use App\Models\SiteConfig;
use App\Util\SendGridUtil;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use League\HTMLToMarkdown\HtmlConverter;

class CourseController extends Controller
{
    /**
     * Get courses with open enrollment
     *
     * Retrieves all courses that are currently accepting enrollments
     * (between enrollment_start and enrollment_end dates).
     *
     * @return JsonResponse Courses with their sessions
     *
     * @source Database Model: Course (reads with sessions relationship)
     */
    public function openEnrollment(): JsonResponse
    {
        $courses = Course::with(['sessions'])->where('enrollment_start', '<=', now())
            ->where('enrollment_end', '>=', now())->get();

        return response()->json($courses);
    }

    /**
     * Get detailed information for a specific course
     *
     * Retrieves course details by slug, including session information and
     * HTML content from the course's snippet file.
     *
     * @param string $slug The course slug identifier
     * @return JsonResponse Course data with HTML content
     *
     * @source
     *   Database Model: Course (reads with sessions relationship)
     *   File: storage/app/public/snippets/courses/{slug}.html
     */
    public function courseDetails(string $slug): JsonResponse
    {
        $course = Course::with(['sessions'])->where('slug', '=', $slug)->first()->toArray();

        $snippetPath = storage_path("app/public/snippets/courses/{$course['slug']}.html");

        $course['html'] = file_get_contents($snippetPath);

        return response()->json($course);
    }

    /**
     * Handle course enrollment contact submission
     *
     * Validates and creates a course contact record, retrieves course and
     * configuration details, formats an enrollment email, and sends it via
     * SendGrid to the course instructor.
     *
     * @param Request $request Contains contact info (first_name, last_name, email, phone, questions, course_id)
     * @return JsonResponse SendGrid response or validation errors
     *
     * @source Database Models:
     *   - CourseContact (validates and creates)
     *   - SiteConfig (reads latest config)
     *   - Course (reads with sessions relationship)
     */
    public function courseContact(Request $request): JsonResponse
    {
        $validated = CourseContact::validate($request->all());
        if (isset($validated['errors'])) {
            return response()->json($validated);
        }

        $config    = SiteConfig::orderByDesc('created_at')->first()->toArray();
        $course = Course::with(['sessions'])->where('id', '=', $request->input('course_id'))->first()->toArray();

        $courseDate = array_shift($course['sessions'])['date'];
        $courseDate = Carbon::parse($courseDate)->format('F j, Y');

        $contact = CourseContact::create($validated);
        $contact->questions = $request->input('questions');

        $fromName = $validated['first_name'] . " " . $validated['last_name'];
        $fromEmail = $validated['email'];

        $toName = $course['instructor_name'];
        $toEmail = env('APP_DEBUG') ? $config['dev_email'] : $course['instructor_email'];

        $subject = $course['name'] . " Enrollment for " . $fromName;
        $classNotes = $course['name'] . " in " . $courseDate;

        $questions = $request->input('questions');
        $questions = !!$questions ? $questions : '';
        // $converter = new HtmlConverter();
        // $questions = $converter->convert($questions);

        $body = <<<BODY
<p>Hi.</p>

<p>My full name is {$fromName}.</p>

<p>My Whatsapp Number is {$validated['phone']}.</p>

<p>Please send more details about the {$classNotes} including how to pay</p>

{$questions}
BODY;

        // $response = SendGridUtil::send('Course Enrollment Message', $fromName, $fromEmail, $toName, $toEmail, $subject, $body);

        // $contact->sendgrid_response = json_encode($response, JSON_PRETTY_PRINT);

        // $contact->save();

        // if ($response['statusCode'] != 202) {
        //     SendGridUtil::send('Error', $fromName, $fromEmail, 'ACT Errors', $config['dev_email'], 'Error with Course Enrollment Request for ' . $classNotes, $body);
        // }

        return response()->json(['status' => 'success']);
    }
}
