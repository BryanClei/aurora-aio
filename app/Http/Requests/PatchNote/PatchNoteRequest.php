<?php

namespace App\Http\Requests\PatchNote;

use Illuminate\Foundation\Http\FormRequest;

class PatchNoteRequest extends FormRequest
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
        // Check if this is an update (has route parameter)
        $isUpdate = $this->route("patchNote") !== null;

        return [
            "title" => $isUpdate
                ? "sometimes|required|string|max:255"
                : "required|string|max:255",
            "description" => $isUpdate
                ? "sometimes|required|string"
                : "required|string",
            "version" => "nullable|string|max:50",
            "type" => $isUpdate
                ? "sometimes|required|in:feature,bugfix,security,performance,breaking"
                : "required|in:feature,bugfix,security,performance,breaking",
            "file" => "required|file|max:10240",
            "is_published" => "nullable|boolean",
        ];
    }

    protected function prepareForValidation()
    {
        if ($this->has("is_published")) {
            $this->merge([
                "is_published" => $this->toBoolean($this->is_published),
            ]);
        }
    }

    private function toBoolean($value)
    {
        if (is_null($value)) {
            return false;
        }
        return filter_var(
            $value,
            FILTER_VALIDATE_BOOLEAN,
            FILTER_NULL_ON_FAILURE
        ) ?? false;
    }
}
