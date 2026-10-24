
<?php
session_start();
require_once "koneksi.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: index.php");
    exit;
}

$id = filter_input(INPUT_POST, "id", FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    http_response_code(400);
    exit("ID tugas tidak valid.");
}

try {
    $stmt = $pdo->prepare(
        "UPDATE tugas
         SET status = CASE
             WHEN status = 'Selesai' THEN 'Belum Selesai'
             ELSE 'Selesai'
         END
         WHERE id = :id"
    );

    $stmt->execute([":id" => $id]);

    header("Location: index.php#daftar-tugas");
    exit;

} catch (PDOException $e) {
    error_log($e->getMessage());
    http_response_code(500);
    exit("Status tugas gagal diperbarui.");
}
