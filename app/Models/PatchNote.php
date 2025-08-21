<?php

namespace App\Models;

use App\Filters\PatchNoteFilter;
use Essa\APIToolKit\Filters\Filterable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PatchNote extends Model
{
    use HasFactory, SoftDeletes, Filterable;

    protected string $default_filters = PatchNoteFilter::class;

    protected $table = "patch_notes";

    protected $fillable = [
        "title",
        "description",
        "version",
        "filename",
        "filepath",
        "type",
        "is_published",
        "published_at",
    ];

    protected $casts = [
        "is_published" => "boolean",
        "published_at" => "datetime",
    ];

    protected $appends = ["file_url"];

    protected function fileInfo(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (!$this->filename || !$this->filepath) {
                    return null;
                }

                return [
                    "filename" => $this->filename,
                    "filepath" => $this->filepath,
                    "url" => asset($this->filepath),
                    "type" => pathinfo($this->filename, PATHINFO_EXTENSION),
                    "size" => file_exists(
                        storage_path(
                            "app/public/" .
                                str_replace("storage/", "", $this->filepath)
                        )
                    )
                        ? filesize(
                            storage_path(
                                "app/public/" .
                                    str_replace("storage/", "", $this->filepath)
                            )
                        )
                        : null,
                ];
            }
        );
    }
    // Accessor for file info

    // Method to set file
    public function setFile($filename, $filepath)
    {
        $this->filename = $filename;
        $this->filepath = $filepath;
        return $this;
    }

    // Method to publish patch note
    public function publish()
    {
        $this->update([
            "is_published" => true,
            "published_at" => now(),
        ]);
    }

    public function getFileUrlAttribute()
    {
        return $this->filepath
            ? asset(Storage::url(str_replace("storage/", "", $this->filepath)))
            : null;
    }
}
