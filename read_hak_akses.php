<?php
require 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;

$spreadsheet = IOFactory::load('DATA PEGAWAI/HAK AKSES.xlsx');
foreach ($spreadsheet->getSheetNames() as $name) {
    echo "=== Sheet: $name ===\n";
    $sheet = $spreadsheet->getSheetByName($name);
    $data = $sheet->toArray();
    foreach ($data as $row) {
        $vals = array_map(function($v) { return $v ?? ''; }, $row);
        echo implode(' | ', $vals) . "\n";
    }
    echo "\n";
}
