
<?php
require_once "koneksi.php";

function e($nilai) {
    return htmlspecialchars((string) $nilai, ENT_QUOTES, "UTF-8");
}

$id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    http_response_code(400);
    exit("ID tugas tidak valid.");
}

$stmt = $pdo->prepare("SELECT * FROM tugas WHERE id = :id");
$stmt->execute([":id" => $id]);
$tugas = $stmt->fetch();

if (!$tugas) {
    http_response_code(404);
    exit("Tugas tidak ditemukan.");
}

$pesan = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nama = trim($_POST["nama"] ?? "");
    $matkul = trim($_POST["matkul"] ?? "");
    $deadline = $_POST["deadline"] ?? "";
    $catatan = trim($_POST["catatan"] ?? "");
    $namaFileBaru = $tugas["nama_file"];
    $pathFileBaru = null;

    $tanggal = DateTime::createFromFormat("!Y-m-d", $deadline);

    if (
        $nama === "" ||
        strlen($nama) > 150 ||
        $matkul === "" ||
        strlen($matkul) > 100 ||
        !$tanggal ||
        $tanggal->format("Y-m-d") !== $deadline
    ) {
        $pesan = "Periksa kembali nama tugas, mata kuliah, dan deadline.";
    } else {
        try {
            if (
                isset($_FILES["fileTugas"]) &&
                $_FILES["fileTugas"]["error"] !== UPLOAD_ERR_NO_FILE
            ) {
                $file = $_FILES["fileTugas"];

                if ($file["error"] !== UPLOAD_ERR_OK) {
                    throw new RuntimeException("Upload file gagal.");
                }

                if ($file["size"] > 10 * 1024 * 1024) {
                    throw new RuntimeException("Ukuran file maksimal 10 MB.");
                }

                $ekstensi = strtolower(
                    pathinfo($file["name"], PATHINFO_EXTENSION)
                );

                $diizinkan = [
                    "pdf", "doc", "docx", "ppt", "pptx",
                    "xls", "xlsx", "zip", "txt"
                ];

                if (!in_array($ekstensi, $diizinkan, true)) {
                    throw new RuntimeException("Format file tidak diizinkan.");
                }

                $folder = __DIR__ . "/uploads";

                if (!is_dir($folder) && !mkdir($folder, 0755, true)) {
                    throw new RuntimeException("Folder upload gagal dibuat.");
                }

                $namaFileBaru = bin2hex(random_bytes(16)) . "." . $ekstensi;
                $pathFileBaru = $folder . "/" . $namaFileBaru;

                if (!move_uploaded_file($file["tmp_name"], $pathFileBaru)) {
                    throw new RuntimeException("File gagal disimpan.");
                }
            }

            $sql = "UPDATE tugas
                    SET nama_tugas = :nama,
                        mata_kuliah = :matkul,
                        deadline = :deadline,
                        nama_file = :nama_file,
                        catatan = :catatan
                    WHERE id = :id";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ":nama" => $nama,
                ":matkul" => $matkul,
                ":deadline" => $deadline,
                ":nama_file" => $namaFileBaru,
                ":catatan" => $catatan !== "" ? $catatan : null,
                ":id" => $id
            ]);

            if (
                $pathFileBaru !== null &&
                !empty($tugas["nama_file"])
            ) {
                $fileLama = __DIR__ . "/uploads/" .
                    basename($tugas["nama_file"]);

                if (is_file($fileLama)) {
                    unlink($fileLama);
                }
            }

            header("Location: index.php?success=1#daftar-tugas");
            exit;

        } catch (Throwable $ex) {
            if ($pathFileBaru !== null && is_file($pathFileBaru)) {
                unlink($pathFileBaru);
            }

            error_log($ex->getMessage());

            $pesan = $ex instanceof RuntimeException
                ? $ex->getMessage()
                : "Tugas gagal diperbarui.";
        }
    }

    $tugas["nama_tugas"] = $nama;
    $tugas["mata_kuliah"] = $matkul;
    $tugas["deadline"] = $deadline;
    $tugas["catatan"] = $catatan;
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Tugas - MyTask</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<main class="main edit-page">
    <section class="panel">
        <h1>Edit Tugas</h1>
        <p class="panel-desc">Perbarui informasi tugas kuliahmu.</p>

        <?php if ($pesan !== ""): ?>
            <div class="notice error"><?= e($pesan) ?></div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" class="form-grid">
            <div class="form-group">
                <label for="nama">Nama Tugas</label>
                <input
                    id="nama"
                    name="nama"
                    maxlength="150"
                    required
                    value="<?= e($tugas["nama_tugas"]) ?>"
                >
            </div>

            <div class="form-group">
                <label for="matkul">Mata Kuliah</label>
                <input
                    id="matkul"
                    name="matkul"
                    maxlength="100"
                    required
                    value="<?= e($tugas["mata_kuliah"]) ?>"
                >
            </div>

            <div class="form-group">
                <label for="deadline">Deadline</label>
                <input
                    id="deadline"
                    type="date"
                    name="deadline"
                    required
                    value="<?= e($tugas["deadline"]) ?>"
                >
            </div>

            <div class="form-group">
                <label for="fileTugas">Ganti File (opsional)</label>
                <input
                    id="fileTugas"
                    type="file"
                    name="fileTugas"
                    accept=".pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.zip,.txt"
                >
                <small>Biarkan kosong jika tidak ingin mengganti file.</small>
            </div>

            <div class="form-group full">
                <label for="catatan">Catatan</label>
                <textarea id="catatan" name="catatan"><?= e($tugas["catatan"] ?? "") ?></textarea>
            </div>

            <div class="form-group full">
                <button type="submit" class="btn">Simpan Perubahan</button>
                <a href="index.php#daftar-tugas" class="download-link">
                    Kembali ke Daftar Tugas
                </a>
            </div>
        </form>
    </section>
</main>
</body>
</html>
