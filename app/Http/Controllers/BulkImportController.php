<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class BulkImportController extends Controller
{
    public function index()
    {
        return Inertia::render('Employee/BulkImport');
    }

    public function preview(Request $request)
    {
        $request->validate([
            'file' => 'required|file',
        ]);

        // Validate extension manually (mimes can be unreliable)
        $allowedExtensions = ['csv', 'xlsx', 'xls'];
        $extension = strtolower($request->file('file')->getClientOriginalExtension());
        if (!in_array($extension, $allowedExtensions)) {
            return response()->json([
                'success' => false,
                'message' => 'Format file tidak didukung. Gunakan .xlsx, .xls, atau .csv',
            ], 422);
        }

        try {
            $file = $request->file('file');
            $rows = [];
            $headers = [];

            // Handle CSV files with PHP built-in function
            if (in_array($file->getClientOriginalExtension(), ['csv'])) {
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
                // For XLSX/XLS, try to use PhpSpreadsheet if available
                try {
                    $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file->getPathname());
                    $worksheet = $spreadsheet->getActiveSheet();
                    $sheetData = $worksheet->toArray();
                    // Hapus baris kosong di awal jika ada
                    while (!empty($sheetData) && empty(array_filter($sheetData[0]))) {
                        array_shift($sheetData);
                    }
                    if (empty($sheetData)) {
                        throw new \Exception('Sheet kosong atau tidak ada data.');
                    }
                    $headers = array_shift($sheetData);
                    $rows = array_slice($sheetData, 0, 5);
                    $totalRows = count($sheetData);
                } catch (\Exception $e) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Error membaca file Excel: ' . $e->getMessage(),
                    ], 422);
                }
            }

            return response()->json([
                'success' => true,
                'headers' => $headers,
                'preview' => $rows,
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
        // Allow more time for large imports (600-800+ rows)
        set_time_limit(300);

        $request->validate([
            'file' => 'required|file',
            'mapping' => 'required',
        ]);

        // Validate extension manually
        $allowedExtensions = ['csv', 'xlsx', 'xls'];
        $extension = strtolower($request->file('file')->getClientOriginalExtension());
        if (!in_array($extension, $allowedExtensions)) {
            return response()->json([
                'success' => false,
                'message' => 'Format file tidak didukung.',
            ], 422);
        }

        try {
            $file = $request->file('file');
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
                // Handle XLSX/XLS
                $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file->getPathname());
                $worksheet = $spreadsheet->getActiveSheet();
                $sheetData = $worksheet->toArray();
                // Skip empty rows at top
                while (!empty($sheetData) && empty(array_filter($sheetData[0]))) {
                    array_shift($sheetData);
                }
                array_shift($sheetData); // Skip header
                $rows = $sheetData;
                // Free memory
                $spreadsheet->disconnectWorksheets();
                unset($spreadsheet, $worksheet, $sheetData);
            }

            // Decode mapping if sent as JSON string
            $mapping = $request->get('mapping');
            if (is_string($mapping)) {
                $mapping = json_decode($mapping, true);
            }
            $imported = 0;
            $errors = [];

            // Pre-fetch existing NIPs for faster duplicate check
            $existingNips = User::pluck('nip')->filter()->toArray();
            $existingNips = array_flip($existingNips);

            foreach ($rows as $rowIndex => $row) {
                if (empty(array_filter($row))) continue;

                try {
                    $data = $this->buildEmployeeData($row, $mapping, $existingNips);
                    // Password = NIP
                    $data['password'] = Hash::make($data['nip']);
                    $data['role'] = 'karyawan';

                    User::create($data);
                    // Track newly imported NIP
                    $existingNips[$data['nip']] = true;
                    $imported++;
                } catch (\Exception $e) {
                    $errors[] = "Baris " . ($rowIndex + 2) . ": " . $e->getMessage();
                }
            }

            return response()->json([
                'success' => true,
                'message' => "Berhasil import $imported karyawan",
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

    private function buildEmployeeData($row, $mapping, $existingNips = [])
    {
        $data = [
            'nip' => '',
            'name' => '',
            'gender' => null,
            'education' => '',
            'birth_place' => '',
            'birth_date' => null,
            'department' => '',
            'position' => '',
            'phone' => '',
            'address' => '',
            'city' => '',
            'join_date' => date('Y-m-d'),
            'npwp' => '',
            'bpjs_kesehatan' => '',
            'bpjs_ketenagakerjaan' => '',
            'bank_name' => '',
            'bank_account' => '',
            'base_salary' => 0,
            'position_allowance' => 0,
            'functional_allowance' => 0,
            'special_allowance' => 0,
            'meal_allowance' => 0,
            'transport_allowance' => 0,
            'attendance_allowance' => 0,
            'status' => 'active',
        ];

        foreach ($mapping as $field => $colIndex) {
            if ($colIndex === null || !isset($row[$colIndex - 1])) {
                continue;
            }

            $value = $row[$colIndex - 1];

            // Handle gender mapping
            if ($field === 'gender' && $value) {
                $genderLower = strtolower(trim($value));
                if (in_array($genderLower, ['l', 'laki-laki', 'laki', 'pria', 'male'])) {
                    $value = 'L';
                } elseif (in_array($genderLower, ['p', 'perempuan', 'wanita', 'female'])) {
                    $value = 'P';
                }
            }

            // Handle status mapping
            if ($field === 'status' && $value) {
                $statusLower = strtolower(trim($value));
                // Remove extra spaces
                $statusLower = preg_replace('/\s+/', ' ', $statusLower);
                if (in_array($statusLower, ['aktif', 'active', 'ya', 'y'])) {
                    $value = 'active';
                } elseif (in_array($statusLower, ['keluar', 'inactive', 'tidak', 'n', 'nonaktif', 'non aktif', 'non-aktif', 'resign'])) {
                    $value = 'inactive';
                } else {
                    $value = 'active'; // default
                }
            }

            // Handle salary/allowance fields - convert to numeric
            if (in_array($field, ['base_salary', 'position_allowance', 'functional_allowance', 'special_allowance', 'meal_allowance', 'transport_allowance', 'attendance_allowance']) && $value) {
                // Remove non-numeric characters except decimal point
                $value = (float) preg_replace('/[^\d.]/', '', $value);
                $value = max(0, $value); // Ensure non-negative
            }

            // Handle date fields - parse various formats
            if (in_array($field, ['birth_date', 'join_date']) && $value) {
                $value = $this->parseDate($value);
            }

            $data[$field] = $value;
        }

        // Validasi required fields
        if (!$data['nip']) throw new \Exception('NIP tidak boleh kosong');
        if (!$data['name']) throw new \Exception('Nama tidak boleh kosong');

        // Check duplicate NIP using cached array (faster than DB query per row)
        $nipStr = (string) $data['nip'];
        if (isset($existingNips[$nipStr])) {
            throw new \Exception("NIP '{$data['nip']}' sudah terdaftar");
        }

        return $data;
    }

    /**
     * Parse various date formats to Y-m-d
     */
    private function parseDate($value): ?string
    {
        if (empty($value)) return null;

        $value = trim($value);

        // Skip invalid dates
        if (in_array($value, ['0000-00-00', '0', '-', '00/00/0000'])) {
            return null;
        }

        // Already Y-m-d format
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            if ($value === '0000-00-00') return null;
            return $value;
        }

        // Excel serial number (numeric)
        if (is_numeric($value) && (int)$value > 1000) {
            try {
                $date = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((int)$value);
                $year = (int)$date->format('Y');
                if ($year < 1900 || $year > 2100) return null;
                return $date->format('Y-m-d');
            } catch (\Exception $e) {
                return null;
            }
        }

        // Try common formats: m/d/Y, d/m/Y, d-m-Y, etc.
        $formats = ['m/d/Y', 'd/m/Y', 'Y/m/d', 'd-m-Y', 'm-d-Y', 'd.m.Y'];
        foreach ($formats as $format) {
            $parsed = \DateTime::createFromFormat($format, $value);
            if ($parsed) {
                $year = (int)$parsed->format('Y');
                if ($year < 1900 || $year > 2100) return null;
                return $parsed->format('Y-m-d');
            }
        }

        // Last try: PHP strtotime
        $ts = strtotime($value);
        if ($ts && $ts > 0) {
            $year = (int)date('Y', $ts);
            if ($year < 1900 || $year > 2100) return null;
            return date('Y-m-d', $ts);
        }

        return null;
    }

    public function downloadTemplate()
    {
        // Langsung download file DATA KARYAWAN.xlsx yang sudah ada sebagai template
        $templatePath = base_path('DATA PEGAWAI/DATA KARYAWAN.xlsx');

        if (file_exists($templatePath)) {
            return response()->download($templatePath, 'Template_DATA_KARYAWAN.xlsx');
        }

        // Fallback: Generate template jika file tidak ada
        $headers = [
            'NO',
            'NIP',
            'NAMA',
            'JENIS_KELAMIN',
            'PENDIDIKAN',
            'TEMPAT_LAHIR',
            'TANGGAL_LAHIR',
            'ALAMAT',
            'KOTA',
            'NO_HP',
            'DEPARTEMEN',
            'JABATAN',
            'GAJI_POKOK',
            'TUNJANGAN_JABATAN',
            'TUNJANGAN_FUNGSIONAL',
            'TUNJANGAN_KHUSUS',
            'TUNJANGAN_MAKAN',
            'TUNJANGAN_TRANSPORTASI',
            'TUNJANGAN_KEHADIRAN',
            'TANGGAL_MASUK',
            'NPWP',
            'BPJS_KESEHATAN',
            'BPJS_KETENAGAKERJAAN',
            'NAMA_REKENING',
            'NOMOR_REKENING',
            'STATUS',
        ];

        $sampleData = [
            [1, '2021C171', 'dr. Jati Sarasanti', 'P', 'S1', 'Surabaya', '1990-05-15', 'Jl. Kesehatan No. 1', 'Surabaya', '08123456789', 'Dokter Umum', 'Dokter', 5000000, 1000000, 800000, 500000, 500000, 300000, 250000, '2021-01-15', '12.345.678.9-012.000', '0001234567890', '0001234567890', 'Jati Sarasanti', '1234567890', 'AKTIF'],
            [2, '2021C172', 'Sri Handayani', 'P', 'D3', 'Jakarta', '1992-08-20', 'Jl. Sehat No. 2', 'Jakarta', '08198765432', 'Keperawatan', 'Perawat', 3500000, 500000, 400000, 300000, 500000, 300000, 200000, '2021-02-01', '98.765.432.1-098.000', '0009876543210', '0009876543210', 'Sri Handayani', '9876543210', 'AKTIF'],
        ];

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Data Karyawan');
        $sheet->fromArray($headers, null, 'A1');
        $sheet->fromArray($sampleData[0], null, 'A2');
        $sheet->fromArray($sampleData[1], null, 'A3');

        foreach (range('A', 'W') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $tempFile = tempnam(sys_get_temp_dir(), 'employee_template');
        $writer->save($tempFile);

        return response()->download($tempFile, 'Template_DATA_KARYAWAN.xlsx')->deleteFileAfterSend(true);
    }

    /**
     * Show schedule import form
     */
    public function scheduleImport()
    {
        return Inertia::render('Schedule/Import');
    }

    /**
     * Schedule import preview
     * Format: NO | NIP | NAMA KARYAWAN | JABATAN/UNIT | Date1 | Date2 | ... | Date31
     */
    public function schedulePreview(Request $request)
    {
        $request->validate([
            'file' => 'required|file',
        ]);

        $allowedExtensions = ['csv', 'xlsx', 'xls'];
        $extension = strtolower($request->file('file')->getClientOriginalExtension());
        if (!in_array($extension, $allowedExtensions)) {
            return response()->json([
                'success' => false,
                'message' => 'Format file tidak didukung. Gunakan .xlsx, .xls, atau .csv',
            ], 422);
        }

        try {
            $file = $request->file('file');
            $rows = [];
            $headers = [];

            if (in_array($file->getClientOriginalExtension(), ['csv'])) {
                $handle = fopen($file->getPathname(), 'r');
                $headers = fgetcsv($handle);
                
                $rowCount = 0;
                while ($rowCount < 5 && ($row = fgetcsv($handle)) !== false) {
                    $rows[] = $row;
                    $rowCount++;
                }
                fclose($handle);
                
                $handle = fopen($file->getPathname(), 'r');
                fgetcsv($handle);
                $totalRows = 0;
                while (fgetcsv($handle) !== false) {
                    $totalRows++;
                }
                fclose($handle);
            } else {
                $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file->getPathname());
                $worksheet = $spreadsheet->getActiveSheet();
                $sheetData = $worksheet->toArray();
                while (!empty($sheetData) && empty(array_filter($sheetData[0]))) {
                    array_shift($sheetData);
                }
                if (empty($sheetData)) {
                    throw new \Exception('Sheet kosong atau tidak ada data.');
                }
                $headers = array_shift($sheetData);
                $rows = array_slice($sheetData, 0, 5);
                $totalRows = count($sheetData);
            }

            // Expected format: NO | NIP | NAMA KARYAWAN | JABATAN/UNIT | Date columns
            $expectedHeaders = ['NO', 'NIP', 'NAMA KARYAWAN', 'JABATAN/UNIT'];
            
            return response()->json([
                'success' => true,
                'headers' => $headers,
                'expected_format' => $expectedHeaders,
                'preview' => $rows,
                'total_rows' => $totalRows,
                'format_info' => 'Kolom 1: NO, Kolom 2: NIP, Kolom 3: NAMA KARYAWAN, Kolom 4: JABATAN/UNIT, Kolom 5+: Tgl 1-31 (isi dengan kode shift: Pagi 1, Middle 2, Malam 1, dll)'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error membaca file: ' . $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Handle schedule bulk import
     * Format: NO | NIP | NAMA KARYAWAN | JABATAN/UNIT | Date1 | Date2 | ... | Date31
     * Each row represents one employee with shifts for each day of the month
     */
    public function scheduleStore(Request $request)
    {
        if (!$request->user()->isAdmin()) {
            abort(403);
        }

        set_time_limit(300);

        $request->validate([
            'file' => 'required|file',
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2020|max:2100',
        ]);

        $allowedExtensions = ['csv', 'xlsx', 'xls'];
        $extension = strtolower($request->file('file')->getClientOriginalExtension());
        if (!in_array($extension, $allowedExtensions)) {
            return response()->json([
                'success' => false,
                'message' => 'Format file tidak didukung.',
            ], 422);
        }

        try {
            $file = $request->file('file');
            $month = $request->get('month');
            $year = $request->get('year');
            $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
            
            $rows = [];
            $headers = [];

            if ($file->getClientOriginalExtension() === 'csv') {
                $handle = fopen($file->getPathname(), 'r');
                $headers = fgetcsv($handle);
                while (($row = fgetcsv($handle)) !== false) {
                    if (!empty(array_filter($row))) {
                        $rows[] = $row;
                    }
                }
                fclose($handle);
            } else {
                $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file->getPathname());
                $worksheet = $spreadsheet->getActiveSheet();
                $sheetData = $worksheet->toArray();
                while (!empty($sheetData) && empty(array_filter($sheetData[0]))) {
                    array_shift($sheetData);
                }
                if (empty($sheetData)) {
                    throw new \Exception('Sheet kosong atau tidak ada data.');
                }
                $headers = array_shift($sheetData);
                $rows = $sheetData;
                $spreadsheet->disconnectWorksheets();
                unset($spreadsheet, $worksheet, $sheetData);
            }

            $imported = 0;
            $errors = [];
            
            // Build shift mapping: name => shift
            $shifts = \App\Models\Shift::all();
            $shiftsByName = $shifts->keyBy('name');
            
            $users = User::all()->keyBy('nip');

            // Process each row (each employee)
            foreach ($rows as $rowIndex => $row) {
                if (empty(array_filter($row))) continue;

                try {
                    // Get NIP from column 2 (index 1)
                    $nip = isset($row[1]) ? trim($row[1]) : null;
                    
                    if (!$nip) {
                        $errors[] = "Baris " . ($rowIndex + 2) . ": NIP tidak boleh kosong";
                        continue;
                    }

                    if (!isset($users[$nip])) {
                        $errors[] = "Baris " . ($rowIndex + 2) . ": NIP $nip tidak ditemukan dalam sistem";
                        continue;
                    }

                    $user = $users[$nip];
                    $rowImported = 0;

                    // Process each date column (from column 5 onwards, starting at index 4)
                    for ($day = 1; $day <= $daysInMonth; $day++) {
                        $columnIndex = 3 + $day; // 4 base columns (NO, NIP, Nama, Jabatan) + day number
                        
                        if (!isset($row[$columnIndex])) {
                            continue;
                        }

                        $shiftName = trim($row[$columnIndex]);
                        
                        // Skip empty cells or "-"
                        if (empty($shiftName) || $shiftName === '-') {
                            continue;
                        }

                        // Check if shift name exists
                        if (!isset($shiftsByName[$shiftName])) {
                            $errors[] = "Baris " . ($rowIndex + 2) . ", Tgl " . $day . ": Shift '$shiftName' tidak ditemukan";
                            continue;
                        }

                        $date = sprintf('%04d-%02d-%02d', $year, $month, $day);
                        $shift = $shiftsByName[$shiftName];
                        
                        \App\Models\UserSchedule::updateOrCreate(
                            ['user_id' => $user->id, 'date' => $date],
                            ['shift_id' => $shift->id, 'created_by' => $request->user()->id]
                        );
                        $rowImported++;
                    }

                    if ($rowImported > 0) {
                        $imported++;
                    }
                } catch (\Exception $e) {
                    $errors[] = "Baris " . ($rowIndex + 2) . ": " . $e->getMessage();
                }
            }

            return response()->json([
                'success' => true,
                'message' => "Berhasil import jadwal untuk $imported karyawan",
                'imported' => $imported,
                'errors' => $errors,
                'month' => $month,
                'year' => $year,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Download schedule template
     */
    public function downloadScheduleTemplate(Request $request)
    {
        // Get month/year from request, default to current month
        $month = $request->get('month', date('m'));
        $year = $request->get('year', date('Y'));
        $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Jadwal Karyawan');

        // Get all employees
        $employees = User::where('role', 'karyawan')
            ->where('status', 'active')
            ->orderBy('nip')
            ->get();

        // Get all shifts
        $shifts = \App\Models\Shift::all();

        // Header row: NO | NIP | NAMA KARYAWAN | JABATAN/UNIT | Dates 1-31
        $headers = ['NO', 'NIP', 'NAMA KARYAWAN', 'JABATAN/UNIT'];
        for ($day = 1; $day <= $daysInMonth; $day++) {
            $headers[] = $day;
        }
        $sheet->fromArray($headers, null, 'A1');

        // Make header bold
        $sheet->getStyle('A1:' . \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($headers)) . '1')
            ->getFont()->setBold(true);
        $sheet->getStyle('A1:' . \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($headers)) . '1')
            ->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        // Add employee rows with existing schedules
        $rowNum = 2;
        foreach ($employees as $idx => $employee) {
            $colNum = 1;

            // NO
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colNum++);
            $sheet->setCellValue($colLetter . $rowNum, $idx + 1);

            // NIP
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colNum++);
            $sheet->setCellValue($colLetter . $rowNum, $employee->nip);

            // NAMA KARYAWAN
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colNum++);
            $sheet->setCellValue($colLetter . $rowNum, $employee->name);

            // JABATAN/UNIT
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colNum++);
            $sheet->setCellValue($colLetter . $rowNum, $employee->position ?? '-');

            // Days (1-31)
            $schedules = \App\Models\UserSchedule::where('user_id', $employee->id)
                ->whereBetween('date', [
                    sprintf('%04d-%02d-01', $year, $month),
                    sprintf('%04d-%02d-%02d', $year, $month, $daysInMonth)
                ])
                ->get()
                ->keyBy(function ($schedule) {
                    return (int) date('d', strtotime($schedule->date));
                });

            for ($day = 1; $day <= $daysInMonth; $day++) {
                $cellValue = '-';
                if (isset($schedules[$day])) {
                    $shift = $schedules[$day]->shift;
                    $cellValue = $shift->name;
                }
                $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colNum++);
                $sheet->setCellValue($colLetter . $rowNum, $cellValue);
            }

            $rowNum++;
        }

        // Set column widths
        $sheet->getColumnDimension('A')->setWidth(5);
        $sheet->getColumnDimension('B')->setWidth(12);
        $sheet->getColumnDimension('C')->setWidth(25);
        $sheet->getColumnDimension('D')->setWidth(20);
        for ($day = 1; $day <= $daysInMonth; $day++) {
            $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(4 + $day);
            $sheet->getColumnDimension($col)->setWidth(12);
        }

        // Add instructions
        $instructionsSheet = $spreadsheet->createSheet();
        $instructionsSheet->setTitle('Petunjuk');
        $instructionsSheet->setCellValue('A1', 'Petunjuk Penggunaan Template Jadwal Karyawan');
        $instructionsSheet->getStyle('A1')->getFont()->setBold(true)->setSize(12);

        $instructions = [
            '',
            'Format:',
            '1. Kolom A-D: Data karyawan (NO, NIP, NAMA, JABATAN) - Jangan diedit',
            '2. Kolom E-AE: Tanggal 1-31 - Isi dengan nama shift atau "-" (tidak ada shift)',
            '',
            'Nama Shift yang Tersedia:',
        ];
        $row = 1;
        foreach ($instructions as $line) {
            $instructionsSheet->setCellValue('A' . $row++, $line);
        }

        // Add shift reference - now showing full names
        foreach ($shifts as $shift) {
            $instructionsSheet->setCellValue('A' . $row, $shift->name);
            $row++;
        }

        $instructions2 = [
            '',
            'Contoh:',
            'Jika karyawan kerja Pagi 1 pada tgl 1: isi "Pagi 1"',
            'Jika karyawan libur pada tgl 2: isi "Libur"',
            'Jika karyawan tidak ada shift: isi "-"',
            '',
            'Catatan:',
            '- Jangan hapus kolom A-D (Data karyawan)',
            '- Jangan menambah/mengurangi karyawan',  
            '- Hanya edit kolom tanggal (E-AE)',
            '- Nama shift harus tepat sesuai daftar yang tersedia',
        ];
        foreach ($instructions2 as $line) {
            $instructionsSheet->setCellValue('A' . $row++, $line);
        }

        $instructionsSheet->getColumnDimension('A')->setWidth(60);

        // Add daftar shift reference sheet
        $shiftSheet = $spreadsheet->createSheet();
        $shiftSheet->setTitle('Daftar Shift');
        $shiftHeaders = ['Nama Shift', 'Jam Kerja', 'Deskripsi'];
        $shiftSheet->fromArray($shiftHeaders, null, 'A1');
        $shiftSheet->getStyle('A1:C1')->getFont()->setBold(true);

        $shiftRow = 2;
        foreach ($shifts as $shift) {
            $startTime = date('H:i', strtotime($shift->start_time));
            $endTime = date('H:i', strtotime($shift->end_time));
            $shiftSheet->setCellValue('A' . $shiftRow, $shift->name);
            $shiftSheet->setCellValue('B' . $shiftRow, "$startTime - $endTime");
            $shiftSheet->setCellValue('C' . $shiftRow, $shift->description ?? '-');
            $shiftRow++;
        }

        $shiftSheet->getColumnDimension('A')->setWidth(15);
        $shiftSheet->getColumnDimension('B')->setWidth(15);
        $shiftSheet->getColumnDimension('C')->setWidth(30);

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $filename = sprintf('Template_JADWAL_KARYAWAN_%04d_%02d.xlsx', $year, $month);
        $tempFile = tempnam(sys_get_temp_dir(), 'schedule_template');
        $writer->save($tempFile);

        return response()->download($tempFile, $filename)->deleteFileAfterSend(true);
    }
}
