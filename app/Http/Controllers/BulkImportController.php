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
            'TANGGAL_MASUK',
            'NPWP',
            'BPJS_KESEHATAN',
            'BPJS_KETENAGAKERJAAN',
            'NAMA_REKENING',
            'NOMOR_REKENING',
            'STATUS',
        ];

        $sampleData = [
            [1, '2021C171', 'dr. Jati Sarasanti', 'P', 'S1', 'Surabaya', '1990-05-15', 'Jl. Kesehatan No. 1', 'Surabaya', '08123456789', 'Dokter Umum', 'Dokter', '2021-01-15', '12.345.678.9-012.000', '0001234567890', '0001234567890', 'Jati Sarasanti', '1234567890', 'AKTIF'],
            [2, '2021C172', 'Sri Handayani', 'P', 'D3', 'Jakarta', '1992-08-20', 'Jl. Sehat No. 2', 'Jakarta', '08198765432', 'Keperawatan', 'Perawat', '2021-02-01', '98.765.432.1-098.000', '0009876543210', '0009876543210', 'Sri Handayani', '9876543210', 'AKTIF'],
        ];

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Data Karyawan');
        $sheet->fromArray($headers, null, 'A1');
        $sheet->fromArray($sampleData[0], null, 'A2');
        $sheet->fromArray($sampleData[1], null, 'A3');

        foreach (range('A', 'S') as $col) {
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

    /**
     * Handle schedule bulk import
     */
    public function scheduleStore(Request $request)
    {
        if (!$request->user()->isAdmin()) {
            abort(403);
        }

        set_time_limit(300);

        $request->validate([
            'file' => 'required|file',
            'mapping' => 'required',
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
            $rows = [];

            if ($file->getClientOriginalExtension() === 'csv') {
                $handle = fopen($file->getPathname(), 'r');
                fgetcsv($handle);
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
                array_shift($sheetData);
                $rows = $sheetData;
                $spreadsheet->disconnectWorksheets();
                unset($spreadsheet, $worksheet, $sheetData);
            }

            $mapping = $request->get('mapping');
            if (is_string($mapping)) {
                $mapping = json_decode($mapping, true);
            }

            $imported = 0;
            $errors = [];
            $shifts = \App\Models\Shift::all()->keyBy('name');
            $users = User::all()->keyBy('nip');

            foreach ($rows as $rowIndex => $row) {
                if (empty(array_filter($row))) continue;

                try {
                    $nip = isset($mapping['nip']) && isset($row[$mapping['nip'] - 1]) ? trim($row[$mapping['nip'] - 1]) : null;
                    $date = isset($mapping['date']) && isset($row[$mapping['date'] - 1]) ? $this->parseDate($row[$mapping['date'] - 1]) : null;
                    $shiftName = isset($mapping['shift']) && isset($row[$mapping['shift'] - 1]) ? trim($row[$mapping['shift'] - 1]) : null;

                    if (!$nip) {
                        $errors[] = "Baris " . ($rowIndex + 2) . ": NIP tidak boleh kosong";
                        continue;
                    }
                    if (!$date) {
                        $errors[] = "Baris " . ($rowIndex + 2) . ": Tanggal tidak boleh kosong";
                        continue;
                    }
                    if (!$shiftName) {
                        $errors[] = "Baris " . ($rowIndex + 2) . ": Shift tidak boleh kosong";
                        continue;
                    }

                    if (!isset($users[$nip])) {
                        $errors[] = "Baris " . ($rowIndex + 2) . ": NIP $nip tidak ditemukan";
                        continue;
                    }

                    if (!isset($shifts[$shiftName])) {
                        $errors[] = "Baris " . ($rowIndex + 2) . ": Shift '$shiftName' tidak ditemukan";
                        continue;
                    }

                    \App\Models\UserSchedule::updateOrCreate(
                        ['user_id' => $users[$nip]->id, 'date' => $date],
                        ['shift_id' => $shifts[$shiftName]->id, 'created_by' => $request->user()->id]
                    );
                    $imported++;
                } catch (\Exception $e) {
                    $errors[] = "Baris " . ($rowIndex + 2) . ": " . $e->getMessage();
                }
            }

            return response()->json([
                'success' => true,
                'message' => "Berhasil import $imported jadwal",
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

    /**
     * Download schedule template
     */
    public function downloadScheduleTemplate()
    {
        $templatePath = base_path('JADWAL KARYAWAN.xlsx');

        if (file_exists($templatePath)) {
            return response()->download($templatePath, 'Template_JADWAL_KARYAWAN.xlsx');
        }

        $headers = ['NIP', 'TANGGAL', 'NAMA_SHIFT'];
        $shifts = \App\Models\Shift::all();
        $shiftNames = $shifts->pluck('name')->implode(', ');

        $sampleData = [
            ['2021C171', '2026-04-01', 'Shift Pagi'],
            ['2021C171', '2026-04-02', 'Shift Sore'],
            ['2021C172', '2026-04-01', 'Shift Malam'],
        ];

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Jadwal Karyawan');
        $sheet->fromArray($headers, null, 'A1');
        
        foreach ($sampleData as $idx => $row) {
            $sheet->fromArray($row, null, 'A' . ($idx + 2));
        }

        foreach (range('A', 'C') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Add shift reference sheet
        $shiftSheet = $spreadsheet->createSheet();
        $shiftSheet->setTitle('Daftar Shift');
        $shiftSheet->fromArray(['Nama Shift'], null, 'A1');
        foreach ($shifts as $idx => $shift) {
            $shiftSheet->setCellValue('A' . ($idx + 2), $shift->name);
        }
        $shiftSheet->getColumnDimension('A')->setAutoSize(true);

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $tempFile = tempnam(sys_get_temp_dir(), 'schedule_template');
        $writer->save($tempFile);

        return response()->download($tempFile, 'Template_JADWAL_KARYAWAN.xlsx')->deleteFileAfterSend(true);
    }
}
