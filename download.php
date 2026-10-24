
<?php
require_once "koneksi_hosting.php";

$id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    http_response_code(400);
    exit("ID tugas tidak valid.");
}

$stmt = $pdo->prepare(
    "SELECT nama_tugas, nama_file FROM tugas WHERE id = :id"
);
$stmt->execute([":id" => $id]);
$tugas = $stmt->fetch();

if (!$tugas || empty($tugas["nama_file"])) {
    http_response_code(404);
    exit("File tugas tidak ditemukan.");
}

$namaFile = basename($tugas["nama_file"]);
$path = __DIR__ . "/uploads/" . $namaFile;

if (!is_file($path)) {
    http_response_code(404);
    exit("File tidak ditemukan di folder penyimpanan.");
}

$ekstensi = strtolower(pathinfo($namaFile, PATHINFO_EXTENSION));

$tipeFile = [
    "pdf" => "application/pdf",
    "doc" => "application/msword",
    "docx" => "application/vnd.openxmlformats-officedocument.wordprocessingml.document",
    "ppt" => "application/vnd.ms-powerpoint",
    "pptx" => "application/vnd.openxmlformats-officedocument.presentationml.presentation",
    "xls" => "application/vnd.ms-excel",
    "xlsx" => "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
    "zip" => "application/zip",
    "txt" => "text/plain"
];

if (!isset($tipeFile[$ekstensi])) {
    http_response_code(415);
    exit("Format file tidak didukung.");
}

$namaUnduhan = preg_replace(
    '/[^A-Za-z0-9_-]/',
    '_',
    $tugas["nama_tugas"]
);

$namaUnduhan = trim($namaUnduhan, "_");

if ($namaUnduhan === "") {
    $namaUnduhan = "tugas";
}

header("Content-Type: " . $tipeFile[$ekstensi]);
header("Content-Length: " . filesize($path));
header(
    'Content-Disposition: attachment; filename="' .
    $namaUnduhan . "." . $ekstensi . '"'
);
header("X-Content-Type-Options: nosniff");

readfile($path);
exit;
