// js/script.js (Versi Final Tanpa Upload Gambar)

$(document).ready(function() {
    // --- Variabel Global ---
    let currentPage = 1;
    let currentSearch = '';
    let currentSortColumn = 'i.id'; // Default sort dengan alias
    let currentSortDirection = 'ASC';
    const itemsPerPage = 10; // Jumlah item per halaman

    // --- Instansiasi Modal Bootstrap (jika diperlukan kontrol lebih) ---
    // const itemModal = new bootstrap.Modal(document.getElementById('itemModal'));
    // const categoryManageModal = new bootstrap.Modal(document.getElementById('categoryManageModal'));
    // const deleteConfirmModal = new bootstrap.Modal(document.getElementById('deleteConfirmModal'));

    // =========================================
    // === FUNGSI-FUNGSI HELPER              ===
    // =========================================

    // --- Fungsi Memuat Dropdown Kategori ---
    function loadCategoriesDropdown() {
        // console.log('Memuat dropdown kategori...');
        const categorySelect = $('#category_id');
        if (!categorySelect.length) { return; } // Keluar jika elemen tidak ada

        const defaultOption = categorySelect.find('option:first');
        categorySelect.empty().append(defaultOption);

        $.ajax({
            url: 'ajax_handler.php', type: 'GET', dataType: 'json', data: { action: 'get_categories' },
            success: function(response) {
                if (response.status === 'success' && response.data) {
                    if (response.data.length > 0) {
                        response.data.forEach(category => {
                            categorySelect.append(`<option value="${category.id}">${category.nama_kategori}</option>`);
                        });
                    }
                } else { console.error("Gagal memuat dropdown kategori:", response.message); }
            },
            error: function(xhr) { console.error("AJAX Error dropdown kategori:", xhr.responseText); }
        });
    }

    // --- Fungsi Memuat Tabel Kategori di Modal ---
    function loadCategoriesTable() {
        // console.log('Memuat tabel kategori...');
        const categoryTableBody = $('#categoryTableBody');
        categoryTableBody.html('<tr><td colspan="3" class="text-center p-3"><i class="fas fa-spinner fa-spin"></i></td></tr>');

        $.ajax({
            url: 'ajax_handler.php', type: 'GET', dataType: 'json', data: { action: 'get_categories' },
            success: function(response) {
                categoryTableBody.empty();
                if (response.status === 'success' && response.data) {
                    if (response.data.length > 0) {
                        response.data.forEach(cat => {
                            // Escape HTML untuk nama kategori sebelum dimasukkan ke data-*
                            const escapedCatName = $('<textarea />').html(cat.nama_kategori).text();
                            categoryTableBody.append(`
                                <tr data-id="${cat.id}" data-nama="${escapedCatName}">
                                    <td>${cat.id}</td>
                                    <td>${cat.nama_kategori}</td>
                                    <td class="text-nowrap">
                                        <button class="btn btn-sm btn-outline-warning btn-edit-category" title="Edit ${escapedCatName}">
                                            <i class="fas fa-edit fa-fw"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger btn-delete-category" title="Hapus ${escapedCatName}">
                                            <i class="fas fa-trash fa-fw"></i>
                                        </button>
                                    </td>
                                </tr>
                            `);
                        });
                    } else {
                        categoryTableBody.html('<tr><td colspan="3" class="text-center p-3">Belum ada kategori. Silakan tambahkan.</td></tr>');
                    }
                } else {
                    console.error("Gagal memuat tabel kategori:", response.message);
                    categoryTableBody.html('<tr><td colspan="3" class="text-center text-danger p-3">Gagal memuat data.</td></tr>');
                }
            },
            error: function(xhr) {
                console.error("AJAX Error tabel kategori:", xhr.responseText);
                categoryTableBody.html('<tr><td colspan="3" class="text-center text-danger p-3">Error memuat data.</td></tr>');
            }
        });
    }

    // --- Fungsi Memuat Data Inventaris ---
    function loadInventory(page = 1, search = '', sortColumn = 'i.id', sortDirection = 'ASC') {
        currentPage = page; currentSearch = search; currentSortColumn = sortColumn; currentSortDirection = sortDirection;

        $('#inventoryTableBody').html('<tr><td colspan="8" class="text-center p-5"><i class="fas fa-spinner fa-spin fa-2x"></i><br>Memuat data...</td></tr>');

        $.ajax({
            url: 'ajax_handler.php', type: 'GET', dataType: 'json',
            data: { action: 'read', page: currentPage, limit: itemsPerPage, search: currentSearch, sort: sortColumn, direction: sortDirection },
            success: function(response) {
                const tableBody = $('#inventoryTableBody'); tableBody.empty();
                const items = response.data; const totalItems = response.total;

                if (items && items.length > 0) {
                    items.forEach(item => {
                        let tanggalMasukFormatted = item.tanggal_masuk ? new Date(item.tanggal_masuk + 'T00:00:00').toLocaleDateString('id-ID', { year: 'numeric', month: 'short', day: 'numeric' }) : '-'; // Tambah T00:00:00 untuk hindari masalah timezone
                        let hargaBeliFormatted = item.harga_beli !== null ? new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(item.harga_beli) : '-';
                        let namaKategori = item.nama_kategori ? item.nama_kategori : '<span class="text-muted fst-italic">Tanpa Kategori</span>';

                        // Escape nama barang untuk title tombol
                        const escapedItemName = $('<textarea />').html(item.nama_barang).text();

                        tableBody.append(`
                            <tr data-id="${item.id}">
                                <td>${item.id}</td>
                                <td>${item.nama_barang || '-'}</td>
                                <td>${namaKategori}</td>
                                <td>${item.jumlah || '0'}</td>
                                <td>${item.satuan || '-'}</td>
                                <td>${tanggalMasukFormatted}</td>
                                <td>${hargaBeliFormatted}</td>
                                <td class="action-buttons text-nowrap">
                                    <button class="btn btn-sm btn-outline-warning btn-edit" title="Edit ${escapedItemName}">
                                        <i class="fas fa-edit fa-fw"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger btn-delete" title="Hapus ${escapedItemName}">
                                        <i class="fas fa-trash fa-fw"></i>
                                    </button>
                                </td>
                            </tr>
                        `);
                    });
                } else {
                    tableBody.html('<tr><td colspan="8" class="text-center p-4">Tidak ada data ditemukan.</td></tr>');
                }
                updatePagination(totalItems, itemsPerPage, currentPage);
                updateSortIndicators();
            },
            error: function(xhr) {
                console.error("Error loading inventory data:", xhr.responseText);
                $('#inventoryTableBody').html('<tr><td colspan="8" class="text-center text-danger p-4">Gagal memuat data inventaris. Periksa koneksi atau log server.</td></tr>');
            }
        });
    }

    // --- Fungsi Update Pagination ---
    function updatePagination(totalItems, itemsPerPage, currentPage) {
        const paginationUl = $('#pagination'); paginationUl.empty(); const totalPages = Math.ceil(totalItems / itemsPerPage); if (totalPages <= 1) return;
        const createPageLink = (page, label, isDisabled = false, isActive = false) => {
            const disabledClass = isDisabled ? ' disabled' : '';
            const activeClass = isActive ? ' active' : '';
            const ariaCurrent = isActive ? ' aria-current="page"' : '';
            const tag = isDisabled || isActive ? 'span' : 'a';
            const href = isDisabled || isActive ? '' : ' href="#"';
            return `<li class="page-item${activeClass}${disabledClass}"><${tag} class="page-link" data-page="${page}"${href}${ariaCurrent}>${label}</${tag}></li>`;
        };
        paginationUl.append(createPageLink(currentPage - 1, '&laquo;', currentPage === 1)); // Previous
        let startPage = Math.max(1, currentPage - 2); let endPage = Math.min(totalPages, currentPage + 2);
        if (currentPage <= 3) { endPage = Math.min(totalPages, 5); } if (currentPage > totalPages - 3) { startPage = Math.max(1, totalPages - 4); }
        if (startPage > 1) { paginationUl.append(createPageLink(1, '1')); if (startPage > 2) { paginationUl.append(createPageLink(0, '...', true)); } }
        for (let i = startPage; i <= endPage; i++) { paginationUl.append(createPageLink(i, i, false, i === currentPage)); }
        if (endPage < totalPages) { if (endPage < totalPages - 1) { paginationUl.append(createPageLink(0, '...', true)); } paginationUl.append(createPageLink(totalPages, totalPages)); }
        paginationUl.append(createPageLink(currentPage + 1, '&raquo;', currentPage === totalPages)); // Next
    }

    // --- Fungsi Update Indikator Sort ---
    function updateSortIndicators() {
         $('th.sortable').removeClass('asc desc');
         $(`th.sortable[data-column="${currentSortColumn}"]`).addClass(currentSortDirection.toLowerCase());
    }

    // --- Fungsi Reset Form Item ---
    function resetItemForm() {
        $('#itemModalLabel').text('Tambah Barang Baru');
        $('#itemForm')[0].reset();
        $('#itemId').val('');
        $('#action').val('create');
        $('#category_id').val('');
        $('#itemForm').removeClass('was-validated');
        $('#itemForm input, #itemForm select').removeClass('is-invalid');
    }

    // --- Fungsi Reset Form Kategori ---
     function resetCategoryForm() {
        $('#categoryForm')[0].reset();
        $('#categoryId').val('');
        $('#categoryAction').val('create_category');
        $('#categoryForm').removeClass('was-validated');
        $('#saveCategoryButton').html('<i class="fas fa-plus"></i> Tambah').prop('disabled', false);
        $('#cancelEditCategoryButton').hide();
    }

    // --- Notifikasi (Helper Sederhana) ---
    // Ganti dengan SweetAlert jika diinginkan
    function showNotification(message, type = 'success') {
        alert(message); // Ganti ini dengan implementasi notifikasi yang lebih baik
        // Contoh dengan SweetAlert:
        // Swal.fire({
        //     icon: type, // 'success', 'error', 'warning', 'info', 'question'
        //     title: type === 'success' ? 'Berhasil!' : 'Oops...',
        //     text: message,
        //     timer: type === 'success' ? 1500 : 3000,
        //     showConfirmButton: false
        // });
    }


    // =========================================
    // === EVENT LISTENERS                   ===
    // =========================================

    // --- Event Listeners Inventaris ---
    $('#pagination').on('click', 'a.page-link', function(e) { e.preventDefault(); const page = $(this).data('page'); if (page && !$(this).parent().hasClass('disabled') && !$(this).parent().hasClass('active')) { loadInventory(page, currentSearch, currentSortColumn, currentSortDirection); } });
    $('th.sortable').on('click', function() { const column = $(this).data('column'); let direction = (column === currentSortColumn && currentSortDirection === 'ASC') ? 'DESC' : 'ASC'; loadInventory(1, currentSearch, column, direction); });
    $('#searchButton').on('click', function() { const searchTerm = $('#searchInput').val().trim(); loadInventory(1, searchTerm, currentSortColumn, currentSortDirection); });
    $('#searchInput').on('keypress', function(e) { if (e.which === 13) { $('#searchButton').click(); } });

    // Tombol Tambah Barang
    $('#btnTambahBarang').on('click', function() {
        resetItemForm();
        // Pastikan dropdown kategori sudah terisi saat modal dibuka
        // loadCategoriesDropdown(); // Mungkin tidak perlu jika sudah diload di awal
    });

    // Tombol Simpan Item (Create/Update)
    $('#saveItemButton').on('click', function() {
        const form = $('#itemForm')[0];
        if (!form.checkValidity()) { form.classList.add('was-validated'); return; }
        form.classList.remove('was-validated');

        const formData = $('#itemForm').serialize();
        const button = $(this); const originalHtml = button.html();
        button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Menyimpan...');

        $.ajax({
            url: 'ajax_handler.php', type: 'POST', data: formData, dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    $('#itemModal').modal('hide'); // Gunakan Bootstrap instance jika ada
                    loadInventory(currentPage, currentSearch, currentSortColumn, currentSortDirection); // Reload data
                    showNotification(response.message || 'Operasi berhasil!');
                } else {
                    showNotification(response.message || 'Operasi gagal.', 'error');
                }
            },
            error: function(xhr) {
                console.error("Save item error:", xhr.responseText);
                showNotification('Terjadi kesalahan saat menyimpan data. Periksa console.', 'error');
            },
            complete: function() { button.prop('disabled', false).html(originalHtml); }
        });
    });

    // Tombol Edit Item
    $('#inventoryTableBody').on('click', '.btn-edit', function() {
        const row = $(this).closest('tr'); const itemId = row.data('id');
        resetItemForm(); // Reset dulu

        $.ajax({
            url: 'ajax_handler.php', type: 'GET', dataType: 'json', data: { action: 'get_item', id: itemId },
            success: function(response) {
                if (response.status === 'success' && response.data) {
                    const item = response.data;
                    $('#itemModalLabel').text('Edit Barang: ' + item.nama_barang); // Ganti judul modal
                    $('#action').val('update'); $('#itemId').val(item.id);
                    $('#nama_barang').val(item.nama_barang); $('#category_id').val(item.category_id || '');
                    $('#jumlah').val(item.jumlah); $('#satuan').val(item.satuan);
                    $('#tanggal_masuk').val(item.tanggal_masuk); $('#harga_beli').val(item.harga_beli);

                    $('#itemModal').modal('show'); // Tampilkan modal
                } else { showNotification(response.message || 'Gagal mengambil data item.', 'error'); }
            },
            error: function(xhr) { console.error("Get item error:", xhr.responseText); showNotification('Gagal mengambil data item. Periksa console.', 'error'); }
        });
    });

    // Tombol Hapus Item
    $('#inventoryTableBody').on('click', '.btn-delete', function() {
         const row = $(this).closest('tr'); const itemId = row.data('id'); const itemName = row.find('td:nth-child(2)').text();
         $('#itemNameToDelete').text(itemName); $('#deleteItemId').val(itemId);
         $('#deleteConfirmModal').modal('show'); // Tampilkan modal konfirmasi
     });

    // Tombol Konfirmasi Hapus Item
    $('#confirmDeleteButton').on('click', function() {
        const itemId = $('#deleteItemId').val(); const button = $(this); const originalHtml = button.html();
        button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Menghapus...');

        $.ajax({
            url: 'ajax_handler.php', type: 'POST', dataType: 'json', data: { action: 'delete', id: itemId },
            success: function(response) {
                 $('#deleteConfirmModal').modal('hide');
                if (response.status === 'success') {
                    const rowsOnPage = $('#inventoryTableBody tr[data-id]').length; // Hitung row data saja
                    let pageToLoad = currentPage;
                    // Mundur halaman jika item terakhir di halaman > 1 dihapus
                    if (rowsOnPage === 1 && currentPage > 1) { pageToLoad = currentPage - 1; }
                    loadInventory(pageToLoad, currentSearch, currentSortColumn, currentSortDirection); // Reload data
                    showNotification(response.message || 'Barang berhasil dihapus.');
                } else { showNotification(response.message || 'Gagal menghapus barang.', 'error'); }
            },
            error: function(xhr) {
                $('#deleteConfirmModal').modal('hide');
                console.error("Delete item error:", xhr.responseText);
                showNotification('Terjadi kesalahan saat menghapus data. Periksa console.', 'error');
            },
            complete: function() { button.prop('disabled', false).html(originalHtml); }
        });
    });


    // --- Event Listeners Kelola Kategori ---

    // Tombol "Kelola Kategori" Utama
    $('#btnKelolaKategori').on('click', function() {
        resetCategoryForm(); // Reset form sebelum buka
        loadCategoriesTable();
        $('#categoryManageModal').modal('show');
    });

    // Tombol Edit Kategori (di tabel modal)
    $('#categoryTableBody').on('click', '.btn-edit-category', function() {
        const row = $(this).closest('tr'); const catId = row.data('id'); const catName = row.data('nama');
        $('#categoryId').val(catId); $('#nama_kategori_input').val(catName).focus(); // Isi form dan fokus
        $('#categoryAction').val('update_category');
        $('#saveCategoryButton').html('<i class="fas fa-sync-alt"></i> Update'); // Ganti ikon/teks tombol simpan
        $('#cancelEditCategoryButton').show(); // Tampilkan tombol batal
    });

    // Tombol Batal Edit Kategori
    $('#cancelEditCategoryButton').on('click', function() {
        resetCategoryForm(); // Reset form ke state tambah baru
    });

    // Tombol Hapus Kategori (di tabel modal)
    $('#categoryTableBody').on('click', '.btn-delete-category', function() {
        const row = $(this).closest('tr'); const catId = row.data('id'); const catName = row.data('nama');
        const button = $(this);

        // Ganti confirm() standar dengan SweetAlert jika dipakai
        if (confirm(`Yakin ingin menghapus kategori "${catName}"?\nItem terkait akan kehilangan kategori ini.`)) {
            button.prop('disabled', true); // Disable tombol saat proses
            $.ajax({
                url: 'ajax_handler.php', type: 'POST', dataType: 'json', data: { action: 'delete_category', id: catId },
                success: function(response) {
                    if (response.status === 'success') {
                        loadCategoriesTable(); // Reload tabel kategori
                        loadCategoriesDropdown(); // Reload dropdown item
                        showNotification(response.message || 'Kategori dihapus.');
                    } else {
                        showNotification(response.message || 'Gagal menghapus kategori.', 'error');
                    }
                },
                error: function(xhr) { console.error("Delete category error:", xhr.responseText); showNotification('Error menghapus kategori.', 'error'); },
                complete: function() { button.prop('disabled', false); } // Enable tombol lagi
            });
        }
    });

    // Submit Form Tambah/Edit Kategori
    $('#categoryForm').on('submit', function(e) {
        e.preventDefault();
        const form = this;
        if (!form.checkValidity()) { form.classList.add('was-validated'); return; }
        form.classList.remove('was-validated');

        const formData = $(this).serialize();
        const saveButton = $('#saveCategoryButton'); const originalButtonHtml = saveButton.html();
        const currentAction = $('#categoryAction').val(); // Simpan action saat ini

        saveButton.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');

        $.ajax({
            url: 'ajax_handler.php', type: 'POST', dataType: 'json', data: formData,
            success: function(response) {
                if (response.status === 'success') {
                    resetCategoryForm(); // Reset form ke mode tambah
                    loadCategoriesTable();
                    loadCategoriesDropdown();
                    showNotification(response.message || 'Operasi kategori berhasil.');
                } else {
                    showNotification(response.message || 'Operasi kategori gagal.', 'error');
                }
            },
            error: function(xhr) { console.error("Save category error:", xhr.responseText); showNotification('Error menyimpan kategori.', 'error'); },
            complete: function() {
                // Kembalikan teks tombol berdasarkan aksi SEBELUM submit
                 if (currentAction === 'create_category') {
                    saveButton.prop('disabled', false).html('<i class="fas fa-plus"></i> Tambah');
                 } else {
                    saveButton.prop('disabled', false).html('<i class="fas fa-sync-alt"></i> Update');
                 }
                 // Jika reset otomatis, tombol batal tetap disembunyikan oleh resetCategoryForm()
            }
        });
    });


    // =========================================
    // === INISIALISASI AWAL                 ===
    // =========================================
    loadCategoriesDropdown();
    loadInventory();

}); // Akhir $(document).ready()