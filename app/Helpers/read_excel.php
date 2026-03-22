<?php
// Helper script to read Excel files using CLI PHP (which has ZipArchive)
// Called from web controllers when ZipArchive is not available in Apache PHP

require_once __DIR__ . '/../vendor/autoload.php';

$filePath = $argv[1] ?? null;
$maxPreviewRows = isset($argv[2]) ? (int)$argv[2] : 0; // 0 = all rows

if (!$filePath || !file_exists($filePath)) {
    echo json_encode(['error' => 'File not found']);
    exit(1);
}

try {
    $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);
    $worksheet = $spreadsheet->getActiveSheet();
    $sheetData = $worksheet->toArray();

    // Skip empty rows at top
    while (!empty($sheetData) && empty(array_filter($sheetData[0] ?? []))) {
        array_shift($sheetData);
    }

    if (empty($sheetData)) {
        echo json_encode(['error' => 'File kosong']);
        exit(1);
    }

    $headers = array_shift($sheetData);

    if ($maxPreviewRows > 0) {
        $preview = array_slice($sheetData, 0, $maxPreviewRows);
        echo json_encode([
            'headers' => $headers,
            'preview' => $preview,
            'total_rows' => count($sheetData),
        ]);
    } else {
        echo json_encode([
            'headers' => $headers,
            'rows' => $sheetData,
            'total_rows' => count($sheetData),
        ]);
    }
} catch (\Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
    exit(1);
}
