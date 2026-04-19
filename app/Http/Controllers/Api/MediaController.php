<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class MediaController extends Controller
{
    public function show($path) {
        $full_path = storage_path('app/public/' . $path);

        if (!file_exists($full_path)) {
            abort(404);
        }

        $mime = mime_content_type($full_path);
        $size = filesize($full_path);

        $headers = [
            'Content-Type' => mime_content_type($full_path),
            'Accept-Ranges' => 'bytes',
        ];

        if (request()->headers->has('Range')) {
            $range = request()->header('Range');

            preg_match('/bytes=(\d+)-(\d*)/', $range, $matches);

            $start = intval($matches[1]);
            $end = $matches[2] !== '' ? intval($matches[2]) : $size - 1;

            $length = $end - $start + 1;

            $file = fopen($full_path, 'rb');
            fseek($file, $start);

            return response()->stream(function() use ($file, $length) {
                $remaining = $length;

                while ($remaining > 0 && !feof($file)) {
                    $read = $remaining > 8192 ? 8192 : $remaining;
                    echo fread($file, $read);
                    $remaining -= $read;
                }

                fclose($file);
            }, 206, [
                'Content-Type' => $mime,
                'Content-Length' => $length,
                'Content-Range' => "bytes $start-$end/$size",
                'Accept-Ranges' => 'bytes',
            ]);
        }

        return response()->file($full_path, $headers);
    }
}
