<?php

namespace App\Services;

/**
 * The files of one .xlsx, written as a zip: with ZipArchive when PHP has the zip module, otherwise by plain PHP
 * (deflate with zlib when available, else stored). Used by SimpleXlsxWriter so Excel exports also work on a server
 * without php-zip.
 */
class XlsxParts
{
    /** @var array<string, string> name => content */
    private array $files = [];

    public function addFromString(string $name, string $content): void
    {
        $this->files[$name] = $content;
    }

    /** Writes the zip to a temporary file and returns its path (the caller deletes it). */
    public function save(): string
    {
        $path = self::tempFile();
        if (class_exists(\ZipArchive::class)) {
            $zip = new \ZipArchive();
            if ($zip->open($path, \ZipArchive::OVERWRITE) !== true) {
                throw new \RuntimeException('Could not create the Excel file.');
            }
            foreach ($this->files as $name => $content) {
                $zip->addFromString($name, $content);
            }
            $zip->close();
            return $path;
        }
        if (file_put_contents($path, $this->zipBytes()) === false) {
            throw new \RuntimeException('Could not write the Excel file.');
        }
        return $path;
    }

    /** A zip archive of the files (local headers, data, central directory). */
    public function zipBytes(): string
    {
        $deflate = function_exists('gzdeflate');
        [$time, $date] = self::dosTime();
        $out = '';
        $central = '';
        foreach ($this->files as $name => $content) {
            $crc = crc32($content);
            $data = $deflate ? gzdeflate($content, 6) : $content;
            $method = $deflate ? 8 : 0;
            $offset = strlen($out);
            $out .= pack('VvvvvvVVVvv', 0x04034b50, 20, 0x0800, $method, $time, $date, $crc, strlen($data), strlen($content), strlen($name), 0)
                . $name . $data;
            $central .= pack('VvvvvvvVVVvvvvvVV', 0x02014b50, 20, 20, 0x0800, $method, $time, $date, $crc, strlen($data),
                    strlen($content), strlen($name), 0, 0, 0, 0, 0, $offset) . $name;
        }
        return $out . $central
            . pack('VvvvvVVv', 0x06054b50, 0, 0, count($this->files), count($this->files), strlen($central), strlen($out), 0);
    }

    /** A temporary file: the system temp folder, or storage/app when that is not writable. */
    private static function tempFile(): string
    {
        $dir = sys_get_temp_dir();
        if (!is_dir($dir) || !is_writable($dir)) {
            $dir = storage_path('app');
        }
        $path = tempnam($dir, 'xlsx');
        if ($path === false) {
            throw new \RuntimeException('Could not create the Excel file.');
        }
        return $path;
    }

    /** Now in MS-DOS time / date format (zip headers). */
    private static function dosTime(): array
    {
        $t = getdate();
        return [($t['hours'] << 11) | ($t['minutes'] << 5) | intdiv($t['seconds'], 2),
            (max($t['year'] - 1980, 0) << 9) | ($t['mon'] << 5) | $t['mday']];
    }
}
