<?php

namespace App\Http\Requests\ScoreRating;

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
                $this->route()->rating
                    ? "unique:score_rating,rating," . $this->route()->rating
                    : "unique:score_rating,rating",
            ],
            "score" => ["required", "integer"],
        ];
    }

    public function messages()
    {
        return [
            "unique" => "The :attribute :input has already been taken.",
        ];
    }
}
