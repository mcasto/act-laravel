<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CourseContact extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'course_id',
        'first_name',
        'last_name',
        'email',
        'phone',
        'questions',
        'sendgrid_response',
        'patron_id',
        'payment_method_id',
        'transfer_date',
        'transaction_id',
        'confirmed',
    ];

    protected $casts = [
        'confirmed' => 'boolean',
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function patron(): BelongsTo
    {
        return $this->belongsTo(Patron::class);
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    /**
     * $requiresPayment gates the payment fields — a free ($0) class doesn't
     * need a payment method or transfer date at all.
     */
    public static function validate($data, bool $requiresPayment = false)
    {
        $validator = validator($data, [
            'course_id' => ['required', 'integer'],
            'first_name'    => ['required', 'string', 'max:255'],
            'last_name'    => ['required', 'string', 'max:255'],
            'email'   => ['required', 'email', 'max:255'],
            'phone'     => ['required', 'string', 'max:20'],
            'questions' => ['nullable', 'string'],
            'payment_method_value' => [$requiresPayment ? 'required' : 'nullable', 'string', 'exists:payment_methods,value'],
            'transfer_date' => ['required_if:payment_method_value,transfer', 'nullable', 'date'],
        ]);

        if ($validator->fails()) {
            return ['errors' => $validator->errors()->toArray()];
        }

        return $validator->validated();
    }
}
