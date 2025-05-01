<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CourseContact extends Model
{
    protected $fillable = [
        'course_id',
        'first_name',
        'last_name',
        'email',
        'phone',
        'questions',
        'sendgrid_response',
    ];

    public static function validate($data)
    {
        $validator = validator($data, [
            'course_id' => ['required', 'integer'],
            'first_name'    => ['required', 'string', 'max:255'],
            'last_name'    => ['required', 'string', 'max:255'],
            'email'   => ['required', 'email', 'max:255'],
            'phone'   => ['required', 'string', 'max:20'],
        ]);

        if ($validator->fails()) {
            return ['errors' => $validator->errors()->toArray()];
        }

        return $validator->validated();
    }
}
