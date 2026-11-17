<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StokAman - Manajemen Inventori UMKM</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-slate-100 text-slate-800">
    <nav class="bg-indigo-700 text-white px-6 py-4 shadow">
        <div class="max-w-7xl mx-auto flex justify-between items-center">
            <div>
                <h1 class="text-xl font-bold">StokAman</h1>
                <p class="text-xs text-indigo-200">Sistem Inventori Gudang UMKM (Metode FIFO)</p>
            </div>
            <a href="/health" target="_blank" class="text-xs bg-indigo-900 hover:bg-indigo-800 px-3 py-1.5 rounded">
                Cek /health
            </a>
        </div>
    </nav>

    <main class="max-w-7xl mx-auto p-6 space-y-6">
        {{-- Notifikasi Sukses / Error Validasi --}}
        @if(session('success'))
            <div class="bg-emerald-100 border border-emerald-400 text-emerald-800 px-4 py-3 rounded text-sm">
                {{ session('success') }}
            </div>
        @endif
        @if(session('error'))
            <div class="bg-rose-100 border border-rose-400 text-rose-800 px-4 py-3 rounded text-sm">
                {{ session('error') }}
            </div>
        @endif
        @if($errors->any())
            <div class="bg-rose-100 border border-rose-400 text-rose-800 px-4 py-3 rounded text-sm">
                <ul class="list-disc pl-5">
                    @foreach($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Peringatan Stok Minimum --}}
        @if($lowStockList->isNotEmpty())
            <div class="bg-amber-50 border-l-4 border-amber-500 p-4 rounded shadow-sm">
                <h2 class="font-semibold text-amber-800 text-sm">Peringatan Stok Minimum ({{ $lowStockList->count() }} Produk):</h2>
                <div class="mt-1.5 flex flex-wrap gap-2">
                    @foreach($lowStockList as $item)
                        <span class="text-xs bg-amber-200 text-amber-900 px-2.5 py-1 rounded font-medium">
                            {{ $item->sku }} - {{ $item->name }} (Sisa: {{ $item->current_stock }} / Min: {{ $item->min_stock }})
                        </span>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Form Transaksi Masuk & Keluar --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            {{-- Barang Masuk --}}
            <div class="bg-white p-5 rounded-lg shadow-sm">
                <h2 class="font-bold text-lg text-emerald-700 mb-3">Transaksi Barang Masuk</h2>
                <form action="{{ route('web.stock.in') }}" method="POST" class="space-y-3 text-sm">
                    @csrf
                    <div>
                        <label class="block font-medium mb-1">Produk</label>
                        <select name="product_id" class="w-full border rounded px-3 py-2" required>
                            @foreach($products as $p)
                                <option value="{{ $p->id }}">[{{ $p->sku }}] {{ $p->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block font-medium mb-1">Pemasok</label>
                        <select name="supplier_id" class="w-full border rounded px-3 py-2" required>
                            @foreach($suppliers as $s)
                                <option value="{{ $s->id }}">{{ $s->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-medium mb-1">Jumlah Masuk</label>
                            <input type="number" name="qty" min="1" class="w-full border rounded px-3 py-2" required>
                        </div>
                        <div>
                            <label class="block font-medium mb-1">Tanggal Masuk (Batch)</label>
                            <input type="datetime-local" name="received_at" class="w-full border rounded px-3 py-2">
                        </div>
                    </div>
                    <div>
                        <label class="block font-medium mb-1">Catatan</label>
                        <input type="text" name="note" placeholder="Contoh: Restock dari supplier" class="w-full border rounded px-3 py-2">
                    </div>
                    <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded font-medium">
                        Simpan Barang Masuk
                    </button>
                </form>
            </div>

            {{-- Barang Keluar (FIFO) --}}
            <div class="bg-white p-5 rounded-lg shadow-sm">
                <h2 class="font-bold text-lg text-rose-700 mb-3">Transaksi Barang Keluar (FIFO)</h2>
                <form action="{{ route('web.stock.out') }}" method="POST" class="space-y-3 text-sm">
                    @csrf
                    <div>
                        <label class="block font-medium mb-1">Produk</label>
                        <select name="product_id" class="w-full border rounded px-3 py-2" required>
                            @foreach($products as $p)
                                <option value="{{ $p->id }}">[{{ $p->sku }}] {{ $p->name }} (Stok: {{ $p->current_stock }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block font-medium mb-1">Jumlah Keluar</label>
                        <input type="number" name="qty" min="1" class="w-full border rounded px-3 py-2" required>
                    </div>
                    <div>
                        <label class="block font-medium mb-1">Catatan Order / Marketplace</label>
                        <input type="text" name="note" placeholder="Contoh: Order Shopee #1024" class="w-full border rounded px-3 py-2">
                    </div>
                    <button type="submit" class="bg-rose-600 hover:bg-rose-700 text-white px-4 py-2 rounded font-medium">
                        Proses Barang Keluar
                    </button>
                </form>
            </div>
        </div>

        {{-- Daftar Stok Produk --}}
        <div class="bg-white p-5 rounded-lg shadow-sm">
            <h2 class="font-bold text-lg mb-3">Daftar Stok Gudang (Akumulasi Batch)</h2>
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-100 border-b">
                            <th class="p-2.5">SKU</th>
                            <th class="p-2.5">Nama Produk</th>
                            <th class="p-2.5">Kategori</th>
                            <th class="p-2.5">Stok Minimum</th>
                            <th class="p-2.5">Stok Saat Ini</th>
                            <th class="p-2.5">Status</th>
                            <th class="p-2.5">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($products as $product)
                            <tr class="border-b hover:bg-slate-50">
                                <td class="p-2.5 font-mono">{{ $product->sku }}</td>
                                <td class="p-2.5 font-medium">{{ $product->name }}</td>
                                <td class="p-2.5">{{ $product->category->name ?? '-' }}</td>
                                <td class="p-2.5">{{ $product->min_stock }}</td>
                                <td class="p-2.5 font-bold">{{ $product->current_stock }}</td>
                                <td class="p-2.5">
                                    @if($product->is_low_stock)
                                        <span class="bg-rose-100 text-rose-700 text-xs px-2 py-1 rounded font-semibold">Stok Menipis</span>
                                    @else
                                        <span class="bg-emerald-100 text-emerald-700 text-xs px-2 py-1 rounded font-semibold">Aman</span>
                                    @endif
                                </td>
                                <td class="p-2.5">
                                    <div class="flex items-center gap-2">
                                        <a href="{{ route('web.dashboard', ['product_id' => $product->id]) }}" class="text-indigo-600 hover:underline text-xs font-medium">
                                            Kartu Stok
                                        </a>
                                        <span class="text-slate-300">|</span>
                                        <button type="button"
                                            onclick="openEditProductModal({{ $product->id }}, '{{ $product->category_id }}', '{{ addslashes($product->sku) }}', '{{ addslashes($product->name) }}', {{ $product->min_stock }})"
                                            class="text-amber-600 hover:underline text-xs font-medium">
                                            Edit
                                        </button>
                                        <span class="text-slate-300">|</span>
                                        <form action="{{ route('web.products.destroy', $product->id) }}" method="POST" onsubmit="return confirm('Yakin ingin menghapus produk ini?')" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-rose-600 hover:underline text-xs font-medium">Hapus</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="p-3 text-center text-slate-500">Belum ada produk.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Kartu Stok per Produk --}}
        @if($selectedProduct)
            <div class="bg-white p-5 rounded-lg shadow-sm">
                <div class="flex justify-between items-center mb-3">
                    <h2 class="font-bold text-lg">
                        Kartu Stok: [{{ $selectedProduct->sku }}] {{ $selectedProduct->name }}
                    </h2>
                    <span class="text-sm font-semibold text-indigo-700">Saldo Akhir: {{ $selectedProduct->current_stock }}</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-100 border-b">
                                <th class="p-2.5">Tanggal</th>
                                <th class="p-2.5">Kode Batch</th>
                                <th class="p-2.5">Tipe</th>
                                <th class="p-2.5">Jumlah</th>
                                <th class="p-2.5">Saldo Berjalan</th>
                                <th class="p-2.5">Keterangan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($stockCard as $row)
                                <tr class="border-b">
                                    <td class="p-2.5">{{ $row->transaction_date }}</td>
                                    <td class="p-2.5 font-mono text-xs">{{ $row->batch->batch_code ?? '-' }}</td>
                                    <td class="p-2.5">
                                        <span class="px-2 py-0.5 rounded text-xs font-bold {{ $row->type === 'IN' ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700' }}">
                                            {{ $row->type }}
                                        </span>
                                    </td>
                                    <td class="p-2.5">{{ $row->type === 'IN' ? '+' : '-' }}{{ $row->qty }}</td>
                                    <td class="p-2.5 font-bold">{{ $row->running_balance }}</td>
                                    <td class="p-2.5 text-slate-600">{{ $row->note }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="p-3 text-center text-slate-500">Belum ada mutasi transaksi.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        {{-- Kelola Master Data (Produk, Kategori, Pemasok) --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            {{-- Tambah Produk Baru --}}
            <div class="bg-white p-5 rounded-lg shadow-sm">
                <h3 class="font-bold mb-3">Tambah Produk Baru</h3>
                <form action="{{ route('web.products.store') }}" method="POST" class="space-y-3 text-sm">
                    @csrf
                    <select name="category_id" class="w-full border rounded px-3 py-2" required>
                        @foreach($categories as $c)
                            <option value="{{ $c->id }}">{{ $c->name }}</option>
                        @endforeach
                    </select>
                    <input type="text" name="sku" placeholder="SKU Unik (cth: SMB-002)" class="w-full border rounded px-3 py-2" required>
                    <input type="text" name="name" placeholder="Nama Produk" class="w-full border rounded px-3 py-2" required>
                    <input type="number" name="min_stock" placeholder="Stok Minimum" value="5" min="0" class="w-full border rounded px-3 py-2" required>
                    <button type="submit" class="bg-slate-800 hover:bg-slate-900 text-white px-4 py-2 rounded">Simpan Produk</button>
                </form>
            </div>

            {{-- Kelola Kategori --}}
            <div class="bg-white p-5 rounded-lg shadow-sm">
                <h3 class="font-bold mb-3">Kelola Kategori</h3>
                <form action="{{ route('web.categories.store') }}" method="POST" class="flex gap-2 mb-4 text-sm">
                    @csrf
                    <input type="text" name="name" placeholder="Nama Kategori Baru" class="w-full border rounded px-3 py-2" required>
                    <button type="submit" class="bg-slate-800 hover:bg-slate-900 text-white px-3 py-2 rounded shrink-0">Tambah</button>
                </form>
                <ul class="divide-y text-sm max-h-48 overflow-y-auto">
                    @foreach($categories as $c)
                        <li class="py-2 flex justify-between items-center">
                            <span>{{ $c->name }}</span>
                            <div class="flex items-center gap-2">
                                <button type="button" onclick="openEditCategoryModal({{ $c->id }}, '{{ addslashes($c->name) }}')" class="text-amber-600 hover:underline text-xs">Edit</button>
                                <span class="text-slate-300">|</span>
                                <form action="{{ route('web.categories.destroy', $c->id) }}" method="POST" onsubmit="return confirm('Hapus kategori ini?')" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-rose-600 hover:underline text-xs">Hapus</button>
                                </form>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>

            {{-- Kelola Pemasok --}}
            <div class="bg-white p-5 rounded-lg shadow-sm">
                <h3 class="font-bold mb-3">Kelola Pemasok</h3>
                <form action="{{ route('web.suppliers.store') }}" method="POST" class="space-y-2 mb-4 text-sm">
                    @csrf
                    <input type="text" name="name" placeholder="Nama Pemasok" class="w-full border rounded px-3 py-2" required>
                    <div class="flex gap-2">
                        <input type="text" name="phone" placeholder="No. Telepon" class="w-full border rounded px-3 py-2">
                        <button type="submit" class="bg-slate-800 hover:bg-slate-900 text-white px-3 py-2 rounded shrink-0">Tambah</button>
                    </div>
                </form>
                <ul class="divide-y text-sm max-h-40 overflow-y-auto">
                    @foreach($suppliers as $s)
                        <li class="py-2 flex justify-between items-center">
                            <div>
                                <div class="font-medium">{{ $s->name }}</div>
                                <div class="text-xs text-slate-500">{{ $s->phone ?? '-' }}</div>
                            </div>
                            <div class="flex items-center gap-2">
                                <button type="button" onclick="openEditSupplierModal({{ $s->id }}, '{{ addslashes($s->name) }}', '{{ addslashes($s->phone ?? '') }}')" class="text-amber-600 hover:underline text-xs">Edit</button>
                                <span class="text-slate-300">|</span>
                                <form action="{{ route('web.suppliers.destroy', $s->id) }}" method="POST" onsubmit="return confirm('Hapus pemasok ini?')" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-rose-600 hover:underline text-xs">Hapus</button>
                                </form>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </main>

    {{-- Modal Edit Produk --}}
    <div id="editProductModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 w-full max-w-md shadow-lg">
            <h3 class="font-bold text-lg mb-4">Edit Data Produk</h3>
            <form id="editProductForm" method="POST" class="space-y-3 text-sm">
                @csrf
                @method('PUT')
                <div>
                    <label class="block font-medium mb-1">Kategori</label>
                    <select id="edit_product_category_id" name="category_id" class="w-full border rounded px-3 py-2" required>
                        @foreach($categories as $c)
                            <option value="{{ $c->id }}">{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block font-medium mb-1">SKU</label>
                    <input type="text" id="edit_product_sku" name="sku" class="w-full border rounded px-3 py-2" required>
                </div>
                <div>
                    <label class="block font-medium mb-1">Nama Produk</label>
                    <input type="text" id="edit_product_name" name="name" class="w-full border rounded px-3 py-2" required>
                </div>
                <div>
                    <label class="block font-medium mb-1">Stok Minimum</label>
                    <input type="number" id="edit_product_min_stock" name="min_stock" min="0" class="w-full border rounded px-3 py-2" required>
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" onclick="closeModal('editProductModal')" class="px-4 py-2 border rounded text-slate-600">Batal</button>
                    <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded font-medium">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal Edit Kategori --}}
    <div id="editCategoryModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 w-full max-w-sm shadow-lg">
            <h3 class="font-bold text-lg mb-4">Edit Kategori</h3>
            <form id="editCategoryForm" method="POST" class="space-y-3 text-sm">
                @csrf
                @method('PUT')
                <div>
                    <label class="block font-medium mb-1">Nama Kategori</label>
                    <input type="text" id="edit_category_name" name="name" class="w-full border rounded px-3 py-2" required>
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" onclick="closeModal('editCategoryModal')" class="px-4 py-2 border rounded text-slate-600">Batal</button>
                    <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded font-medium">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal Edit Pemasok --}}
    <div id="editSupplierModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 w-full max-w-sm shadow-lg">
            <h3 class="font-bold text-lg mb-4">Edit Pemasok</h3>
            <form id="editSupplierForm" method="POST" class="space-y-3 text-sm">
                @csrf
                @method('PUT')
                <div>
                    <label class="block font-medium mb-1">Nama Pemasok</label>
                    <input type="text" id="edit_supplier_name" name="name" class="w-full border rounded px-3 py-2" required>
                </div>
                <div>
                    <label class="block font-medium mb-1">No. Telepon</label>
                    <input type="text" id="edit_supplier_phone" name="phone" class="w-full border rounded px-3 py-2">
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" onclick="closeModal('editSupplierModal')" class="px-4 py-2 border rounded text-slate-600">Batal</button>
                    <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded font-medium">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openModal(id) {
            const modal = document.getElementById(id);
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function closeModal(id) {
            const modal = document.getElementById(id);
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        function openEditProductModal(id, categoryId, sku, name, minStock) {
            document.getElementById('editProductForm').action = '/web/products/' + id;
            document.getElementById('edit_product_category_id').value = categoryId;
            document.getElementById('edit_product_sku').value = sku;
            document.getElementById('edit_product_name').value = name;
            document.getElementById('edit_product_min_stock').value = minStock;
            openModal('editProductModal');
        }

        function openEditCategoryModal(id, name) {
            document.getElementById('editCategoryForm').action = '/web/categories/' + id;
            document.getElementById('edit_category_name').value = name;
            openModal('editCategoryModal');
        }

        function openEditSupplierModal(id, name, phone) {
            document.getElementById('editSupplierForm').action = '/web/suppliers/' + id;
            document.getElementById('edit_supplier_name').value = name;
            document.getElementById('edit_supplier_phone').value = phone;
            openModal('editSupplierModal');
        }
    </script>
</body>
</html>