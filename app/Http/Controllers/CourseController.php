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
     * Get classes with open enrollment
     */

    public function openEnrollment(): JsonResponse
    {
        $courses = Course::with(['sessions'])->where('enrollment_start', '<=', now())
            ->where('enrollment_end', '>=', now())->get();

        return response()->json($courses);
    }

    /**
     * Get details for specific course based on slug
     */
    public function courseDetails(string $slug): JsonResponse
    {
        $course = Course::with(['sessions'])->where('slug', '=', $slug)->first()->toArray();

        $snippetPath = storage_path("app/public/snippets/courses/{$course['slug']}.html");

        $course['html'] = file_get_contents($snippetPath);

        return response()->json($course);
    }

    /**
     * Send & log course enrollment request
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

        $response = SendGridUtil::send('Course Enrollment Message', $fromName, $fromEmail, $toName, $toEmail, $subject, $body);

        $contact->sendgrid_response = json_encode($response, JSON_PRETTY_PRINT);

        $contact->save();

        if ($response['statusCode'] != 202) {
            SendGridUtil::send('Error', $fromName, $fromEmail, 'ACT Errors', $config['dev_email'], 'Error with Course Enrollment Request for ' . $classNotes, $body);
        }

        return response()->json(['sendgrid_response' => $response]);
    }
}
