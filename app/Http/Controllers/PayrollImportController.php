<?php

namespace App\Http\Controllers;

use App\Models\Payroll;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use PhpOffice\PhpSpreadsheet\IOFactory;

class PayrollImportController extends Controller
{
    public function index()
    {
        return Inertia::render('Payroll/PayrollImport');
    }

    public function preview(Request $request)
    {
        $request->validate([
            'file' => 'required|file',
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2020|max:2030',
        ]);

        // Validate file extension manually to support xlsm
        $allowedExtensions = ['csv', 'xlsx', 'xls', 'xlsm'];
        $extension = $request->file('file')->getClientOriginalExtension();
        if (!in_array(strtolower($extension), $allowedExtensions)) {
            return response()->json([
                'success' => false,
                'message' => 'Format file tidak didukung. Gunakan CSV, XLSX, XLS, atau XLSM.',
            ], 422);
        }

        try {
            $file = $request->file('file');
            $rows = [];
            $headers = [];

            // Handle CSV
            if ($file->getClientOriginalExtension() === 'csv') {
                $handle = fopen($file->getPathname(), 'r');
                $headers = fgetcsv($handle);
                
                $rowCount = 0;
                while ($rowCount < 5 && ($row = fgetcsv($handle)) !== false) {
                    $rows[] = $row;
                    $rowCount++;
                }
                fclose($handle);
                
                // Count total rows
                $handle = fopen($file->getPathname(), 'r');
                fgetcsv($handle); // Skip header
                $totalRows = 0;
                while (fgetcsv($handle) !== false) {
                    $totalRows++;
                }
                fclose($handle);
            } else {
                // For XLSX/XLS, use toArray() for consistent cell value extraction
                $spreadsheet = IOFactory::load($file->getPathname());
                $worksheet = $spreadsheet->getActiveSheet();
                $sheetData = $worksheet->toArray(null, true, true, false);

                // Detect template format and skip all header rows
                $headers = [];
                $dataStartRow = 0;
                
                foreach ($sheetData as $rowIdx => $row) {
                    $firstCell = trim((string)($row[0] ?? ''));
                    $firstLower = strtolower($firstCell);
                    
                    // Skip empty rows
                    if (empty($firstCell)) continue;
                    
                    // Skip title, section labels, and legend rows
                    if (str_contains($firstCell, 'DATA PENGGAJIAN') ||
                        str_contains($firstCell, 'IDENTITAS') ||
                        str_contains($firstCell, 'TAMBAHAN') ||
                        str_contains($firstCell, 'POTONGAN') ||
                        str_contains($firstCell, 'PENDAPATAN') ||
                        str_contains($firstCell, 'Kolom BIRU') ||
                        str_contains($firstCell, 'Kolom MERAH') ||
                        str_contains($firstCell, 'Kolom HIJAU') ||
                        str_starts_with($firstCell, '*') ||
                        str_starts_with($firstCell, '=') ||
                        str_starts_with($firstCell, '|')) {
                        continue;
                    }
                    
                    // Detect header row (NIP, NAMA, etc.)
                    if (in_array($firstLower, ['nip', 'no', 'nama', 'employee_id', 'employeeid'])) {
                        $headers = $row;
                        $dataStartRow = $rowIdx + 1;
                        continue;
                    }
                    
                    // Found first data row
                    if (empty($headers) && $rowIdx > 0) {
                        $headers = $sheetData[$rowIdx - 1];
                    }
                    $dataStartRow = $rowIdx;
                    break;
                }
                
                // Extract data rows starting from dataStartRow
                $dataRows = array_slice($sheetData, $dataStartRow);
                
                // Filter out notes/empty rows
                $dataRows = array_values(array_filter($dataRows, function ($row) {
                    $firstCell = trim((string)($row[0] ?? ''));
                    return !empty($firstCell) && !str_starts_with($firstCell, '*') && 
                           !str_starts_with($firstCell, '=') && !str_starts_with($firstCell, '|');
                }));

                $rows = array_slice($dataRows, 0, 5);
                $totalRows = count($dataRows);
            }

            // Ensure headers is always an array, not an object or associative array
            if (!is_array($headers)) {
                $headers = [];
            } else {
                $headers = array_values($headers); // Reset array keys to numeric 0,1,2...
            }

            // Ensure preview rows are always arrays
            $previewRows = array_map(function ($row) {
                return is_array($row) ? array_values($row) : [];
            }, $rows);

            return response()->json([
                'success' => true,
                'headers' => $headers,
                'preview' => $previewRows,
                'total_rows' => $totalRows,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error membaca file: ' . $e->getMessage(),
            ], 422);
        }
    }

