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
                $sheetData = $worksheet->toArray(null, true, true, true);

                // Detect template format and skip all header rows
                $headers = [];
                $dataStartRow = 0;
                
                // Look for first row that has actual employee data (NIP column)
                foreach ($sheetData as $rowIdx => $row) {
                    $firstCell = trim((string)($row[0] ?? ''));
                    
                    // Skip rows that are part of template legend
                    if (str_contains($firstCell, 'DATA PENGGAJIAN') ||
                        str_contains($firstCell, 'Kolom BIRU') ||
                        str_contains($firstCell, 'Kolom MERAH') ||
                        str_contains($firstCell, 'NIP') && str_contains($firstCell, 'NAMA') ||
                        str_starts_with($firstCell, '*') ||
                        str_starts_with($firstCell, '=') ||
                        str_starts_with($firstCell, '|') ||
                        empty($firstCell)) {
                        
                        // If this looks like a header row (has column names)
                        if (str_contains($firstCell, 'NIP') || str_contains($firstCell, 'NAMA') || 
                            str_contains($firstCell, 'GAJI') || str_contains($firstCell, 'nip') || 
                            str_contains($firstCell, 'nama') || str_contains($firstCell, 'gaji')) {
                            $headers = $row;
                            $dataStartRow = $rowIdx + 1;
                        }
                        continue;
                    }
                    
                    // Found first data row (NIP should be numeric or code)
                    if (!empty($firstCell)) {
                        if (empty($headers)) {
                            // No header found yet, use previous row as headers
                            if ($rowIdx > 0) {
                                $headers = $sheetData[$rowIdx - 1];
                            }
                        }
                        $dataStartRow = $rowIdx;
                        break;
                    }
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
                $sheetData = $worksheet->toArray(null, true, true, true);

                // Detect template format and skip all header rows
                $dataStartRow = 0;
                
                // Look for first row that has actual employee data
                foreach ($sheetData as $rowIdx => $row) {
                    $firstCell = trim((string)($row[0] ?? ''));
                    
                    // Skip rows that are part of template legend
                    if (str_contains($firstCell, 'DATA PENGGAJIAN') ||
                        str_contains($firstCell, 'Kolom BIRU') ||
                        str_contains($firstCell, 'Kolom MERAH') ||
                        str_contains($firstCell, 'NIP') && str_contains($firstCell, 'NAMA') ||
                        str_starts_with($firstCell, '*') ||
                        str_starts_with($firstCell, '=') ||
                        str_starts_with($firstCell, '|') ||
                        empty($firstCell)) {
                        
                        // If this looks like a header row, skip it
                        if (str_contains($firstCell, 'NIP') || str_contains($firstCell, 'NAMA') || 
                            str_contains($firstCell, 'GAJI') || str_contains($firstCell, 'nip') || 
                            str_contains($firstCell, 'nama') || str_contains($firstCell, 'gaji')) {
                            $dataStartRow = $rowIdx + 1;
                        }
                        continue;
                    }
                    
                    // Found first data row
                    if (!empty($firstCell)) {
                        $dataStartRow = $rowIdx;
                        break;
                    }
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
        $data = [
            'user_id' => null,
            'month' => $month,
            'year' => $year,
            'total_work_days' => 0,
            'present_days' => 0,
            'absent_days' => 0,
            'late_days' => 0,
            'leave_days' => 0,
            'sick_days' => 0,
            'overtime_hours' => 0,
            
            // Tunjangan
            'base_salary' => 0,
            'position_allowance' => 0,
            'functional_allowance' => 0,
            'special_allowance' => 0,
            'meal_allowance' => 0,
            'transport_allowance' => 0,
            'attendance_allowance' => 0,
            'other_allowance' => 0,
            'salary_correction' => 0,
            'total_allowance' => 0,
            
            // Bruto
            'gross_salary' => 0,
            
            // Lembur
            'overtime_hourly' => 0,
            'overtime_shift' => 0,
            'overtime_night' => 0, // Lembur Malam
            'overtime_on_call' => 0,
            'overtime_mod' => 0,
            'overtime_holiday' => 0,
            'overtime_pay' => 0,
            'total_overtime_other' => 0,
            
            // Potongan
            'bpjs_kesehatan' => 0,
            'cdt_deduction' => 0,
            'alpha_deduction' => 0,
            'cashbond_deduction' => 0,
            'piutang_obat_deduction' => 0,
            'bpjs_ketenagakerjaan' => 0,
            'bpjs_pensiun' => 0,
            'bpjs_pensiun_jp' => 0,
            'pph21' => 0,
            'salary_correction_deduction' => 0,
            'bank_admin_deduction' => 0,
            'absence_deduction' => 0,
            'other_deduction' => 0,
            'total_deduction' => 0,
            
            // Net
            'net_salary' => 0,
            'status' => 'draft',
        ];

        // Map each field from CSV
        foreach ($mapping as $field => $colIndex) {
            if ($colIndex === null || !isset($row[$colIndex - 1])) {
                continue;
            }

            $value = $row[$colIndex - 1];

            // Handle numeric fields - use improved parsing
            if (in_array($field, [
                'base_salary', 'position_allowance', 'functional_allowance', 'special_allowance',
                'meal_allowance', 'transport_allowance', 'attendance_allowance', 'other_allowance',
                'salary_correction', 'total_allowance', 'gross_salary',
                'overtime_hourly', 'overtime_shift', 'overtime_night', 'overtime_on_call', 'overtime_mod', 'overtime_holiday',
                'overtime_pay', 'total_overtime_other',
                'bpjs_kesehatan', 'cdt_deduction', 'alpha_deduction', 'cashbond_deduction',
                'piutang_obat_deduction', 'bpjs_ketenagakerjaan', 'bpjs_pensiun', 'bpjs_pensiun_jp',
                'pph21', 'salary_correction_deduction', 'bank_admin_deduction', 'other_deduction',
                'total_deduction', 'net_salary'
            ])) {
                // Use improved number parsing
                $data[$field] = $this->parseNumeric($value);
            }
            // Special handling for employee_id/nip
            elseif ($field === 'employee_id') {
                // Search by nip first, then employee_id
                $employee = User::where('nip', $value)
                    ->orWhere('employee_id', $value)
                    ->first();
                if (!$employee) {
                    throw new \Exception("Karyawan dengan NIP '$value' tidak ditemukan");
                }
                $data['user_id'] = $employee->id;
            }
        }

        // Validate required fields
        if (!$data['user_id']) {
            throw new \Exception('Karyawan ID tidak valid atau tidak ditemukan');
        }

        // Auto-calculate BRUTO = base_salary + semua tunjangan (tanpa salary_correction & other_allowance)
        if ($data['gross_salary'] == 0 && $data['base_salary'] > 0) {
            $data['gross_salary'] = $data['base_salary'] + $data['position_allowance'] +
                                    $data['functional_allowance'] + $data['special_allowance'] +
                                    $data['meal_allowance'] + $data['transport_allowance'] +
                                    $data['attendance_allowance'];
        }

        // Total Lembur (semua kategori termasuk Malam)
        if ($data['total_overtime_other'] == 0) {
            $data['total_overtime_other'] = $data['overtime_hourly'] + $data['overtime_night'] +
                                            $data['overtime_shift'] + $data['overtime_on_call'] +
                                            $data['overtime_mod'] + $data['overtime_holiday'];
        }

        // TOTAL PENDAPATAN = BRUTO + Lembur + Koreksi Upah (+) + Lain-lain
        $totalPendapatan = $data['gross_salary'] + $data['total_overtime_other'] +
                           $data['salary_correction'] + $data['other_allowance'];

        // Auto-calculate BPJS dari base_salary jika tidak diinput
        if ($data['bpjs_kesehatan'] == 0 && $data['base_salary'] > 0) {
            $data['bpjs_kesehatan'] = round($data['base_salary'] * 0.01);
        }
        if ($data['bpjs_ketenagakerjaan'] == 0 && $data['base_salary'] > 0) {
            $data['bpjs_ketenagakerjaan'] = round($data['base_salary'] * 0.02);
        }
        if ($data['bpjs_pensiun_jp'] == 0 && $data['base_salary'] > 0) {
            $data['bpjs_pensiun_jp'] = round($data['base_salary'] * 0.01);
        }

        // Total Potongan = Potongan Admin + BPJS + PPh21
        if ($data['total_deduction'] == 0) {
            $data['total_deduction'] = $data['bpjs_kesehatan'] + $data['bpjs_ketenagakerjaan'] +
                                       $data['bpjs_pensiun'] + $data['bpjs_pensiun_jp'] +
                                       $data['pph21'] + $data['cdt_deduction'] +
                                       $data['alpha_deduction'] + $data['cashbond_deduction'] +
                                       $data['piutang_obat_deduction'] + $data['salary_correction_deduction'] +
                                       $data['bank_admin_deduction'] + $data['other_deduction'];
        }

        // GAJI DIBAYARKAN = TOTAL PENDAPATAN - TOTAL POTONGAN
        if ($data['net_salary'] == 0) {
            $data['net_salary'] = $totalPendapatan - $data['total_deduction'];
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
        $blueHeader = [   // Admin input
            'font'      => ['bold' => true, 'color' => ['rgb' => '000000']],
            'fill'      => ['fillType' => 'solid', 'startColor' => ['rgb' => '4472C4']],
            'alignment' => ['horizontal' => 'center', 'vertical' => 'center', 'wrapText' => true],
            'borders'   => ['allBorders' => ['borderStyle' => 'thin', 'color' => ['rgb' => 'FFFFFF']]],
        ];
        $redHeader = [    // Auto-calculated / sync ke slip gaji
            'font'      => ['bold' => true, 'color' => ['rgb' => '000000']],
            'fill'      => ['fillType' => 'solid', 'startColor' => ['rgb' => 'FF0000']],
            'alignment' => ['horizontal' => 'center', 'vertical' => 'center', 'wrapText' => true],
            'borders'   => ['allBorders' => ['borderStyle' => 'thin', 'color' => ['rgb' => 'FFFFFF']]],
        ];
        $blueCell = [
            'fill'    => ['fillType' => 'solid', 'startColor' => ['rgb' => 'BDD7EE']],
            'borders' => ['allBorders' => ['borderStyle' => 'thin', 'color' => ['rgb' => 'CCCCCC']]],
            'alignment' => ['horizontal' => 'right'],
        ];
        $redCell = [
            'fill'    => ['fillType' => 'solid', 'startColor' => ['rgb' => 'FFB3B3']],
            'borders' => ['allBorders' => ['borderStyle' => 'thin', 'color' => ['rgb' => 'CCCCCC']]],
            'alignment' => ['horizontal' => 'right'],
        ];

        // ── Row 1: LEGEND ───────────────────────────────────────────────────
        $sheet->mergeCells('A1:AF1');
        $sheet->setCellValue('A1', 'DATA PENGGAJIAN  |  Kolom BIRU = Input Admin  |  Kolom MERAH = Kalkulasi Otomatis (sync ke slip gaji)');
        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '1F3864']],
            'alignment' => ['horizontal' => 'center', 'vertical' => 'center'],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(22);

        // ── Row 2: Section Labels ────────────────────────────────────────────
        $sectionStyle = [
            'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['fillType' => 'solid', 'startColor' => ['rgb' => '2F5496']],
            'alignment' => ['horizontal' => 'center', 'vertical' => 'center'],
        ];
        $sheet->mergeCells('A2:D2');  $sheet->setCellValue('A2', 'IDENTITAS');
        $sheet->mergeCells('E2:L2');  $sheet->setCellValue('E2', 'PENDAPATAN (GAJI & TUNJANGAN)');
        $sheet->mergeCells('M2:T2');  $sheet->setCellValue('M2', 'LEMBUR & TAMBAHAN');
        $sheet->mergeCells('U2:AF2'); $sheet->setCellValue('U2', 'POTONGAN');
        foreach (['A2','E2','M2','U2'] as $c) {
            $sheet->getStyle($c)->applyFromArray($sectionStyle);
        }
        $sheet->getRowDimension(2)->setRowHeight(18);

        // ── Row 3: Column Headers ────────────────────────────────────────────
        // A-D: Identitas, E-K: Gaji & Tunjangan, L: BRUTO
        // M-Q: Lembur, R-S: Tambahan, T: TOTAL PENDAPATAN
        // U-AA: Potongan Admin, AB-AD: BPJS, AE: TOTAL POTONGAN, AF: GAJI DIBAYARKAN
        $headers = [
            'A'  => ['NIP',                              'blue'],
            'B'  => ['NAMA',                             'blue'],
            'C'  => ['JABATAN',                          'blue'],
            'D'  => ['UNIT',                             'blue'],
            'E'  => ['GAJI POKOK',                       'blue'],
            'F'  => ['TUNJ. JABATAN',                    'blue'],
            'G'  => ['TUNJ. FUNGSIONAL',                 'blue'],
            'H'  => ['TUNJ. KHUSUS',                     'blue'],
            'I'  => ['TUNJ. MAKAN',                      'blue'],
            'J'  => ['TUNJ. TRANSPORT',                  'blue'],
            'K'  => ['TUNJ. KEHADIRAN',                  'blue'],
            'L'  => ['BRUTO',                            'red'],
            'M'  => ['LEMBUR JAM (@10.000/jam)',         'blue'],
            'N'  => ['LEMBUR MALAM (@20.000/shift)',     'blue'],
            'O'  => ['LEMBUR SHIFT (@80.000/shift)',     'blue'],
            'P'  => ['LEMBUR ON CALL (@50.000/shift)',   'blue'],
            'Q'  => ['LEMBUR HARI RAYA (@120.000/shift)','blue'],
            'R'  => ['KOREKSI UPAH (+)',                 'blue'],
            'S'  => ['LAIN-LAIN (+)',                    'blue'],
            'T'  => ['TOTAL PENDAPATAN',                 'red'],
            'U'  => ['CDT',                              'blue'],
            'V'  => ['ALPA',                             'blue'],
            'W'  => ['CASHBOND',                         'blue'],
            'X'  => ['PIUTANG OBAT',                     'blue'],
            'Y'  => ['KOREKSI UPAH (-)',                 'blue'],
            'Z'  => ['ADM. BANK',                        'blue'],
            'AA' => ['PPH 21',                           'blue'],
            'AB' => ['BPJS KESEHATAN (1%)',              'red'],
            'AC' => ['BPJS TK JHT (2%)',                 'red'],
            'AD' => ['BPJS TK JP (1%)',                  'red'],
            'AE' => ['TOTAL POTONGAN',                   'red'],
            'AF' => ['GAJI DIBAYARKAN',                  'red'],
        ];

        foreach ($headers as $col => [$label, $color]) {
            $cell = $col . '3';
            $sheet->setCellValue($cell, $label);
            $sheet->getStyle($cell)->applyFromArray($color === 'blue' ? $blueHeader : $redHeader);
        }
        $sheet->getRowDimension(3)->setRowHeight(42);

        // ── Row 4-103: Pre-fill formulas + styling for 100 rows ──────────
        $blueCellLeft = array_merge($blueCell, ['alignment' => ['horizontal' => 'left']]);
        $blueTextCols = ['A','B','C','D'];
        $blueNumCols  = ['E','F','G','H','I','J','K','M','N','O','P','Q','R','S','U','V','W','X','Y','Z','AA'];
        $redFormulaCols = ['L','T','AB','AC','AD','AE','AF'];
        $numFmt = '#,##0';

        for ($r = 4; $r <= 103; $r++) {
            // Blue text columns (identitas) - left aligned
            foreach ($blueTextCols as $col) {
                $sheet->getStyle("{$col}{$r}")->applyFromArray($blueCellLeft);
            }

            // Blue numeric columns - right aligned + number format
            foreach ($blueNumCols as $col) {
                $sheet->getStyle("{$col}{$r}")->applyFromArray($blueCell);
                $sheet->getStyle("{$col}{$r}")->getNumberFormat()->setFormatCode($numFmt);
            }

            // Red formula columns
            $sheet->setCellValue("L{$r}", "=E{$r}+F{$r}+G{$r}+H{$r}+I{$r}+J{$r}+K{$r}");
            $sheet->setCellValue("T{$r}", "=L{$r}+M{$r}+N{$r}+O{$r}+P{$r}+Q{$r}+R{$r}+S{$r}");
            $sheet->setCellValue("AB{$r}", "=ROUND(E{$r}*0.01,0)");
            $sheet->setCellValue("AC{$r}", "=ROUND(E{$r}*0.02,0)");
            $sheet->setCellValue("AD{$r}", "=ROUND(E{$r}*0.01,0)");
            $sheet->setCellValue("AE{$r}", "=U{$r}+V{$r}+W{$r}+X{$r}+Y{$r}+Z{$r}+AA{$r}+AB{$r}+AC{$r}+AD{$r}");
            $sheet->setCellValue("AF{$r}", "=T{$r}-AE{$r}");

            foreach ($redFormulaCols as $col) {
                $sheet->getStyle("{$col}{$r}")->applyFromArray($redCell);
                $sheet->getStyle("{$col}{$r}")->getNumberFormat()->setFormatCode($numFmt);
            }
        }

        // ── Column Widths ────────────────────────────────────────────────────
        $widths = [
            'A'=>14, 'B'=>22, 'C'=>18, 'D'=>16,
            'E'=>14, 'F'=>14, 'G'=>14, 'H'=>14, 'I'=>13, 'J'=>13, 'K'=>13,
            'L'=>14, 'M'=>16, 'N'=>16, 'O'=>16, 'P'=>16, 'Q'=>18,
            'R'=>14, 'S'=>13, 'T'=>16,
            'U'=>12, 'V'=>12, 'W'=>12, 'X'=>13, 'Y'=>14, 'Z'=>12, 'AA'=>12,
            'AB'=>16, 'AC'=>14, 'AD'=>14, 'AE'=>15, 'AF'=>16,
        ];
        foreach ($widths as $col => $width) {
            $sheet->getColumnDimension($col)->setWidth($width);
        }

        // ── Freeze pane: fix identitas + header rows ────────────────────────
        $sheet->freezePane('E4');

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $tempFile = tempnam(sys_get_temp_dir(), 'data_penggajian_');
        $writer->save($tempFile);

        return response()->download($tempFile, 'DATA PENGGAJIAN.xlsx')->deleteFileAfterSend(true);
    }
}
