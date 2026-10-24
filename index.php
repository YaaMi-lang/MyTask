
<?php
require_once "koneksi.php";

$pesan = "";
$jenisPesan = "error";

if (isset($_GET["success"])) {
    $pesan = "Tugas berhasil disimpan!";
    $jenisPesan = "success";
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nama = trim($_POST["nama"] ?? "");
    $matkul = trim($_POST["matkul"] ?? "");
    $deadline = $_POST["deadline"] ?? "";
    $catatan = trim($_POST["catatan"] ?? "");
    $namaFile = null;
    $lokasiFile = null;

    if ($nama === "" || $matkul === "" || $deadline === "") {
        $pesan = "Nama tugas, mata kuliah, dan deadline wajib diisi.";
    } elseif (
        !DateTime::createFromFormat("!Y-m-d", $deadline) ||
        DateTime::createFromFormat("!Y-m-d", $deadline)->format("Y-m-d") !== $deadline
    ) {
        $pesan = "Format tanggal deadline tidak valid.";
    } else {
        try {
            if (
                isset($_FILES["fileTugas"]) &&
                $_FILES["fileTugas"]["error"] !== UPLOAD_ERR_NO_FILE
            ) {
                $file = $_FILES["fileTugas"];

                if ($file["error"] !== UPLOAD_ERR_OK) {
                    throw new RuntimeException("Upload file gagal. Coba pilih file lagi.");
                }

                if ($file["size"] > 10 * 1024 * 1024) {
                    throw new RuntimeException("Ukuran file maksimal 10 MB.");
                }

                $ekstensi = strtolower(
                    pathinfo($file["name"], PATHINFO_EXTENSION)
                );

                $ekstensiDiizinkan = [
                    "pdf", "doc", "docx", "ppt", "pptx",
                    "xls", "xlsx", "zip", "txt"
                ];

                if (!in_array($ekstensi, $ekstensiDiizinkan, true)) {
                    throw new RuntimeException(
                        "Format file tidak diizinkan. Gunakan PDF, Word, PowerPoint, Excel, ZIP, atau TXT."
                    );
                }

                $folderUpload = __DIR__ . "/uploads";

                if (!is_dir($folderUpload)) {
                    if (!mkdir($folderUpload, 0755, true)) {
                        throw new RuntimeException(
                            "Folder uploads tidak dapat dibuat."
                        );
                    }
                }

                $namaFile = bin2hex(random_bytes(16)) . "." . $ekstensi;
                $lokasiFile = $folderUpload . "/" . $namaFile;

                if (!move_uploaded_file($file["tmp_name"], $lokasiFile)) {
                    throw new RuntimeException(
                        "File gagal dipindahkan ke folder uploads."
                    );
                }
            }

            $sql = "INSERT INTO tugas
                    (nama_tugas, mata_kuliah, deadline, nama_file, catatan)
                    VALUES (:nama, :matkul, :deadline, :nama_file, :catatan)";

            $stmt = $pdo->prepare($sql);

            $stmt->execute([
                ":nama" => $nama,
                ":matkul" => $matkul,
                ":deadline" => $deadline,
                ":nama_file" => $namaFile,
                ":catatan" => $catatan !== "" ? $catatan : null
            ]);

            header("Location: index.php?success=1");
            exit;

        } catch (Throwable $e) {
            if ($lokasiFile !== null && is_file($lokasiFile)) {
                unlink($lokasiFile);
            }

            error_log($e->getMessage());

            $pesan = $e instanceof RuntimeException
                ? $e->getMessage()
                : "Tugas gagal disimpan. Periksa konfigurasi database.";
        }
    }
}

$stmt = $pdo->query(
    "SELECT * FROM tugas ORDER BY created_at DESC, id DESC"
);
$tugas = $stmt->fetchAll();

$totalTugas = count($tugas);
$totalSelesai = 0;
$mataKuliah = [];

foreach ($tugas as $item) {
    if ($item["status"] === "Selesai") {
        $totalSelesai++;
    }

    $mataKuliah[$item["mata_kuliah"]] = true;
}

$totalMatkul = count($mataKuliah);

