<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Collection;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use ZipArchive;

class StudentExcelExporter
{
    /**
     * @param  Collection<int, User>  $students
     * @param  array<string, string>  $standards
     */
    public static function download(Collection $students, array $standards): BinaryFileResponse
    {
        $filename = 'students-'.now()->format('Y-m-d').'.xlsx';
        $path = storage_path('app/students-'.uniqid('', true).'.xlsx');

        self::write($path, $students, $standards);

        return response()->download($path, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    /**
     * @param  Collection<int, User>  $students
     * @param  array<string, string>  $standards
     */
    public static function write(string $path, Collection $students, array $standards): void
    {
        $styles = self::styleDefinitions();
        $styleIndex = [];
        $i = 0;
        foreach ($styles as $name => $style) {
            $styleIndex[$name] = $i;
            $i++;
        }

        $sheet = self::sheet($students, $standards, $styleIndex);
        $files = [
            '[Content_Types].xml' => self::contentTypes(),
            '_rels/.rels' => self::rootRels(),
            'xl/workbook.xml' => self::workbook(),
            'xl/_rels/workbook.xml.rels' => self::workbookRels(),
            'xl/styles.xml' => self::stylesXml($styles),
            'xl/worksheets/sheet1.xml' => $sheet,
        ];

        $zip = new ZipArchive;
        if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Could not create the Excel file.');
        }
        foreach ($files as $name => $contents) {
            $zip->addFromString($name, $contents);
        }
        $zip->close();
    }

    /**
     * @param  Collection<int, User>  $students
     * @param  array<string, string>  $standards
     * @param  array<string, int>  $styleIndex
     */
    private static function sheet(Collection $students, array $standards, array $styleIndex): string
    {
        $title = 'Gses Chaturji — Students ('.$students->count().')';
        $subtitle = 'Exported '.now()->format('d M Y, h:i A');

        $rows = self::row(1, 28, [
            ['A', $title, $styleIndex['Title']],
        ]);
        $rows .= self::row(2, 18, [
            ['A', $subtitle, $styleIndex['Subtitle']],
        ]);
        $rows .= self::row(3, 22, [
            ['A', 'No', $styleIndex['Header']],
            ['B', 'Student', $styleIndex['Header']],
            ['C', 'Mobile', $styleIndex['Header']],
            ['D', 'Email', $styleIndex['Header']],
            ['E', 'Standard', $styleIndex['Header']],
            ['F', 'Medium', $styleIndex['Header']],
            ['G', 'Status', $styleIndex['Header']],
            ['H', 'Joined', $styleIndex['Header']],
        ]);

        $line = 4;
        $index = 1;
        foreach ($students as $student) {
            $standardKey = (string) $student->standard;
            $standardLabel = $standards[$standardKey] ?? $student->standardLabel();
            $medium = (string) $student->medium;
            $approved = (bool) $student->is_approved;
            $email = trim((string) $student->email);
            $band = $index % 2 === 0 ? 'Alt' : 'Base';

            $rows .= self::row($line, 18, [
                ['A', (string) $index, $styleIndex[$band]],
                ['B', $student->name, $styleIndex[$index % 2 === 0 ? 'NameAlt' : 'Name']],
                ['C', (string) $student->mobile, $styleIndex[$band]],
                ['D', $email !== '' ? $email : '—', $styleIndex[$email !== '' ? 'Email' : 'Missing']],
                ['E', $standardLabel, $styleIndex[self::standardStyle($standardKey)]],
                ['F', ucfirst($medium !== '' ? $medium : '—'), $styleIndex[$medium === 'english' ? 'English' : ($medium === 'gujarati' ? 'Gujarati' : $band)]],
                ['G', $approved ? 'Approved' : 'Pending', $styleIndex[$approved ? 'Approved' : 'Pending']],
                ['H', optional($student->created_at)->format('d M Y') ?? '', $styleIndex[$band]],
            ]);
            $line++;
            $index++;
        }

        $lastRow = max(3, $line - 1);

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<dimension ref="A1:H'.$lastRow.'"/>'
            .'<sheetViews><sheetView workbookViewId="0"><pane ySplit="3" topLeftCell="A4" activePane="bottomLeft" state="frozen"/><selection pane="bottomLeft" activeCell="A4" sqref="A4"/></sheetView></sheetViews>'
            .'<cols>'
            .'<col min="1" max="1" width="8" customWidth="1"/>'
            .'<col min="2" max="2" width="32" customWidth="1"/>'
            .'<col min="3" max="3" width="16" customWidth="1"/>'
            .'<col min="4" max="4" width="36" customWidth="1"/>'
            .'<col min="5" max="5" width="18" customWidth="1"/>'
            .'<col min="6" max="6" width="14" customWidth="1"/>'
            .'<col min="7" max="7" width="14" customWidth="1"/>'
            .'<col min="8" max="8" width="16" customWidth="1"/>'
            .'</cols>'
            .'<sheetData>'.$rows.'</sheetData>'
            .'<autoFilter ref="A3:H'.$lastRow.'"/>'
            .'<mergeCells count="2"><mergeCell ref="A1:H1"/><mergeCell ref="A2:H2"/></mergeCells>'
            .'</worksheet>';
    }

    /**
     * @param  list<array{0: string, 1: string, 2: int}>  $cells
     */
    private static function row(int $number, int $height, array $cells): string
    {
        $xml = '<row r="'.$number.'" ht="'.$height.'" customHeight="1">';
        foreach ($cells as [$column, $value, $style]) {
            $xml .= '<c r="'.$column.$number.'" t="inlineStr" s="'.$style.'"><is><t>'.self::xml($value).'</t></is></c>';
        }
        $xml .= '</row>';

        return $xml;
    }

    private static function xml(string $value): string
    {
        $value = preg_replace('/[^\x{9}\x{A}\x{D}\x{20}-\x{D7FF}\x{E000}-\x{FFFD}]/u', '', $value) ?? $value;

        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    private static function standardStyle(string $standard): string
    {
        return match (true) {
            str_contains($standard, '6') => 'Std6',
            str_contains($standard, '7') => 'Std7',
            str_contains($standard, '8') => 'Std8',
            str_contains($standard, '9') => 'Std9',
            str_contains($standard, '10') => 'Std10',
            str_contains($standard, '11') => 'Std11',
            str_contains($standard, '12') => 'Std12',
            default => 'Base',
        };
    }

    /**
     * @return array<string, array{bg: string, color: string, bold: bool, size: int, align: string}>
     */
    private static function styleDefinitions(): array
    {
        return [
            'Title' => ['bg' => 'FF0F3D32', 'color' => 'FFFFFFFF', 'bold' => true, 'size' => 16, 'align' => 'left'],
            'Subtitle' => ['bg' => 'FFE7F6EF', 'color' => 'FF0F3D32', 'bold' => false, 'size' => 11, 'align' => 'left'],
            'Header' => ['bg' => 'FF1E3A5F', 'color' => 'FFFFFFFF', 'bold' => true, 'size' => 11, 'align' => 'center'],
            'Base' => ['bg' => 'FFFFFFFF', 'color' => 'FF1E293B', 'bold' => false, 'size' => 11, 'align' => 'center'],
            'Alt' => ['bg' => 'FFF4FBF7', 'color' => 'FF1E293B', 'bold' => false, 'size' => 11, 'align' => 'center'],
            'Name' => ['bg' => 'FFFFFFFF', 'color' => 'FF0F172A', 'bold' => true, 'size' => 11, 'align' => 'left'],
            'NameAlt' => ['bg' => 'FFF4FBF7', 'color' => 'FF0F172A', 'bold' => true, 'size' => 11, 'align' => 'left'],
            'Email' => ['bg' => 'FFEFF6FF', 'color' => 'FF1E3A8A', 'bold' => false, 'size' => 11, 'align' => 'left'],
            'Missing' => ['bg' => 'FFFEE2E2', 'color' => 'FF991B1B', 'bold' => false, 'size' => 11, 'align' => 'center'],
            'Approved' => ['bg' => 'FFDCFCE7', 'color' => 'FF166534', 'bold' => true, 'size' => 11, 'align' => 'center'],
            'Pending' => ['bg' => 'FFFFEDD5', 'color' => 'FF9A3412', 'bold' => true, 'size' => 11, 'align' => 'center'],
            'English' => ['bg' => 'FFDBEAFE', 'color' => 'FF1D4ED8', 'bold' => true, 'size' => 11, 'align' => 'center'],
            'Gujarati' => ['bg' => 'FFFEF3C7', 'color' => 'FFB45309', 'bold' => true, 'size' => 11, 'align' => 'center'],
            'Std6' => ['bg' => 'FFFCE7F3', 'color' => 'FF9D174D', 'bold' => true, 'size' => 11, 'align' => 'center'],
            'Std7' => ['bg' => 'FFEDE9FE', 'color' => 'FF5B21B6', 'bold' => true, 'size' => 11, 'align' => 'center'],
            'Std8' => ['bg' => 'FFDBEAFE', 'color' => 'FF1E40AF', 'bold' => true, 'size' => 11, 'align' => 'center'],
            'Std9' => ['bg' => 'FFCFFAFE', 'color' => 'FF155E75', 'bold' => true, 'size' => 11, 'align' => 'center'],
            'Std10' => ['bg' => 'FFD1FAE5', 'color' => 'FF065F46', 'bold' => true, 'size' => 11, 'align' => 'center'],
            'Std11' => ['bg' => 'FFFEF3C7', 'color' => 'FF92400E', 'bold' => true, 'size' => 11, 'align' => 'center'],
            'Std12' => ['bg' => 'FFFFEDD5', 'color' => 'FF9A3412', 'bold' => true, 'size' => 11, 'align' => 'center'],
        ];
    }

    /**
     * @param  array<string, array{bg: string, color: string, bold: bool, size: int, align: string}>  $styles
     */
    private static function stylesXml(array $styles): string
    {
        $fonts = '';
        $fills = '<fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill>';
        $xfs = '';
        $fontCount = 0;
        $fillCount = 2;

        foreach ($styles as $style) {
            $bold = $style['bold'] ? '<b/>' : '';
            $fonts .= '<font>'.$bold.'<sz val="'.$style['size'].'"/><color rgb="'.$style['color'].'"/><name val="Calibri"/></font>';
            $fills .= '<fill><patternFill patternType="solid"><fgColor rgb="'.$style['bg'].'"/><bgColor rgb="'.$style['bg'].'"/></patternFill></fill>';
            $xfs .= '<xf numFmtId="0" fontId="'.$fontCount.'" fillId="'.$fillCount.'" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="'.$style['align'].'" vertical="center"/></xf>';
            $fontCount++;
            $fillCount++;
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<fonts count="'.$fontCount.'">'.$fonts.'</fonts>'
            .'<fills count="'.$fillCount.'">'.$fills.'</fills>'
            .'<borders count="2">'
            .'<border><left/><right/><top/><bottom/><diagonal/></border>'
            .'<border><left style="thin"><color rgb="FFE2E8F0"/></left><right style="thin"><color rgb="FFE2E8F0"/></right><top style="thin"><color rgb="FFE2E8F0"/></top><bottom style="thin"><color rgb="FFE2E8F0"/></bottom><diagonal/></border>'
            .'</borders>'
            .'<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            .'<cellXfs count="'.$fontCount.'">'.$xfs.'</cellXfs>'
            .'</styleSheet>';
    }

    private static function contentTypes(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            .'<Default Extension="xml" ContentType="application/xml"/>'
            .'<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            .'<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            .'<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            .'</Types>';
    }

    private static function rootRels(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            .'</Relationships>';
    }

    private static function workbook(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            .'<sheets><sheet name="Students" sheetId="1" r:id="rId1"/></sheets>'
            .'</workbook>';
    }

    private static function workbookRels(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            .'<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            .'</Relationships>';
    }
}
