<?php

namespace App\Employees;

use App\Models\Employee;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EmployeePhotoService
{
    public const MAX_BYTES = 2_097_152;

    /** @var list<string> */
    public const ALLOWED_MIME_TYPES = [
        'image/jpeg',
        'image/png',
        'image/webp',
    ];

    public function store(Employee $employee, UploadedFile $file): string
    {
        $this->assertValidImage($file);

        $extension = match ($file->getMimeType()) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            default => throw ValidationException::withMessages([
                'image' => __('The profile image must be a JPEG, PNG, or WebP file.'),
            ]),
        };

        $directory = 'employees/'.$employee->id;
        $filename = 'profile.'.$extension;
        $path = $file->storeAs($directory, $filename, 'local');

        if ($path === false) {
            throw ValidationException::withMessages([
                'image' => __('The profile image could not be stored.'),
            ]);
        }

        if ($employee->image_path && $employee->image_path !== $path) {
            Storage::disk('local')->delete($employee->image_path);
        }

        $employee->forceFill(['image_path' => $path])->save();

        return $path;
    }

    public function delete(Employee $employee): void
    {
        if ($employee->image_path) {
            Storage::disk('local')->delete($employee->image_path);
            $employee->forceFill(['image_path' => null])->save();
        }
    }

    public function stream(Employee $employee): StreamedResponse
    {
        if (! $employee->image_path || ! Storage::disk('local')->exists($employee->image_path)) {
            abort(404);
        }

        $mime = Storage::disk('local')->mimeType($employee->image_path) ?: 'application/octet-stream';

        return Storage::disk('local')->response(
            $employee->image_path,
            headers: [
                'Content-Type' => $mime,
                'Cache-Control' => 'private, max-age=0, must-revalidate',
                'X-Content-Type-Options' => 'nosniff',
            ],
        );
    }

    public function assertValidImage(UploadedFile $file): void
    {
        if (! $file->isValid()) {
            throw ValidationException::withMessages([
                'image' => __('The profile image upload failed.'),
            ]);
        }

        if ($file->getSize() > self::MAX_BYTES) {
            throw ValidationException::withMessages([
                'image' => __('The profile image may not be greater than 2 MB.'),
            ]);
        }

        $mime = $file->getMimeType();
        if (! in_array($mime, self::ALLOWED_MIME_TYPES, true)) {
            throw ValidationException::withMessages([
                'image' => __('The profile image must be a JPEG, PNG, or WebP file.'),
            ]);
        }

        $imageInfo = @getimagesize($file->getRealPath());
        if ($imageInfo === false) {
            throw ValidationException::withMessages([
                'image' => __('The profile image content is invalid.'),
            ]);
        }

        $detected = $imageInfo['mime'] ?? null;
        if (! in_array($detected, self::ALLOWED_MIME_TYPES, true)) {
            throw ValidationException::withMessages([
                'image' => __('The profile image content is invalid.'),
            ]);
        }
    }
}
