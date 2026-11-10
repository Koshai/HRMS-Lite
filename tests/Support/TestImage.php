<?php

namespace Tests\Support;

use Illuminate\Http\UploadedFile;

final class TestImage
{
    /**
     * 1x1 PNG that validates with getimagesize without requiring the GD extension.
     */
    public static function png(string $filename = 'face.png'): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'hrms_png_');
        $target = $path.'.png';
        rename($path, $target);

        file_put_contents($target, base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg=='
        ));

        return new UploadedFile($target, $filename, 'image/png', null, true);
    }

    public static function oversizedJpegStub(string $filename = 'large.jpg'): UploadedFile
    {
        return UploadedFile::fake()->create($filename, 3000, 'image/jpeg');
    }

    public static function spoofedJpeg(string $filename = 'payload.jpg'): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($filename, '<?php echo "not an image";');
    }
}
