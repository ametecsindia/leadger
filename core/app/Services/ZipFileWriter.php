<?php

namespace App\Services;

/**
 * A plain-PHP zip writer for servers without the PHP zip module (ZipArchive). It offers the ZipArchive methods the
 * bulk downloads use: open(), addFile(), addFromString(), setCompressionName() and close(). Files are stored without
 * compression (audio is already compressed) and copied in chunks, so big recordings are never loaded into memory.
 * Limit: under 4 GB per file and per archive (no ZIP64), far above a 500-recording download.
 */
class ZipFileWriter
{
    /** @var resource|null */
    private $fh = null;
    private string $central = '';
    private int $count = 0;
    private int $offset = 0;

    /** Same meaning as ZipArchive::open: true when the file could be created. */
    public function open(string $path, int $flags = 0): bool
    {
        $this->fh = @fopen($path, 'wb');
        return $this->fh !== false && $this->fh !== null;
    }

    public function addFile(string $filePath, string $name): bool
    {
        $size = @filesize($filePath);
        $crc = @hash_file('crc32b', $filePath);
        $in = @fopen($filePath, 'rb');
        if (!$this->fh || $size === false || $crc === false || !$in || $size >= 0xFFFFFFFF) {
            if ($in) fclose($in);
            return false;
        }
        $this->header($name, (int)hexdec($crc), $size);
        $copied = stream_copy_to_stream($in, $this->fh);
        fclose($in);
        $this->offset += (int)$copied;
        return $copied === $size;
    }

    public function addFromString(string $name, string $contents): bool
    {
        if (!$this->fh || strlen($contents) >= 0xFFFFFFFF) {
            return false;
        }
        $this->header($name, crc32($contents), strlen($contents));
        fwrite($this->fh, $contents);
        $this->offset += strlen($contents);
        return true;
    }

    /** Everything is stored uncompressed already; kept so the caller can use ZipArchive and this class alike. */
    public function setCompressionName(string $name, int $method, int $flags = 0): bool
    {
        return true;
    }

    public function close(): bool
    {
        if (!$this->fh) {
            return false;
        }
        fwrite($this->fh, $this->central);
        fwrite($this->fh, pack('VvvvvVVv', 0x06054b50, 0, 0, $this->count, $this->count, strlen($this->central), $this->offset, 0));
        $ok = fclose($this->fh);
        $this->fh = null;
        return $ok;
    }

    /** Local file header now, central directory entry for close(). Flag 0x0800: the name is UTF-8. */
    private function header(string $name, int $crc, int $size): void
    {
        [$time, $date] = self::dosTime();
        $local = pack('VvvvvvVVVvv', 0x04034b50, 20, 0x0800, 0, $time, $date, $crc, $size, $size, strlen($name), 0) . $name;
        fwrite($this->fh, $local);
        $this->central .= pack('VvvvvvvVVVvvvvvVV', 0x02014b50, 20, 20, 0x0800, 0, $time, $date, $crc, $size, $size,
                strlen($name), 0, 0, 0, 0, 0, $this->offset) . $name;
        $this->offset += strlen($local);
        $this->count++;
    }

    private static function dosTime(): array
    {
        $t = getdate();
        return [($t['hours'] << 11) | ($t['minutes'] << 5) | intdiv($t['seconds'], 2),
            (max($t['year'] - 1980, 0) << 9) | ($t['mon'] << 5) | $t['mday']];
    }
}
