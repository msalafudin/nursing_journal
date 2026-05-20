@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold">Manajemen Unit</h1>
        <a href="{{ route('units.create') }}" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
            Tambah Unit
        </a>
    </div>

    @if (session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            {{ session('error') }}
        </div>
    @endif

    <div class="overflow-x-auto bg-white rounded-lg shadow">
        <table class="min-w-full">
            <thead class="bg-gray-100 border-b">
                <tr>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Nama Unit</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Status</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($units as $unit)
                    <tr class="border-b hover:bg-gray-50">
                        <td class="px-6 py-4 text-sm text-gray-900">{{ $unit->name }}</td>
                        <td class="px-6 py-4 text-sm">
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium {{ $unit->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                {{ $unit->status === 'active' ? 'Aktif' : 'Nonaktif' }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm space-x-2">
                            <a href="{{ route('units.edit', $unit) }}" class="text-blue-600 hover:text-blue-900 font-medium">
                                Edit
                            </a>
                            <button type="button" onclick="showDeleteConfirm({{ $unit->id }}, '{{ $unit->name }}')" class="text-red-600 hover:text-red-900 font-medium">
                                Hapus
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="px-6 py-4 text-center text-gray-500">
                            Tidak ada unit terdaftar.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-lg p-6 max-w-md w-full mx-4">
        <h2 class="text-xl font-bold mb-4">Konfirmasi Penghapusan</h2>
        <p id="deleteMessage" class="text-gray-700 mb-6"></p>
        <div id="warningMessage" class="bg-yellow-50 border border-yellow-200 text-yellow-800 px-4 py-3 rounded mb-6 hidden">
            <p class="font-semibold mb-2">Peringatan:</p>
            <p id="warningText"></p>
        </div>
        <div class="flex justify-end space-x-3">
            <button type="button" onclick="closeDeleteModal()" class="px-4 py-2 text-gray-700 bg-gray-200 hover:bg-gray-300 rounded font-medium">
                Batal
            </button>
            <button type="button" onclick="confirmDelete()" class="px-4 py-2 text-white bg-red-600 hover:bg-red-700 rounded font-medium">
                Hapus
            </button>
        </div>
    </div>
</div>

<script>
let deleteUnitId = null;

function showDeleteConfirm(unitId, unitName) {
    deleteUnitId = unitId;
    
    // Fetch related data count
    fetch(`/units/${unitId}/delete-confirm`)
        .then(response => response.json())
        .then(data => {
            document.getElementById('deleteMessage').textContent = `Apakah Anda yakin ingin menghapus unit "${unitName}"?`;
            
            if (data.related_data_count > 0) {
                document.getElementById('warningMessage').classList.remove('hidden');
                document.getElementById('warningText').textContent = 
                    `Unit ini memiliki ${data.related_data_count} data pasien terkait. Data ini akan dihapus jika Anda melanjutkan.`;
            } else {
                document.getElementById('warningMessage').classList.add('hidden');
            }
            
            document.getElementById('deleteModal').classList.remove('hidden');
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Terjadi kesalahan saat memproses permintaan.');
        });
}

function closeDeleteModal() {
    document.getElementById('deleteModal').classList.add('hidden');
    deleteUnitId = null;
}

function confirmDelete() {
    if (!deleteUnitId) return;
    
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = `/units/${deleteUnitId}`;
    
    const csrfToken = document.querySelector('meta[name="csrf-token"]');
    if (csrfToken) {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = '_token';
        input.value = csrfToken.content;
        form.appendChild(input);
    }
    
    const methodInput = document.createElement('input');
    methodInput.type = 'hidden';
    methodInput.name = '_method';
    methodInput.value = 'DELETE';
    form.appendChild(methodInput);
    
    const confirmedInput = document.createElement('input');
    confirmedInput.type = 'hidden';
    confirmedInput.name = 'confirmed';
    confirmedInput.value = '1';
    form.appendChild(confirmedInput);
    
    document.body.appendChild(form);
    form.submit();
}

// Close modal when clicking outside
document.getElementById('deleteModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closeDeleteModal();
    }
});
</script>
@endsection
