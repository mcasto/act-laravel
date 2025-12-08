<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\CourseContact;
use App\Models\SiteConfig;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use League\HTMLToMarkdown\HtmlConverter;
use Illuminate\Support\Str;

class CourseController extends Controller
{
    public function index(): JsonResponse
    {
        $courses = Course::with(['sessions', 'contacts'])
            ->orderBy('enrollment_start', 'desc')
            ->get()
            ->each(function ($course) {
                $course->append('message');
            });

        return response()->json($courses);
    }

    public function show(string|int $id): JsonResponse
    {
        if ($id == 'new') {
            return response()->json([
                'cost' => 0,
                'name' => null,
                'instructor_name' => null,
                'enrollment_start' => null,
                'enrollment_end' => null,
                'fixr' => null,
                'instructor_email' => null,
                'instructor_info' => "",
                'instructor_photo' => null,
                'location' => "Azuay Community Theater, 14-46 Atonio Vega Muñoz between Estevez de Toral and Coronel Talbot",
                'message' => "",
                'poster' => null,
                'sessions' => [],
                'contacts' => [],
                'tagline' => null,
            ]);
        }

        $course = Course::with(['sessions', 'contacts'])
            ->where('id', '=', $id)
            ->first();

        $course->append('message');

        return response()->json($course);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name'             => 'required|string|max:255',
            'instructor_name'  => 'required|string|max:255',
            'instructor_email' => 'required|email|max:255',
            'enrollment_start' => 'required|date',
            'enrollment_end'   => 'required|date|after_or_equal:enrollment_start',
            'cost'             => 'required|numeric|min:0',
            'poster'           => 'required|string|max:255',
            'tagline'          => 'required|string|max:255',
            'location'         => 'required|string|max:255',
            'fixr'             => 'nullable|string|max:255',
            'instructor_photo' => 'required|string|max:255',
            'instructor_info'  => 'required|string',
            'message'         => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors(),
            ]);
        }

        $rec = $validator->validated();
        $baseSlug = Str::slug($rec['name'], '-') . '-' . date("Y");
        $rec['slug'] = $baseSlug;

        // Check if slug exists and append incremented number if needed
        if (Course::where('slug', $rec['slug'])->exists()) {
            // Find the highest number suffix for this slug
            $existingSlugs = Course::where('slug', 'like', $baseSlug . '%')
                ->pluck('slug')
                ->map(function ($slug) use ($baseSlug) {
                    // Extract the number suffix if it exists
                    if (preg_match('/^' . preg_quote($baseSlug, '/') . '-(\d+)$/', $slug, $matches)) {
                        return (int) $matches[1];
                    }
                    return 0;
                })
                ->max();

            $nextNumber = $existingSlugs ? $existingSlugs + 1 : 1;
            $rec['slug'] = $baseSlug . '-' . sprintf('%03s', $nextNumber);
        }

        $course = Course::create($rec);
        $rec['id'] = $course->id;

        // Create the blade template view file
        $viewPath = resource_path("views/courses/{$rec['slug']}.blade.php");
        $viewDir = dirname($viewPath);

        // Ensure the directory exists
        if (!file_exists($viewDir)) {
            mkdir($viewDir, 0755, true);
        }

        // Write the message content to the blade view file
        file_put_contents($viewPath, $rec['message']);

        return response()->json($course);
    }

    public function uploadPoster(Request $request, int|string $id)
    {
        if ($request->hasFile('poster')) {
            $file     = $request->file('poster');
            $filename = uniqid() . '.' . $file->getClientOriginalExtension();
            $file->storeAs('uploads', $filename);
        } else {
            return response()->json([
                'status' => 'error',
                'message' => 'No file uploaded',
            ]);
        }

        return response()->json([
            'status' => 'success',
            'poster' => $filename
        ]);
    }

    public function uploadInstructorPhoto(Request $request, int|string $id)
    {
        if ($request->hasFile('instructor_photo')) {
            $file     = $request->file('instructor_photo');
            $filename = uniqid() . '.' . $file->getClientOriginalExtension();
            $file->storeAs('uploads', $filename);
        } else {
            return response()->json([
                'status' => 'error',
                'message' => 'No file uploaded',
            ]);
        }

        return response()->json([
            'status' => 'success',
            'instructor_photo' => $filename
        ]);
    }

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
