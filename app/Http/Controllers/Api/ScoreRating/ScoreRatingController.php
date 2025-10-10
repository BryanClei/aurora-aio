<?php

namespace App\Http\Controllers\Api\ScoreRating;

use App\Models\ScoreRating;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Essa\APIToolKit\Api\ApiResponse;
use App\Http\Requests\DisplayRequest;
use App\Http\Requests\ScoreRating\ScoreRatingRequest;

class ScoreRatingController extends Controller
{
    use ApiResponse;
    public function index(DisplayRequest $request)
    {
        $status = $request->status;

        $score_rating = ScoreRating::when($status === "inactive", function (
            $query
        ) {
            $query->onlyTrashed();
        })
            ->useFilters()
            ->dynamicPaginate();

        if ($score_rating->isEmpty()) {
            return $this->responseNotFound(__("messages.data_not_found"));
        }

        return $this->responseSuccess(
            "Score rating display successfully.",
            $score_rating
        );
    }

    public function show($id)
    {
        $score_rating = ScoreRating::find($id);

        if (!$score_rating) {
            return $this->responseNotFound(__("messages.data_not_found"));
        }

        return $this->responseSuccess(
            "Score rating display successfully.",
            $score_rating
        );
    }

    public function store(ScoreRatingRequest $request)
    {
        $score_rating = ScoreRating::create([
            "rating" => $request->rating,
            "score" => $request->score,
        ]);

        return $this->responseCreated(
            "Score Rating successfully created",
            $score_rating
        );
    }

    public function update(ScoreRatingRequest $request, $id)
    {
        $score_rating = ScoreRating::find($id);

        if (!$score_rating) {
            return $this->responseNotFound("", __("messages.id_not_found"));
        }

        $score_rating->rating = $request->rating;
        $score_rating->score = $request->score;

        if (!$score_rating->isDirty()) {
            return $this->responseSuccess("No Changes", $score_rating);
        }

        $score_rating->save();

        return $this->responseSuccess(
            "Role successfully updated",
            $score_rating
        );
    }

    public function toggleArchived(Request $request, $id)
    {
        $score_rating = ScoreRating::withTrashed()->find($id);

        if (!$score_rating) {
            return $this->responseNotFound("", __("messages.id_not_found"));
        }

        if ($score_rating->trashed()) {
            $score_rating->restore();
            return $this->responseSuccess(
                __("messages.success_restored", [
                    "attribute" => "Score rating",
                ]),
                $score_rating
            );
        }

        $score_rating->delete();
        return $this->responseSuccess(
            __("messages.success_archived", [
                "attribute" => "Score rating",
            ]),
            $score_rating
        );
    }
}