    public function import(Request $request)
    {
        if (!$request->user()->isAdmin()) {
            abort(403);
        }

        $request->validate([
            'file' => 'required|file',
            'mapping' => 'required|string',
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2020|max:2030',
        ]);

        $mapping = json_decode($request->get('mapping'), true);
        if (!is_array($mapping)) {
            return response()->json([
                'success' => false,
                'message' => 'Format mapping tidak valid.',
            ], 422);
        }

        // Validate file extension manually to support xlsm
        $allowedExtensions = ['csv', 'xlsx', 'xls', 'xlsm'];
        $extension = $request->file('file')->getClientOriginalExtension();
        if (!in_array(strtolower($extension), $allowedExtensions)) {
            return response()->json([
                'success' => false,
                'message' => 'Format file tidak didukung. Gunakan CSV, XLSX, XLS, atau XLSM.',
            ], 422);
        }

        try {
            $file = $request->file('file');
            $month = $request->get('month');
            $year = $request->get('year');
            $rows = [];

            // Handle CSV
            if ($file->getClientOriginalExtension() === 'csv') {
                $handle = fopen($file->getPathname(), 'r');
                fgetcsv($handle); // Skip header
                while (($row = fgetcsv($handle)) !== false) {
                    if (!empty(array_filter($row))) {
                        $rows[] = $row;
                    }
                }
                fclose($handle);
            } else {
                // Handle XLSX/XLS using same logic as preview
                $spreadsheet = IOFactory::load($file->getPathname());
                $worksheet = $spreadsheet->getActiveSheet();
                $sheetData = $worksheet->toArray(null, true, true, false);

                $dataStartRow = 0;
                
                foreach ($sheetData as $rowIdx => $row) {
                    $firstCell = trim((string)($row[0] ?? ''));
                    $firstLower = strtolower($firstCell);
                    
                    if (empty($firstCell)) continue;
                    
                    // Skip title, section labels, and legend rows
                    if (str_contains($firstCell, 'DATA PENGGAJIAN') ||
                        str_contains($firstCell, 'IDENTITAS') ||
                        str_contains($firstCell, 'TAMBAHAN') ||
                        str_contains($firstCell, 'POTONGAN') ||
                        str_contains($firstCell, 'PENDAPATAN') ||
                        str_contains($firstCell, 'Kolom BIRU') ||
                        str_contains($firstCell, 'Kolom MERAH') ||
                        str_contains($firstCell, 'Kolom HIJAU') ||
                        str_starts_with($firstCell, '*') ||
                        str_starts_with($firstCell, '=') ||
                        str_starts_with($firstCell, '|')) {
                        continue;
                    }
                    
                    // Detect header row
                    if (in_array($firstLower, ['nip', 'no', 'nama', 'employee_id', 'employeeid'])) {
                        $dataStartRow = $rowIdx + 1;
                        continue;
                    }
                    
                    // Found first data row
                    $dataStartRow = $rowIdx;
                    break;
                }
                
                // Extract data rows starting from dataStartRow
                $dataRows = array_slice($sheetData, $dataStartRow);
                
                // Filter out notes/empty rows
                $rows = array_values(array_filter($dataRows, function ($row) {
                    $firstCell = trim((string)($row[0] ?? ''));
                    return !empty($firstCell) && !str_starts_with($firstCell, '*') && 
                           !str_starts_with($firstCell, '=') && !str_starts_with($firstCell, '|');
                }));
            }

            $imported = 0;
            $errors = [];

            foreach ($rows as $rowIndex => $row) {
                if (empty(array_filter($row))) continue;

                try {
                    $payrollData = $this->buildPayrollData($row, $mapping, $month, $year);
                    
                    // Check if payroll exists for this employee & period
                    $existing = Payroll::where('user_id', $payrollData['user_id'])
                        ->where('month', $month)
                        ->where('year', $year)
                        ->first();

                    if ($existing) {
                        $existing->update($payrollData);
                    } else {
                        Payroll::create($payrollData);
                    }
                    
                    $imported++;
                } catch (\Exception $e) {
                    $errors[] = "Baris " . ($rowIndex + 2) . ": " . $e->getMessage();
                }
            }

            return response()->json([
                'success' => true,
                'message' => "Berhasil import $imported penggajian untuk " . $this->getMonthName($month) . " $year",
                'imported' => $imported,
                'errors' => $errors,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 422);
        }
    }

    private function parseNumeric($value)
    {
        // If already numeric (from Excel reading), return as integer
        if (is_numeric($value)) {
            return (int) $value;
        }

        // Convert to string for processing
        $value = (string) $value;
        
        // Trim whitespace
        $value = trim($value);
        
        if (empty($value)) {
            return 0;
        }

        // Remove all whitespace
        $value = preg_replace('/\s+/', '', $value);

        // Check if value contains only digits (already an integer)
        if (ctype_digit($value)) {
            return (int) $value;
        }

        // Handle negative numbers
        $isNegative = strpos($value, '-') === 0;
        if ($isNegative) {
            $value = ltrim($value, '-');
        }

        // Find last occurrence of comma and period
        $lastComma = strrpos($value, ',');
        $lastPeriod = strrpos($value, '.');

        // Remove all separators and convert to integer
        if ($lastComma === false && $lastPeriod === false) {
            // No separators - just digits and possibly a sign
            $result = (int) $value;
        } elseif ($lastComma === false) {
            // Only period - remove it and convert
            $value = str_replace('.', '', $value);
            $result = (int) $value;
        } elseif ($lastPeriod === false) {
            // Only comma - remove it and convert
            $value = str_replace(',', '', $value);
            $result = (int) $value;
        } else {
            // Both exist - use the rightmost as decimal indicator if it's in last 3 positions
            if ($lastComma > $lastPeriod) {
                // Comma is rightmost
                if ($lastComma > strlen($value) - 4) {
                    // Treat as decimal separator (1-3 digits after)
                    $value = str_replace('.', '', $value);
                    $value = str_replace(',', '.', $value);
                    $result = (int) round((float) $value);
                } else {
                    // Treat as thousand separator
                    $value = str_replace(['.', ','], '', $value);
                    $result = (int) $value;
                }
            } else {
                // Period is rightmost - standard format with decimals
                if ($lastPeriod > strlen($value) - 4) {
                    // Treat as decimal separator
                    $value = str_replace(',', '', $value);
                    $result = (int) round((float) $value);
                } else {
                    // Treat as thousand separator
                    $value = str_replace([',', '.'], '', $value);
                    $result = (int) $value;
                }
            }
        }

        return $isNegative ? -$result : $result;
    }

    private function buildPayrollData($row, $mapping, $month, $year)
    {
        // Import hanya untuk potongan & koreksi admin.
        // Gaji, tunjangan, lembur, BPJS diambil dari sistem saat Generate.
        $data = [
            'user_id' => null,
            'month' => $month,
            'year' => $year,
            'salary_correction' => 0,
            'other_allowance' => 0,
            'cdt_deduction' => 0,
            'alpha_deduction' => 0,
            'cashbond_deduction' => 0,
            'piutang_obat_deduction' => 0,
            'salary_correction_deduction' => 0,
            'bank_admin_deduction' => 0,
            'pph21' => 0,
            'status' => 'draft',
        ];

        $numericFields = [
            'salary_correction', 'other_allowance',
            'cdt_deduction', 'alpha_deduction', 'cashbond_deduction',
            'piutang_obat_deduction', 'salary_correction_deduction',
            'bank_admin_deduction', 'pph21',
        ];

        foreach ($mapping as $field => $colIndex) {
            if ($colIndex === null || !isset($row[$colIndex - 1])) {
                continue;
            }

            $value = $row[$colIndex - 1];

            if (in_array($field, $numericFields)) {
                $data[$field] = $this->parseNumeric($value);
            } elseif ($field === 'employee_id') {
                $employee = User::where('nip', $value)
                    ->orWhere('employee_id', $value)
                    ->first();
                if (!$employee) {
                    throw new \Exception("Karyawan dengan NIP '$value' tidak ditemukan");
                }
                $data['user_id'] = $employee->id;
            }
        }

        if (!$data['user_id']) {
            throw new \Exception('Karyawan ID tidak valid atau tidak ditemukan');
        }

        return $data;
    }

    private function getMonthName($month)
    {
        $months = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];
        return $months[$month] ?? '';
    }

