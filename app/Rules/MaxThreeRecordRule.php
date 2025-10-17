<?php

namespace App\Rules;

use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Validation\ValidationRule;

class MaxThreeRecordRule implements ValidationRule
{
    public function validate(
        string $attribute,
        mixed $value,
        Closure $fail
    ): void {
        $currentCount = DB::table("score_rating")->count();

        if (request()->route("rating")) {
            if ($currentCount > 3) {
                $fail(
                    "Maximum of 3 records allowed in the score rating table."
                );
            }
        } else {
            if ($currentCount >= 3) {
                $fail(
                    "Maximum of 3 records allowed in the score rating table."
                );
            }
        }
    }
}
