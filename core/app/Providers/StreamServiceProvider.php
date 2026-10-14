<?php

namespace App\Providers;

use Arr;
use File;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Request;
use App\Facades\ResponseFacade as Response;
use Illuminate\Support\ServiceProvider;
use Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StreamServiceProvider extends ServiceProvider
{
    const MAX_CHUNK_SIZE = 4000000;//4MB

    public function boot()
    {
        Response::macro('anyFileStream', function (string $filepath) {
            $headers = [
                'Content-Type' => File::mimeType($filepath),
                'Content-Size' => File::size($filepath)
            ];

            return response()->stream(function () use ($filepath) {
                echo File::get($filepath);
            }, 200, $headers);
        });

        //Custom CSV file download response
        //Usage: response()->streamDownloadCSV($filename, $titles, $columns, $records)
        Response::macro('streamDownloadCSV', function ($filename, $titles, $columns, $records) {
            return Response::stream(function () use ($filename, $titles, $columns, $records) {
                $filename = Str::endsWith($filename, '.csv') ? $filename : $filename . '.csv';
                header('Content-Type: application/vnd.ms-excel; charset=utf-8');
                header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
                header('Content-Disposition: attachment; filename=' . $filename);
                header('Expires: 0');
                header('Pragma: public');

                $handle = fopen('php://output', 'wb');

                fputcsv($handle, $titles);

                foreach ($records as $record) {
                    $csvRecord = [];

                    foreach ($columns as $column) {
                        $csvRecord[] = Arr::get($record, $column);
                    }

                    fputcsv($handle, $csvRecord);
                }
                flush();
                fclose($handle);
            });
        });

        Response::macro('videoStream',
            /**
             * @param string       $path
             * @param Request|null $request
             *
             * @return Application|ResponseFactory|Response|StreamedResponse
             */
            function (string $path, Request $request = null) {
                $request = $request ?? request();

                if (!File::exists($path))
                    return response('File not found', 404);

                $type = File::mimeType($path);
                $size = File::size($path);

                $start = 0;
                $end = $size;

                $headers = [
                    'Content-Type'  => $type,
                    'Accept-Ranges' => "bytes $start-$end",
                    'Content-Size'  => $size,
                    'Cache-Control' => 'max-age=2592000, public',
                    'Expires'       => now()->addMonth()->format('D, d M Y H:i:s') . ' GMT',
                    'Last-Modified' => gmdate('D, d M Y H:i:s', @filemtime($path)) . ' GMT',
                ];

                $range = $request->header('Range', "bytes=0-" . self::MAX_CHUNK_SIZE); //bytes=$startByte-$endByte

                $range = Str::of($range)->trim()->replace('bytes=', '');

                [$rangeStart, $rangeEnd] = Str::of($range)->explode('-');

                $start = intval(str_replace('-', '', $rangeStart));
                $end = intval(str_replace('-', '', $rangeEnd));

                $end = $end == 0 ? ($start + self::MAX_CHUNK_SIZE) : $end;
                $end = min($end, $size);

                $length = $end - $start;
                $length = $length === 0 ? 1 : $length;

                if ($length > self::MAX_CHUNK_SIZE) {
                    $end = $start + self::MAX_CHUNK_SIZE;
                    $length = self::MAX_CHUNK_SIZE;
                }

                $headers['Content-Range'] = "bytes $start-$end/$size";
                $headers['Content-Length'] = $length;
                $status = $end >= $size ? 200 : 206;

                return response()->stream(
                    function () use ($path, $start, $length) {
                        $file = fopen($path, 'rb');
                        fseek($file, $start);
                        while (!feof($file) && !connection_aborted()) {
                            echo fread($file, $length);
                        }
                        fclose($file);
                    }
                    , $status, $headers);
            });
    }
}
