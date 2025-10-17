<?php

namespace App\Http\Requests\ScoreRating;

use App\Rules\MaxThreeRecordRule;
use Illuminate\Foundation\Http\FormRequest;

class ScoreRatingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            "rating" => [
                "required",
                "integer",
                new MaxThreeRecordRule(),
                $this->route()->rating
                    ? "unique:score_rating,rating," . $this->route()->rating
                    : "unique:score_rating,rating",
            ],
            "score" => ["required", "integer", "between:1,100"],
        ];
    }

    public function messages()
    {
        return [
            "unique" => "The :attribute :input has already been taken.",
        ];
    }
}
