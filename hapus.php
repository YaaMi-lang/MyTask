
<?php
require_once "koneksi_hosting.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: index.php#daftar-tugas");
    exit;
}

$id = filter_input(INPUT_POST, "id", FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    http_response_code(400);
    exit("ID tugas tidak valid.");
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare(
        "SELECT nama_file FROM tugas WHERE id = :id FOR UPDATE"
    );
    $stmt->execute([":id" => $id]);
    $tugas = $stmt->fetch();

    if (!$tugas) {
        $pdo->rollBack();
        header("Location: index.php#daftar-tugas");
        exit;
    }

    $stmt = $pdo->prepare("DELETE FROM tugas WHERE id = :id");
    $stmt->execute([":id" => $id]);

    $pdo->commit();

    if (!empty($tugas["nama_file"])) {
        $path = __DIR__ . "/uploads/" .
            basename($tugas["nama_file"]);

        if (is_file($path)) {
            if (!unlink($path)) {
                error_log("File tugas gagal dihapus: " . $path);
            }
        }
    }

    header("Location: index.php?deleted=1#daftar-tugas");
    exit;

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log($e->getMessage());
    http_response_code(500);
    exit("Tugas gagal dihapus. Silakan coba kembali.");
}
