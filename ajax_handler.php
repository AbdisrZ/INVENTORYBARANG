<?php
// ajax_handler.php (Versi Final Tanpa Upload Gambar)

// Selalu mulai session jika Anda berencana menambahkan fitur login nanti
// session_start();

// Set header agar output selalu JSON dan mencegah caching response AJAX
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Tanggal di masa lalu

// Memuat file koneksi database
require 'db.php'; // Pastikan file ini ada dan benar

// Respons Default
$response = ['status' => 'error', 'message' => 'Aksi tidak valid atau tidak ditentukan.'];
// Ambil aksi dari request (utamakan POST, fallback ke GET)
$action = $_POST['action'] ?? $_GET['action'] ?? null;

// --- Blok Utama Pengolahan Aksi ---
try {
    // Pastikan koneksi $pdo dari db.php tersedia
    if (!isset($pdo)) {
        throw new Exception("Koneksi database tidak tersedia (PDO tidak terdefinisi).");
    }

    // Cek otorisasi di sini jika sudah implementasi login
    // if (!isset($_SESSION['user_id'])) {
    //     throw new Exception("Akses ditolak. Silakan login terlebih dahulu.");
    // }

    // Proses aksi berdasarkan parameter 'action'
    switch ($action) {

        // =========================================
        // === AKSI TERKAIT KATEGORI BARANG      ===
        // =========================================

        case 'get_categories': // Untuk dropdown & tabel kelola kategori
            $stmt = $pdo->query("SELECT id, nama_kategori FROM categories ORDER BY nama_kategori ASC");
            // Set fetch mode jika belum diatur default di db.php
            // $stmt->setFetchMode(PDO::FETCH_ASSOC);
            $categories = $stmt->fetchAll();
            $response = ['status' => 'success', 'data' => $categories];
            break;

        case 'create_category':
            $nama_kategori = trim($_POST['nama_kategori'] ?? '');

            if (empty($nama_kategori)) {
                throw new Exception('Nama kategori tidak boleh kosong.');
            }

            // Cek duplikasi nama kategori (case-insensitive check lebih baik)
            $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM categories WHERE LOWER(nama_kategori) = LOWER(:nama)");
            $stmtCheck->bindParam(':nama', $nama_kategori);
            $stmtCheck->execute();
            if ($stmtCheck->fetchColumn() > 0) {
                throw new Exception('Nama kategori "' . htmlspecialchars($nama_kategori) . '" sudah ada.');
            }

            // Insert kategori baru
            $sql = "INSERT INTO categories (nama_kategori) VALUES (:nama_kategori)";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':nama_kategori', $nama_kategori);

            if ($stmt->execute()) {
                $response = ['status' => 'success', 'message' => 'Kategori "' . htmlspecialchars($nama_kategori) . '" berhasil ditambahkan.'];
            } else {
                throw new Exception('Gagal menambahkan kategori ke database.');
            }
            break;

        case 'update_category':
            $id = $_POST['id'] ?? null;
            $nama_kategori = trim($_POST['nama_kategori'] ?? '');

            if (empty($id) || !filter_var($id, FILTER_VALIDATE_INT)) {
                throw new Exception('ID kategori tidak valid.');
            }
            if (empty($nama_kategori)) {
                throw new Exception('Nama kategori tidak boleh kosong.');
            }

            // Cek duplikasi nama (kecuali untuk ID yang sedang diedit)
            $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM categories WHERE LOWER(nama_kategori) = LOWER(:nama) AND id != :id");
            $stmtCheck->bindParam(':nama', $nama_kategori);
            $stmtCheck->bindParam(':id', $id, PDO::PARAM_INT);
            $stmtCheck->execute();
            if ($stmtCheck->fetchColumn() > 0) {
                throw new Exception('Nama kategori "' . htmlspecialchars($nama_kategori) . '" sudah digunakan oleh kategori lain.');
            }

            // Update kategori
            $sql = "UPDATE categories SET nama_kategori = :nama_kategori WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':nama_kategori', $nama_kategori);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);

            if ($stmt->execute()) {
                if ($stmt->rowCount() > 0) {
                    $response = ['status' => 'success', 'message' => 'Kategori berhasil diperbarui menjadi "' . htmlspecialchars($nama_kategori) . '".'];
                } else {
                    // Bisa jadi nama sama persis atau ID tidak ditemukan
                     $stmtCheckExist = $pdo->prepare("SELECT COUNT(*) FROM categories WHERE id = :id");
                     $stmtCheckExist->bindParam(':id', $id, PDO::PARAM_INT);
                     $stmtCheckExist->execute();
                     if ($stmtCheckExist->fetchColumn() > 0) {
                         $response = ['status' => 'success', 'message' => 'Tidak ada perubahan pada data kategori.'];
                     } else {
                          throw new Exception('Kategori dengan ID ' . $id . ' tidak ditemukan.');
                     }
                }
            } else {
                throw new Exception('Gagal memperbarui kategori di database.');
            }
            break;

        case 'delete_category':
            $id = $_POST['id'] ?? null;

            if (empty($id) || !filter_var($id, FILTER_VALIDATE_INT)) {
                throw new Exception('ID kategori tidak valid.');
            }

            // Hapus kategori (constraint ON DELETE SET NULL akan menangani relasi di tabel 'items')
            $sql = "DELETE FROM categories WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);

            if ($stmt->execute()) {
                if ($stmt->rowCount() > 0) {
                    $response = ['status' => 'success', 'message' => 'Kategori berhasil dihapus.'];
                } else {
                    throw new Exception('Kategori tidak ditemukan atau sudah dihapus sebelumnya.');
                }
            } else {
                // Cek apakah ada foreign key constraint error (jika tidak pakai ON DELETE SET NULL)
                // $errorInfo = $stmt->errorInfo();
                // if ($errorInfo[1] == 1451) { // Kode error MySQL untuk foreign key constraint
                //     throw new Exception('Kategori tidak dapat dihapus karena masih digunakan oleh item.');
                // }
                throw new Exception('Gagal menghapus kategori dari database.');
            }
            break;

        // =========================================
        // === AKSI TERKAIT ITEM INVENTARIS      ===
        // =========================================

        case 'read': // Baca Item
            $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
            $limit = isset($_GET['limit']) ? max(1, (int)$_GET['limit']) : 10;
            $offset = ($page - 1) * $limit;
            $search = isset($_GET['search']) ? trim($_GET['search']) : '';
            $sortColumn = isset($_GET['sort']) ? $_GET['sort'] : 'i.id';
            $sortDirection = isset($_GET['direction']) ? strtoupper($_GET['direction']) : 'ASC';

            $allowedSortColumns = ['i.id', 'i.nama_barang', 'c.nama_kategori', 'i.jumlah', 'i.satuan', 'i.tanggal_masuk', 'i.harga_beli'];
            if (!in_array($sortColumn, $allowedSortColumns)) { $sortColumn = 'i.id'; }
            if (!in_array($sortDirection, ['ASC', 'DESC'])) { $sortDirection = 'ASC'; }

            $baseSql = "FROM items i LEFT JOIN categories c ON i.category_id = c.id WHERE 1=1";
            $params = [];
            if (!empty($search)) {
                $baseSql .= " AND (i.nama_barang LIKE :search OR c.nama_kategori LIKE :search)";
                $params[':search'] = "%" . $search . "%";
            }

            $countSql = "SELECT COUNT(i.id) " . $baseSql;
            $stmtCount = $pdo->prepare($countSql);
            $stmtCount->execute($params);
            $totalItems = $stmtCount->fetchColumn();

            $sql = "SELECT i.id, i.nama_barang, i.jumlah, i.satuan, i.tanggal_masuk, i.harga_beli, i.category_id, c.nama_kategori "
                 . $baseSql
                 . " ORDER BY {$sortColumn} {$sortDirection} LIMIT :limit OFFSET :offset";

            $stmt = $pdo->prepare($sql);
            foreach ($params as $key => $value) { $stmt->bindValue($key, $value); }
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            $items = $stmt->fetchAll();

            $response = [
                'status' => 'success',
                'data' => $items,
                'total' => (int)$totalItems,
                'page' => $page,
                'limit' => $limit
            ];
            break;

        case 'get_item': // Ambil detail 1 item
            $id = $_GET['id'] ?? null;
            if (!$id || !filter_var($id, FILTER_VALIDATE_INT)) {
                 throw new Exception('ID item tidak valid.');
            }
            $stmt = $pdo->prepare("SELECT i.*, c.nama_kategori
                                    FROM items i
                                    LEFT JOIN categories c ON i.category_id = c.id
                                    WHERE i.id = :id");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            $item = $stmt->fetch();

            if ($item) {
                $response = ['status' => 'success', 'data' => $item];
            } else {
                throw new Exception('Item tidak ditemukan.');
            }
            break;

        case 'create': // Buat Item Baru
            $nama_barang = trim($_POST['nama_barang'] ?? '');
            $category_id = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
            $jumlah = isset($_POST['jumlah']) && $_POST['jumlah'] !== '' ? (int)$_POST['jumlah'] : null;
            $satuan = trim($_POST['satuan'] ?? '');
            $tanggal_masuk = !empty($_POST['tanggal_masuk']) ? $_POST['tanggal_masuk'] : null;
            $harga_beli = isset($_POST['harga_beli']) && $_POST['harga_beli'] !== '' ? filter_var($_POST['harga_beli'], FILTER_VALIDATE_FLOAT) : null;

            if (empty($nama_barang)) { throw new Exception('Nama Barang wajib diisi.'); }
            if ($jumlah === null || $jumlah < 0) { throw new Exception('Jumlah wajib diisi dan minimal 0.'); }
            if ($harga_beli !== null && $harga_beli === false) { throw new Exception('Format Harga Beli salah.'); }
            if ($harga_beli !== null && $harga_beli < 0) { throw new Exception('Harga Beli tidak boleh negatif.'); }
             // Validasi tanggal jika perlu
             if ($tanggal_masuk !== null && !preg_match("/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/", $tanggal_masuk)) {
                 throw new Exception('Format Tanggal Masuk salah (YYYY-MM-DD).');
             }

            $sql = "INSERT INTO items (nama_barang, category_id, jumlah, satuan, tanggal_masuk, harga_beli)
                    VALUES (:nama_barang, :category_id, :jumlah, :satuan, :tanggal_masuk, :harga_beli)";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':nama_barang', $nama_barang, PDO::PARAM_STR);
            $stmt->bindParam(':category_id', $category_id, PDO::PARAM_INT); // PDO handles NULL
            $stmt->bindParam(':jumlah', $jumlah, PDO::PARAM_INT);
            $stmt->bindParam(':satuan', $satuan, PDO::PARAM_STR);
            $stmt->bindParam(':tanggal_masuk', $tanggal_masuk);
            $stmt->bindParam(':harga_beli', $harga_beli);

            if ($stmt->execute()) {
                $response = ['status' => 'success', 'message' => 'Barang "' . htmlspecialchars($nama_barang) . '" berhasil ditambahkan.'];
            } else {
                throw new Exception('Gagal menyimpan data barang ke database.');
            }
            break;

        case 'update': // Update Item
            $id = $_POST['id'] ?? null;
            $nama_barang = trim($_POST['nama_barang'] ?? '');
            $category_id = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
            $jumlah = isset($_POST['jumlah']) && $_POST['jumlah'] !== '' ? (int)$_POST['jumlah'] : null;
            $satuan = trim($_POST['satuan'] ?? '');
            $tanggal_masuk = !empty($_POST['tanggal_masuk']) ? $_POST['tanggal_masuk'] : null;
            $harga_beli = isset($_POST['harga_beli']) && $_POST['harga_beli'] !== '' ? filter_var($_POST['harga_beli'], FILTER_VALIDATE_FLOAT) : null;

            if (!$id || !filter_var($id, FILTER_VALIDATE_INT)) { throw new Exception('ID item tidak valid.'); }
            if (empty($nama_barang)) { throw new Exception('Nama Barang wajib diisi.'); }
            if ($jumlah === null || $jumlah < 0) { throw new Exception('Jumlah wajib diisi dan minimal 0.'); }
            if ($harga_beli !== null && $harga_beli === false) { throw new Exception('Format Harga Beli salah.'); }
            if ($harga_beli !== null && $harga_beli < 0) { throw new Exception('Harga Beli tidak boleh negatif.'); }
             if ($tanggal_masuk !== null && !preg_match("/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/", $tanggal_masuk)) {
                 throw new Exception('Format Tanggal Masuk salah (YYYY-MM-DD).');
             }


            $sql = "UPDATE items SET
                        nama_barang = :nama_barang, category_id = :category_id, jumlah = :jumlah,
                        satuan = :satuan, tanggal_masuk = :tanggal_masuk, harga_beli = :harga_beli
                    WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':nama_barang', $nama_barang, PDO::PARAM_STR);
            $stmt->bindParam(':category_id', $category_id, PDO::PARAM_INT);
            $stmt->bindParam(':jumlah', $jumlah, PDO::PARAM_INT);
            $stmt->bindParam(':satuan', $satuan, PDO::PARAM_STR);
            $stmt->bindParam(':tanggal_masuk', $tanggal_masuk);
            $stmt->bindParam(':harga_beli', $harga_beli);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);

            if ($stmt->execute()) {
                if ($stmt->rowCount() > 0) {
                    $response = ['status' => 'success', 'message' => 'Barang "' . htmlspecialchars($nama_barang) . '" berhasil diperbarui.'];
                } else {
                     // Cek apakah ID ada, jika ada berarti tidak ada perubahan
                     $stmtCheckExist = $pdo->prepare("SELECT COUNT(*) FROM items WHERE id = :id");
                     $stmtCheckExist->bindParam(':id', $id, PDO::PARAM_INT);
                     $stmtCheckExist->execute();
                     if ($stmtCheckExist->fetchColumn() > 0) {
                         $response = ['status' => 'success', 'message' => 'Tidak ada perubahan data yang disimpan.'];
                     } else {
                          throw new Exception('Item dengan ID ' . $id . ' tidak ditemukan untuk diperbarui.');
                     }
                }
            } else {
                throw new Exception('Gagal memperbarui data barang di database.');
            }
            break;

        case 'delete': // Hapus Item
            $id = $_POST['id'] ?? null;
            if (!$id || !filter_var($id, FILTER_VALIDATE_INT)) {
                throw new Exception('ID item tidak valid.');
            }

            $sql = "DELETE FROM items WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);

            if ($stmt->execute()) {
                 if ($stmt->rowCount() > 0) {
                     $response = ['status' => 'success', 'message' => 'Barang berhasil dihapus.'];
                 } else {
                     throw new Exception('Barang tidak ditemukan atau sudah dihapus sebelumnya.');
                 }
            } else {
                throw new Exception('Gagal menghapus barang dari database.');
            }
            break;

        // --- Aksi Default Jika Tidak Dikenali ---
        default:
            // Biarkan respons default yang diatur di awal
            break;
    }

} catch (PDOException $e) {
    // Tangani error terkait database
    error_log("PDO Error [" . $e->getCode() . "]: " . $e->getMessage()); // Log error detail
    $response = ['status' => 'error', 'message' => 'Terjadi kesalahan pada database.'];
    // $response['debug_message'] = $e->getMessage(); // Uncomment for development

} catch (Exception $e) {
     // Tangani error umum lainnya (dari validasi, file, dll)
     error_log("General Error: " . $e->getMessage());
     // Kembalikan pesan error dari exception agar lebih informatif
     $response = ['status' => 'error', 'message' => $e->getMessage()];
}

// --- Output Akhir ---
// Selalu kirimkan respons dalam format JSON
echo json_encode($response);
// Hentikan eksekusi script setelah output dikirim
exit;
?>