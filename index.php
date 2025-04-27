<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventaris Barang Sederhana</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" integrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <style>
        /* CSS Kustom */
        body { padding-top: 20px; }
        .action-buttons button { margin-right: 5px; }
        th.sortable { cursor: pointer; position: relative; }
        th.sortable::after { content: '\f0dc'; /* fa-sort */ font-family: 'Font Awesome 6 Free'; font-weight: 900; position: absolute; right: 8px; color: #aaa; transition: color 0.2s; }
        th.sortable:hover::after { color: #555; }
        th.sortable.asc::after { content: '\f0de'; /* fa-sort-up */ color: #333; }
        th.sortable.desc::after { content: '\f0dd'; /* fa-sort-down */ color: #333; }
    </style>
</head>
<body>

<div class="container">
    <h1 class="mb-4 text-center">Inventaris Barang Sederhana</h1>

    <div class="row mb-3">
        <div class="col-md-6 mb-2 mb-md-0">
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#itemModal" id="btnTambahBarang">
                <i class="fas fa-plus"></i> Tambah Barang
            </button>
            <button class="btn btn-outline-secondary ms-2" id="btnKelolaKategori" title="Kelola Kategori">
                <i class="fas fa-tags"></i> Kelola Kategori
            </button>
        </div>
        <div class="col-md-6">
            <div class="input-group">
                <input type="text" class="form-control" placeholder="Cari Nama Barang/Kategori..." id="searchInput" aria-label="Cari Barang">
                <button class="btn btn-outline-secondary" type="button" id="searchButton" title="Cari"><i class="fas fa-search"></i></button>
            </div>
        </div>
    </div>

    <div class="table-responsive shadow-sm">
        <table class="table table-striped table-bordered table-hover caption-top">
             <caption>Daftar Barang Inventaris</caption>
            <thead class="table-dark">
                <tr>
                    <th class="sortable" data-column="i.id">ID</th>
                    <th class="sortable" data-column="i.nama_barang">Nama Barang</th>
                    <th class="sortable" data-column="c.nama_kategori">Kategori</th>
                    <th class="sortable" data-column="i.jumlah">Jumlah</th>
                    <th class="sortable" data-column="i.satuan">Satuan</th>
                    <th class="sortable" data-column="i.tanggal_masuk">Tgl Masuk</th>
                    <th class="sortable" data-column="i.harga_beli">Harga Beli</th>
                    <th style="width: 110px;">Aksi</th>
                </tr>
            </thead>
            <tbody id="inventoryTableBody">
                <tr>
                    <td colspan="8" class="text-center p-5"><i class="fas fa-spinner fa-spin fa-2x"></i><br>Memuat data...</td>
                </tr>
            </tbody>
        </table>
    </div>

     <nav aria-label="Page navigation Inventaris" class="mt-3 d-flex justify-content-center">
        <ul class="pagination" id="pagination">
            </ul>
    </nav>

</div> <div class="modal fade" id="itemModal" tabindex="-1" aria-labelledby="itemModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="itemModalLabel">Tambah Barang Baru</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="itemForm" novalidate>
            <input type="hidden" id="itemId" name="id">
            <input type="hidden" id="action" name="action" value="create">

             <div class="mb-3">
                <label for="nama_barang" class="form-label">Nama Barang <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="nama_barang" name="nama_barang" required>
                <div class="invalid-feedback">Nama barang tidak boleh kosong.</div>
              </div>

             <div class="mb-3">
                  <label for="category_id" class="form-label">Kategori</label>
                  <select class="form-select" id="category_id" name="category_id">
                      <option value="">-- Pilih Kategori --</option>
                      </select>
                  </div>

             <div class="row">
                 <div class="col-md-6 mb-3">
                    <label for="jumlah" class="form-label">Jumlah <span class="text-danger">*</span></label>
                    <input type="number" class="form-control" id="jumlah" name="jumlah" required min="0">
                    <div class="invalid-feedback">Jumlah harus angka 0 atau lebih.</div>
                  </div>
                 <div class="col-md-6 mb-3">
                    <label for="satuan" class="form-label">Satuan</label>
                    <input type="text" class="form-control" id="satuan" name="satuan" placeholder="Contoh: pcs, kg, box">
                  </div>
             </div>

              <div class="row">
                 <div class="col-md-6 mb-3">
                    <label for="tanggal_masuk" class="form-label">Tanggal Masuk</label>
                    <input type="date" class="form-control" id="tanggal_masuk" name="tanggal_masuk">
                  </div>
                 <div class="col-md-6 mb-3">
                    <label for="harga_beli" class="form-label">Harga Beli</label>
                    <input type="number" class="form-control" id="harga_beli" name="harga_beli" step="0.01" min="0" placeholder="Contoh: 50000">
                     <div class="invalid-feedback">Harga beli harus angka 0 atau lebih.</div>
                  </div>
              </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
        <button type="button" class="btn btn-primary" id="saveItemButton">
             <i class="fas fa-save"></i> Simpan
        </button>
      </div>
    </div>
  </div>
</div>
<div class="modal fade" id="categoryManageModal" tabindex="-1" aria-labelledby="categoryManageModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="categoryManageModalLabel">Kelola Kategori Barang</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="categoryForm" class="mb-4 p-3 border rounded bg-light" novalidate>
                    <input type="hidden" id="categoryId" name="id">
                    <input type="hidden" id="categoryAction" name="action" value="create_category">
                    <div class="row g-2 align-items-end">
                        <div class="col-md">
                            <label for="nama_kategori_input" class="form-label visually-hidden">Nama Kategori</label>
                            <input type="text" class="form-control" id="nama_kategori_input" name="nama_kategori" required placeholder="Masukkan nama kategori...">
                            <div class="invalid-feedback">Nama kategori tidak boleh kosong.</div>
                        </div>
                        <div class="col-md-auto">
                            <button type="submit" class="btn btn-success" id="saveCategoryButton">
                                <i class="fas fa-plus"></i> Tambah
                            </button>
                            <button type="button" class="btn btn-secondary" id="cancelEditCategoryButton" style="display: none;" title="Batal Edit">
                                <i class="fas fa-times"></i> Batal
                            </button>
                        </div>
                    </div>
                </form>

                <h6>Daftar Kategori Tersimpan:</h6>
                <div class="table-responsive" style="max-height: 300px; overflow-y: auto;">
                    <table class="table table-sm table-striped table-bordered table-hover">
                        <thead class="table-light sticky-top">
                            <tr>
                                <th style="width: 60px;">ID</th>
                                <th>Nama Kategori</th>
                                <th style="width: 100px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="categoryTableBody">
                            <tr><td colspan="3" class="text-center p-4"><i class="fas fa-spinner fa-spin"></i> Memuat...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-labelledby="deleteConfirmModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteConfirmModalLabel"><i class="fas fa-exclamation-triangle"></i> Konfirmasi Hapus</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Apakah Anda yakin ingin menghapus barang <strong id="itemNameToDelete"></strong>? Tindakan ini tidak dapat diurungkan.
                <input type="hidden" id="deleteItemId">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteButton">
                     <i class="fas fa-trash"></i> Ya, Hapus
                </button>
            </div>
        </div>
    </div>
</div>
<script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
<script src="js/script.js"></script>

</body>
</html>