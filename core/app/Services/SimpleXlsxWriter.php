<?php

namespace App\Services;

/**
 * Minimal single-sheet .xlsx writer (Office Open XML via ZipArchive; no extra Composer package).
 * Column types: 'text' (stored as text, so phone numbers / IDs keep every digit), 'date' (Carbon/DateTime,
 * shown dd-mmm-yyyy), 'time' (Carbon/DateTime, shown hh:mm AM/PM), 'datetime' (shown dd-mmm-yyyy hh:mm AM/PM),
 * 'datetime_long' (shown dd mmm yyyy hh:mm AM/PM), 'int' (number, #,##0), 'percent' (fraction, shown 0.0%),
 * 'duration' (seconds, shown [h]:mm:ss). A value that does not fit its column type (e.g. "-") is written as text.
 * Header row is bold with a light fill and frozen; optional title lines can go above it.
 */
class SimpleXlsxWriter
{
    /**
     * @param string[] $headers
     * @param array[]  $rows   each row: values in header order
     * @param string[] $types  per column: text | date | time | datetime | datetime_long | int | percent | duration
     * @param int[]    $widths per column width in characters
     * @param array[]  $preamble optional lines above the table (the first is the title, the rest label + value),
     *                 followed by one blank row
     * @return string path of the written temporary .xlsx file (caller deletes it)
     */
    public static function write(array $headers, array $rows, array $types, array $widths, string $sheetName = 'Sheet1', array $preamble = []): string
    {
        $path = tempnam(sys_get_temp_dir(), 'xlsx');
        $zip = new \ZipArchive();
        if ($zip->open($path, \ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('Could not create the Excel file.');
        }

        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            . '</Types>');
        $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '</Relationships>');
        $zip->addFromString('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets><sheet name="' . self::esc(mb_substr($sheetName, 0, 31)) . '" sheetId="1" r:id="rId1"/></sheets></workbook>');
        $zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            . '</Relationships>');
        // Styles: 0 normal, 1 bold header, 2 date, 3 time, 4 datetime, 5 int, 6 percent, 7 duration, 8 datetime_long,
        // 9 title, 10 bold label
        $zip->addFromString('xl/styles.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<numFmts count="6"><numFmt numFmtId="164" formatCode="dd\-mmm\-yyyy"/><numFmt numFmtId="165" formatCode="hh:mm AM/PM"/>'
            . '<numFmt numFmtId="166" formatCode="dd\-mmm\-yyyy hh:mm AM/PM"/><numFmt numFmtId="167" formatCode="0.0%"/>'
            . '<numFmt numFmtId="168" formatCode="[h]:mm:ss"/><numFmt numFmtId="169" formatCode="dd mmm yyyy hh:mm AM/PM"/></numFmts>'
            . '<fonts count="3"><font><sz val="11"/><name val="Calibri"/></font><font><b/><sz val="11"/><name val="Calibri"/></font>'
            . '<font><b/><sz val="14"/><name val="Calibri"/></font></fonts>'
            . '<fills count="3"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FFE2E8F0"/><bgColor indexed="64"/></patternFill></fill></fills>'
            . '<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="11"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            . '<xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1"/>'
            . '<xf numFmtId="164" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/>'
            . '<xf numFmtId="165" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/>'
            . '<xf numFmtId="166" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/>'
            . '<xf numFmtId="3" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/>'
            . '<xf numFmtId="167" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/>'
            . '<xf numFmtId="168" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/>'
            . '<xf numFmtId="169" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/>'
            . '<xf numFmtId="0" fontId="2" fillId="0" borderId="0" xfId="0" applyFont="1"/>'
            . '<xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0" applyFont="1"/></cellXfs>'
            . '</styleSheet>');

        // Title lines (if any), a blank row, then the header row (frozen) and the data
        $headerRow = $preamble ? count($preamble) + 2 : 1;
        $sheet = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<sheetViews><sheetView workbookViewId="0"><pane ySplit="' . $headerRow . '" topLeftCell="A' . ($headerRow + 1) . '" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>'
            . '<cols>';
        foreach ($widths as $i => $w) {
            $sheet .= '<col min="' . ($i + 1) . '" max="' . ($i + 1) . '" width="' . (int)$w . '" customWidth="1"/>';
        }
        $sheet .= '</cols><sheetData>';
        foreach (array_values($preamble) as $i => $line) {
            $sheet .= self::row($i + 1, array_values($line), $i === 0 ? ['title'] : ['label']);
        }
        $sheet .= self::row($headerRow, $headers, array_fill(0, count($headers), 'header'));
        foreach (array_values($rows) as $i => $row) {
            $sheet .= self::row($headerRow + 1 + $i, $row, $types);
        }
        $sheet .= '</sheetData><autoFilter ref="A' . $headerRow . ':' . self::col(count($headers) - 1) . ($headerRow + count($rows)) . '"/></worksheet>';
        $zip->addFromString('xl/worksheets/sheet1.xml', $sheet);
        $zip->close();
        return $path;
    }

    private static function row(int $r, array $values, array $types): string
    {
        $xml = '<row r="' . $r . '">';
        foreach (array_values($values) as $i => $v) {
            $ref = self::col($i) . $r;
            $type = $types[$i] ?? 'text';
            if (in_array($type, ['date', 'time', 'datetime', 'datetime_long'], true) && $v instanceof \DateTimeInterface) {
                // Excel serial: days since 1899-12-30; dates keep only the day, times only the fraction, datetime both
                $serial = $v->getTimestamp() + $v->getOffset();
                $days = floor($serial / 86400) + 25569;
                $fraction = ($serial % 86400) / 86400;
                $num = $type === 'date' ? $days : ($type === 'time' ? round($fraction, 8) : round($days + $fraction, 8));
                $style = ['date' => 2, 'time' => 3, 'datetime' => 4, 'datetime_long' => 8][$type];
                $xml .= '<c r="' . $ref . '" s="' . $style . '"><v>' . $num . '</v></c>';
            } elseif (in_array($type, ['int', 'percent', 'duration'], true) && (is_int($v) || is_float($v))) {
                $num = $type === 'duration' ? round($v / 86400, 10) : $v;
                $style = ['int' => 5, 'percent' => 6, 'duration' => 7][$type];
                $xml .= '<c r="' . $ref . '" s="' . $style . '"><v>' . $num . '</v></c>';
            } elseif ($v === null || $v === '') {
                continue;
            } else {
                $style = ['header' => 1, 'title' => 9, 'label' => 10][$type] ?? null;
                $xml .= '<c r="' . $ref . '" t="inlineStr"' . ($style ? ' s="' . $style . '"' : '') . '><is><t xml:space="preserve">' . self::esc((string)$v) . '</t></is></c>';
            }
        }
        return $xml . '</row>';
    }

    /** 0 -> A, 25 -> Z, 26 -> AA */
    private static function col(int $i): string
    {
        $s = '';
        for ($i++; $i > 0; $i = intdiv($i - 1, 26)) {
            $s = chr(65 + ($i - 1) % 26) . $s;
        }
        return $s;
    }

    private static function esc(string $s): string
    {
        // drop characters XML 1.0 does not allow, then escape
        $s = preg_replace('/[^\x{9}\x{A}\x{D}\x{20}-\x{D7FF}\x{E000}-\x{FFFD}]/u', '', $s) ?? '';
        return htmlspecialchars($s, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