    public function downloadTemplate()
    {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('DATA PENGGAJIAN');

        // ── Styles ──────────────────────────────────────────────────────────
        $darkTitle = [
            'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '1F3864']],
            'alignment' => ['horizontal' => 'center', 'vertical' => 'center'],
        ];
        $sectionStyle = [
            'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['fillType' => 'solid', 'startColor' => ['rgb' => '2F5496']],
            'alignment' => ['horizontal' => 'center', 'vertical' => 'center'],
        ];
        $blueHeader = [
            'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['fillType' => 'solid', 'startColor' => ['rgb' => '4472C4']],
            'alignment' => ['horizontal' => 'center', 'vertical' => 'center', 'wrapText' => true],
            'borders'   => ['allBorders' => ['borderStyle' => 'thin', 'color' => ['rgb' => 'FFFFFF']]],
        ];
        $greenHeader = [
            'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['fillType' => 'solid', 'startColor' => ['rgb' => '548235']],
            'alignment' => ['horizontal' => 'center', 'vertical' => 'center', 'wrapText' => true],
            'borders'   => ['allBorders' => ['borderStyle' => 'thin', 'color' => ['rgb' => 'FFFFFF']]],
        ];
        $redHeader = [
            'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['fillType' => 'solid', 'startColor' => ['rgb' => 'C00000']],
            'alignment' => ['horizontal' => 'center', 'vertical' => 'center', 'wrapText' => true],
            'borders'   => ['allBorders' => ['borderStyle' => 'thin', 'color' => ['rgb' => 'FFFFFF']]],
        ];

        // ── Row 1: Title ────────────────────────────────────────────────────
        $sheet->mergeCells('A1:K1');
        $sheet->setCellValue('A1', 'DATA PENGGAJIAN — Isi kolom HIJAU (Tambahan) & MERAH (Potongan). Gaji, tunjangan, lembur & BPJS otomatis dari sistem.');
        $sheet->getStyle('A1')->applyFromArray($darkTitle);
        $sheet->getRowDimension(1)->setRowHeight(25);

        // ── Row 2: Section Labels ───────────────────────────────────────────
        $sheet->mergeCells('A2:B2');
        $sheet->setCellValue('A2', 'IDENTITAS (OTOMATIS)');
        $sheet->mergeCells('C2:D2');
        $sheet->setCellValue('C2', 'TAMBAHAN (+)');
        $sheet->mergeCells('E2:K2');
        $sheet->setCellValue('E2', 'POTONGAN ADMIN (-)');
        foreach (['A2', 'C2', 'E2'] as $c) {
            $sheet->getStyle($c)->applyFromArray($sectionStyle);
        }
        $sheet->getRowDimension(2)->setRowHeight(20);

        // ── Row 3: Column Headers ───────────────────────────────────────────
        $headers = [
            'A'  => ['NIP',               'blue'],
            'B'  => ['NAMA',              'blue'],
            'C'  => ['KOREKSI UPAH (+)',  'green'],
            'D'  => ['LAIN-LAIN (+)',     'green'],
            'E'  => ['CDT',              'red'],
            'F'  => ['ALPA',             'red'],
            'G'  => ['CASHBOND',         'red'],
            'H'  => ['PIUTANG OBAT',     'red'],
            'I'  => ['KOREKSI UPAH (-)', 'red'],
            'J'  => ['ADM. BANK',        'red'],
            'K'  => ['PPH 21',           'red'],
        ];

        foreach ($headers as $col => [$label, $color]) {
            $sheet->setCellValue("{$col}3", $label);
            $style = $color === 'blue' ? $blueHeader : ($color === 'green' ? $greenHeader : $redHeader);
            $sheet->getStyle("{$col}3")->applyFromArray($style);
        }
        $sheet->getRowDimension(3)->setRowHeight(38);

        // ── Pre-fill employees ──────────────────────────────────────────────
        $employees = User::where('role', 'karyawan')
            ->where('status', '!=', 'keluar')
            ->orderBy('department')
            ->orderBy('name')
            ->get();

        $numFmt = '#,##0';
        $row = 4;
        foreach ($employees as $emp) {
            $sheet->setCellValueExplicit("A{$row}", $emp->nip ?? $emp->employee_id, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValue("B{$row}", $emp->name);

            // Identity columns (read-only style)
            $sheet->getStyle("A{$row}:B{$row}")->applyFromArray([
                'fill'    => ['fillType' => 'solid', 'startColor' => ['rgb' => 'D6DCE4']],
                'borders' => ['allBorders' => ['borderStyle' => 'thin', 'color' => ['rgb' => 'CCCCCC']]],
            ]);

            // Tambahan columns (green) C-D
            foreach (range('C', 'D') as $c) {
                $sheet->getStyle("{$c}{$row}")->applyFromArray([
                    'fill'    => ['fillType' => 'solid', 'startColor' => ['rgb' => 'E2EFDA']],
                    'borders' => ['allBorders' => ['borderStyle' => 'thin', 'color' => ['rgb' => 'CCCCCC']]],
                    'alignment' => ['horizontal' => 'right'],
                ]);
                $sheet->getStyle("{$c}{$row}")->getNumberFormat()->setFormatCode($numFmt);
            }
            // Potongan columns (red) E-K
            foreach (range('E', 'K') as $c) {
                $sheet->getStyle("{$c}{$row}")->applyFromArray([
                    'fill'    => ['fillType' => 'solid', 'startColor' => ['rgb' => 'FCE4EC']],
                    'borders' => ['allBorders' => ['borderStyle' => 'thin', 'color' => ['rgb' => 'CCCCCC']]],
                    'alignment' => ['horizontal' => 'right'],
                ]);
                $sheet->getStyle("{$c}{$row}")->getNumberFormat()->setFormatCode($numFmt);
            }
            $row++;
        }

        // ── Column Widths ───────────────────────────────────────────────────
        $widths = ['A' => 16, 'B' => 30, 'C' => 17, 'D' => 15, 'E' => 12, 'F' => 12, 'G' => 14, 'H' => 15, 'I' => 17, 'J' => 14, 'K' => 13];
        foreach ($widths as $col => $width) {
            $sheet->getColumnDimension($col)->setWidth($width);
        }

        $sheet->freezePane('C4');

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $tempFile = tempnam(sys_get_temp_dir(), 'data_penggajian_');
        $writer->save($tempFile);

        return response()->download($tempFile, 'DATA PENGGAJIAN.xlsx')->deleteFileAfterSend(true);
    }
}