function e($nilai) {
    return htmlspecialchars(
        (string) $nilai,
        ENT_QUOTES,
        "UTF-8"
    );
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MyTask - Tugas Kuliahku</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="container">

    <aside class="sidebar">
        <div class="logo">MyTask.</div>

        <p>MENU UTAMA</p>
        <a href="#dashboard" class="menu active">Dashboard</a>
        <a href="#tambah-tugas" class="menu">Tambah Tugas</a>
        <a href="#daftar-tugas" class="menu">Daftar Tugas</a>

        <div class="notice">
            Kelola tugas kuliahmu dengan lebih rapi.
        </div>
    </aside>

    <main class="main" id="dashboard">

        <header class="header">
            <div>
                <h1>Dashboard</h1>
                <p class="subtitle">
                    Selamat datang di MyTask! Yuk, atur tugas kuliahmu.
                </p>
            </div>
            <div class="profile">Mahasiswa</div>
        </header>

        <section class="stats">
            <div class="stat-card">
                <p>Total Tugas</p>
                <h2><?= $totalTugas ?></h2>
            </div>

            <div class="stat-card">
                <p>Tugas Selesai</p>
                <h2><?= $totalSelesai ?></h2>
            </div>

            <div class="stat-card">
                <p>Mata Kuliah</p>
                <h2><?= $totalMatkul ?></h2>
            </div>
        </section>

        <?php if ($pesan !== ""): ?>
            <div class="notice <?= e($jenisPesan) ?>">
                <?= e($pesan) ?>
            </div>
        <?php endif; ?>

        <section class="panel" id="tambah-tugas">
            <h2>Tambah Tugas Baru</h2>
            <p class="panel-desc">
                Isi informasi tugas kuliah yang ingin kamu simpan.
            </p>

            <form
                action="index.php#daftar-tugas"
                method="POST"
                enctype="multipart/form-data"
                class="form-grid"
            >
                <div class="form-group">
                    <label for="nama">Nama Tugas</label>
                    <input
                        type="text"
                        name="nama"
                        id="nama"
                        maxlength="150"
                        placeholder="Contoh: Laporan praktikum"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="matkul">Mata Kuliah</label>
                    <input
                        type="text"
                        name="matkul"
                        id="matkul"
                        maxlength="100"
                        placeholder="Contoh: Pemrograman Web"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="deadline">Deadline</label>
                    <input
                        type="date"
                        name="deadline"
                        id="deadline"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="fileTugas">File Tugas (opsional)</label>
                    <input
                        type="file"
                        name="fileTugas"
                        id="fileTugas"
                        accept=".pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.zip,.txt"
                    >
                    <small>Maksimal 10 MB.</small>
                </div>

                <div class="form-group full">
                    <label for="catatan">Catatan (opsional)</label>
                    <textarea
                        name="catatan"
                        id="catatan"
                        placeholder="Tambahkan keterangan tugas..."
                    ></textarea>
                </div>

                <div class="form-group full">
                    <button type="submit" class="btn">
                        Simpan Tugas
                    </button>
                </div>
            </form>
        </section>

        <section class="panel" id="daftar-tugas">
            <h2>Daftar Tugas</h2>
            <p class="panel-desc">
                Semua tugas yang tersimpan di database MyTask.
            </p>

            <?php if (count($tugas) === 0): ?>
                <div class="empty">
                    <p>Belum ada tugas tersimpan.</p>
                    <p>Mulai dengan mengisi formulir di atas.</p>
                </div>
            <?php else: ?>
                <div class="task-list">
                    <?php foreach ($tugas as $item): ?>
                        <article class="task-card">
                            <div class="task-top">
                                <div>
                                    <h3><?= e($item["nama_tugas"]) ?></h3>
                                    <p class="subtitle">
                                        <?= e($item["mata_kuliah"]) ?>
                                    </p>
                                </div>

                                <span class="status">
                                    <?= e($item["status"]) ?>
                                </span>
                            </div>

                            <p class="task-deadline">
                                Deadline: <?= e($item["deadline"]) ?>
                            </p>

                            <?php if (!empty($item["catatan"])): ?>
                                <p class="task-note">
                                    <?= nl2br(e($item["catatan"])) ?>
                                </p>
                            <?php endif; ?>

                            <?php if (!empty($item["nama_file"])): ?>
                                <a
                                    class="download-link"
                                    href="download.php?id=<?= (int) $item["id"] ?>"
                                >
                                    Unduh file tugas
                                </a>
                            <?php else: ?>
                                <p class="subtitle">Belum ada file terlampir.</p>
                            <?php endif; ?>
                            
                            
<div class="task-actions">

    <a
        class="download-link"
        href="edit.php?id=<?= (int) $item["id"] ?>"
    >
        Edit Tugas
    </a>

    <form
        action="status.php"
        method="POST"
        class="task-action-form"
    >
        <input
            type="hidden"
            name="id"
            value="<?= (int) $item["id"] ?>"
        >

        <button type="submit" class="btn">
            <?= $item["status"] === "Selesai"
                ? "Tandai Belum Selesai"
                : "Tandai Selesai" ?>
        </button>
    </form>

    <form
        action="hapus.php"
        method="POST"
        class="task-action-form"
        onsubmit="return confirm('Yakin ingin menghapus tugas ini?');"
    >
        <input
            type="hidden"
            name="id"
            value="<?= (int) $item["id"] ?>"
        >

        <button type="submit" class="btn btn-danger">
            Hapus Tugas
        </button>
    </form>

</div>


                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

    </main>
</div>

</body>
</html>
