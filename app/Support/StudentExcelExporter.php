<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StudentExcelExporter
{
    /**
     * @param  Collection<int, User>  $students
     * @param  array<string, string>  $standards
     */
    public static function download(Collection $students, array $standards): StreamedResponse
    {
        $filename = 'students-'.now()->format('Y-m-d').'.xls';

        return response()->streamDownload(function () use ($students, $standards) {
            echo self::workbook($students, $standards);
        }, $filename, [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
        ]);
    }

    /**
     * @param  Collection<int, User>  $students
     * @param  array<string, string>  $standards
     */
    public static function workbook(Collection $students, array $standards): string
    {
        $rows = '';
        $index = 1;
        foreach ($students as $student) {
            $standardKey = (string) $student->standard;
            $standardLabel = $standards[$standardKey] ?? $student->standardLabel();
            $medium = (string) $student->medium;
            $approved = (bool) $student->is_approved;
            $email = trim((string) $student->email);
            $band = $index % 2 === 0 ? 'Alt' : 'Base';

            $rows .= '<Row>';
            $rows .= self::cell((string) $index, 'Number', $band);
            $rows .= self::cell($student->name, 'String', $index % 2 === 0 ? 'NameAlt' : 'Name');
            $rows .= self::cell((string) $student->mobile, 'String', $band);
            $rows .= self::cell($email !== '' ? $email : '—', 'String', $email !== '' ? 'Email' : 'Missing');
            $rows .= self::cell($standardLabel, 'String', self::standardStyle($standardKey));
            $rows .= self::cell(ucfirst($medium), 'String', $medium === 'english' ? 'English' : 'Gujarati');
            $rows .= self::cell($approved ? 'Approved' : 'Pending', 'String', $approved ? 'Approved' : 'Pending');
            $rows .= self::cell(optional($student->created_at)->format('d M Y') ?? '', 'String', $band);
            $rows .= '</Row>';
            $index++;
        }

        $title = 'Gses Chaturji — Students ('.$students->count().')';
        $subtitle = 'Exported '.now()->format('d M Y, h:i A');

        return '<?xml version="1.0" encoding="UTF-8"?>'
            .'<?mso-application progid="Excel.Sheet"?>'
            .'<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">'
            .self::styles()
            .'<Worksheet ss:Name="Students"><Table>'
            .'<Column ss:Width="46"/>'
            .'<Column ss:Width="220"/>'
            .'<Column ss:Width="120"/>'
            .'<Column ss:Width="240"/>'
            .'<Column ss:Width="130"/>'
            .'<Column ss:Width="100"/>'
            .'<Column ss:Width="100"/>'
            .'<Column ss:Width="110"/>'
            .'<Row ss:Height="28"><Cell ss:MergeAcross="7" ss:StyleID="Title"><Data ss:Type="String">'.self::xml($title).'</Data></Cell></Row>'
            .'<Row><Cell ss:MergeAcross="7" ss:StyleID="Subtitle"><Data ss:Type="String">'.self::xml($subtitle).'</Data></Cell></Row>'
            .'<Row ss:Height="22">'
            .self::cell('No', 'String', 'Header')
            .self::cell('Student', 'String', 'Header')
            .self::cell('Mobile', 'String', 'Header')
            .self::cell('Email', 'String', 'Header')
            .self::cell('Standard', 'String', 'Header')
            .self::cell('Medium', 'String', 'Header')
            .self::cell('Status', 'String', 'Header')
            .self::cell('Joined', 'String', 'Header')
            .'</Row>'
            .$rows
            .'</Table>'
            .'<WorksheetOptions xmlns="urn:schemas-microsoft-com:office:excel">'
            .'<FreezePanes/><FrozenNoScroll/>'
            .'<SplitHorizontal>3</SplitHorizontal><TopRowBottomPane>3</TopRowBottomPane>'
            .'</WorksheetOptions>'
            .'</Worksheet></Workbook>';
    }

    private static function cell(string $value, string $type, string $style): string
    {
        return '<Cell ss:StyleID="'.$style.'"><Data ss:Type="'.$type.'">'.self::xml($value).'</Data></Cell>';
    }

    private static function xml(string $value): string
    {
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

    private static function styles(): string
    {
        $styles = [
            'Title' => ['bg' => '#0F3D32', 'color' => '#FFFFFF', 'bold' => true, 'size' => 16],
            'Subtitle' => ['bg' => '#E7F6EF', 'color' => '#0F3D32', 'bold' => false, 'size' => 11],
            'Header' => ['bg' => '#1E3A5F', 'color' => '#FFFFFF', 'bold' => true, 'size' => 11],
            'Base' => ['bg' => '#FFFFFF', 'color' => '#1E293B', 'bold' => false, 'size' => 11, 'align' => 'Center'],
            'Alt' => ['bg' => '#F4FBF7', 'color' => '#1E293B', 'bold' => false, 'size' => 11, 'align' => 'Center'],
            'Name' => ['bg' => '#FFFFFF', 'color' => '#0F172A', 'bold' => true, 'size' => 11, 'align' => 'Left'],
            'NameAlt' => ['bg' => '#F4FBF7', 'color' => '#0F172A', 'bold' => true, 'size' => 11, 'align' => 'Left'],
            'Email' => ['bg' => '#EFF6FF', 'color' => '#1E3A8A', 'bold' => false, 'size' => 11, 'align' => 'Left'],
            'Missing' => ['bg' => '#FEE2E2', 'color' => '#991B1B', 'bold' => false, 'size' => 11],
            'Approved' => ['bg' => '#DCFCE7', 'color' => '#166534', 'bold' => true, 'size' => 11],
            'Pending' => ['bg' => '#FFEDD5', 'color' => '#9A3412', 'bold' => true, 'size' => 11],
            'English' => ['bg' => '#DBEAFE', 'color' => '#1D4ED8', 'bold' => true, 'size' => 11],
            'Gujarati' => ['bg' => '#FEF3C7', 'color' => '#B45309', 'bold' => true, 'size' => 11],
            'Std6' => ['bg' => '#FCE7F3', 'color' => '#9D174D', 'bold' => true, 'size' => 11],
            'Std7' => ['bg' => '#EDE9FE', 'color' => '#5B21B6', 'bold' => true, 'size' => 11],
            'Std8' => ['bg' => '#DBEAFE', 'color' => '#1E40AF', 'bold' => true, 'size' => 11],
            'Std9' => ['bg' => '#CFFAFE', 'color' => '#155E75', 'bold' => true, 'size' => 11],
            'Std10' => ['bg' => '#D1FAE5', 'color' => '#065F46', 'bold' => true, 'size' => 11],
            'Std11' => ['bg' => '#FEF3C7', 'color' => '#92400E', 'bold' => true, 'size' => 11],
            'Std12' => ['bg' => '#FFEDD5', 'color' => '#9A3412', 'bold' => true, 'size' => 11],
        ];

        $xml = '<Styles>';
        foreach ($styles as $id => $style) {
            $bold = $style['bold'] ? '<Font ss:Bold="1" ss:Color="'.$style['color'].'" ss:Size="'.$style['size'].'" ss:FontName="Calibri"/>' : '<Font ss:Color="'.$style['color'].'" ss:Size="'.$style['size'].'" ss:FontName="Calibri"/>';
            $align = $style['align'] ?? (($id === 'Title' || $id === 'Subtitle') ? 'Left' : 'Center');
            $xml .= '<Style ss:ID="'.$id.'">'
                .'<Alignment ss:Vertical="Center" ss:Horizontal="'.$align.'"/>'
                .'<Borders>'
                .'<Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/>'
                .'<Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/>'
                .'<Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/>'
                .'<Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/>'
                .'</Borders>'
                .$bold
                .'<Interior ss:Color="'.$style['bg'].'" ss:Pattern="Solid"/>'
                .'</Style>';
        }
        $xml .= '</Styles>';

        return $xml;
    }
}
