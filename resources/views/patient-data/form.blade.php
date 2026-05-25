@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gray-50 py-6 px-4 sm:px-6 lg:px-8">
    <div class="max-w-2xl mx-auto">
        <h1 class="text-2xl sm:text-3xl font-bold mb-6 text-gray-900">Input Data Pasien</h1>

        <!-- Unit and Shift Information -->
        <div class="bg-white border-l-4 border-indigo-600 p-4 sm:p-6 rounded-lg mb-6 shadow-sm">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <p class="text-sm font-medium text-gray-500 uppercase">Unit</p>
                    <p class="text-lg sm:text-xl font-semibold text-gray-900 mt-1">{{ $unit->name }}</p>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-500 uppercase">Shift Aktif</p>
                    <p class="text-lg sm:text-xl font-semibold text-gray-900 mt-1">{{ $currentShift }}</p>
                </div>
            </div>
        </div>

        <!-- Text Output Display (shown after successful save) -->
        <div id="textOutputContainer" class="hidden mb-6 p-4 sm:p-6 bg-gray-50 border border-gray-300 rounded-lg">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-3 gap-3">
                <h3 class="text-lg font-semibold text-gray-900">Output Data</h3>
                <button type="button" id="copyButton" class="w-full sm:w-auto tap-target btn btn-primary">
                    Salin ke Clipboard
                </button>
            </div>
            <textarea id="textOutput" class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-white text-sm" rows="8" readonly></textarea>
            <p id="copyFeedback" class="text-green-600 text-sm mt-2 hidden">✓ Berhasil disalin ke clipboard</p>
        </div>

        <!-- Patient Data Form -->
        <form id="patientDataForm" class="space-y-6" novalidate>
            @csrf

            <!-- Shift Selection -->
            <div>
                <label for="shift" class="form-label">
                    Pilih Shift <span class="text-red-500">*</span>
                </label>
                <select id="shift" name="shift" class="form-input" required>
                    <option value="">-- Pilih Shift --</option>
                    @foreach($availableShifts as $shift)
                        <option value="{{ $shift }}" @selected($shift === $currentShift)>
                            {{ $shift }}
                        </option>
                    @endforeach
                </select>
                <p id="shift-error" class="form-error hidden"></p>
                @error('shift')
                    <p class="form-error">{{ $message }}</p>
                @enderror
            </div>

            <!-- Date Selection -->
            <div>
                <label for="date" class="form-label">
                    Tanggal <span class="text-red-500">*</span>
                </label>
                <input type="date" id="date" name="date" class="form-input" value="{{ date('Y-m-d') }}" required>
                <p id="date-error" class="form-error hidden"></p>
                @error('date')
                    <p class="form-error">{{ $message }}</p>
                @enderror
            </div>

            <!-- Unit-Specific Fields -->
            <div class="space-y-4">
                <h2 class="text-xl font-semibold text-gray-900">Data Pasien</h2>
                <div class="form-single-column">
                @foreach($fields as $field)
                        @if($field['type'] === 'numeric' && !isset($field['auto_calculated']))
                            <div>
                                <label for="{{ $field['key'] }}" class="form-label">
                                    {{ $field['name'] }}
                                    @if($field['required'])
                                        <span class="text-red-500">*</span>
                                    @endif
                                </label>
                                <input 
                                    type="number" 
                                    id="{{ $field['key'] }}" 
                                    name="{{ $field['key'] }}" 
                                    class="form-input numeric-field tap-target"
                                    min="{{ $field['min'] ?? 0 }}"
                                    max="{{ $field['max'] ?? 9999 }}"
                                    data-required="{{ $field['required'] ? 'true' : 'false' }}"
                                    data-field-name="{{ $field['name'] }}"
                                    @required($field['required'])
                                >
                                <p id="{{ $field['key'] }}-error" class="form-error hidden"></p>
                                @error($field['key'])
                                    <p class="form-error">{{ $message }}</p>
                                @enderror
                            </div>
                        @elseif($field['type'] === 'text' && !isset($field['auto_calculated']))
                            <div>
                                <label for="{{ $field['key'] }}" class="form-label">
                                    {{ $field['name'] }}
                                    @if($field['required'])
                                        <span class="text-red-500">*</span>
                                    @endif
                                </label>
                                <textarea 
                                    id="{{ $field['key'] }}" 
                                    name="{{ $field['key'] }}" 
                                    class="form-input text-field tap-target"
                                    rows="3"
                                    data-required="{{ $field['required'] ? 'true' : 'false' }}"
                                    data-field-name="{{ $field['name'] }}"
                                    @required($field['required'])
                                ></textarea>
                                <p id="{{ $field['key'] }}-error" class="form-error hidden"></p>
                                @error($field['key'])
                                    <p class="form-error">{{ $message }}</p>
                                @enderror
                            </div>
                        @elseif(isset($field['auto_calculated']))
                            <div>
                                <label for="{{ $field['key'] }}" class="form-label">
                                    {{ $field['name'] }} (Otomatis)
                                </label>
                                <input 
                                    type="number" 
                                    id="{{ $field['key'] }}" 
                                    name="{{ $field['key'] }}" 
                                    class="form-input tap-target"
                                    data-calculation="{{ $field['calculation'] }}"
                                    readonly
                                >
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>

            <!-- Form Actions -->
            <div class="flex flex-col sm:flex-row gap-3 sm:gap-4">
                <button type="submit" class="flex-1 tap-target btn btn-primary">
                    Simpan Data
                </button>
                <button type="reset" class="flex-1 tap-target btn btn-secondary">
                    Bersihkan
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Duplicate Confirmation Modal -->
<div id="duplicateModal" class="fixed inset-0 z-[70] hidden">
    <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" id="duplicateModalOverlay"></div>
    <div class="fixed inset-0 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-xl max-w-md w-full p-6 relative">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 rounded-full bg-amber-100 flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-midnight">Data Duplikat</h3>
            </div>
            <p id="duplicateMessage" class="text-body text-cloud mb-6">Data untuk tanggal, shift, dan unit ini sudah ada. Apakah Anda ingin mengganti data yang lama?</p>
            <div class="flex gap-3">
                <button type="button" id="duplicateCancelBtn" class="flex-1 tap-target btn btn-secondary">
                    Batal
                </button>
                <button type="button" id="duplicateReplaceBtn" class="flex-1 tap-target btn btn-primary bg-amber-600 hover:bg-amber-700 border-amber-600 hover:border-amber-700">
                    Ganti Data
                </button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('patientDataForm');
    const numericFields = document.querySelectorAll('.numeric-field');
    const textFields = document.querySelectorAll('.text-field');
    const autoCalculatedFields = document.querySelectorAll('[data-calculation]');

    // Duplicate modal elements
    const duplicateModal = document.getElementById('duplicateModal');
    const duplicateModalOverlay = document.getElementById('duplicateModalOverlay');
    const duplicateCancelBtn = document.getElementById('duplicateCancelBtn');
    const duplicateReplaceBtn = document.getElementById('duplicateReplaceBtn');
    const duplicateMessage = document.getElementById('duplicateMessage');
    let pendingExistingId = null;

    // Function to show notification
    function showNotification(message, type = 'success') {
        if (window.notificationManager) {
            window.notificationManager.show(message, type);
        }
    }

    // Show duplicate modal
    function showDuplicateModal(message, existingId) {
        duplicateMessage.textContent = message;
        pendingExistingId = existingId;
        duplicateModal.classList.remove('hidden');
    }

    // Hide duplicate modal
    function hideDuplicateModal() {
        duplicateModal.classList.add('hidden');
        pendingExistingId = null;
    }

    // Cancel button
    duplicateCancelBtn.addEventListener('click', hideDuplicateModal);
    duplicateModalOverlay.addEventListener('click', hideDuplicateModal);

    // Replace button - send PUT request to update existing record
    duplicateReplaceBtn.addEventListener('click', function() {
        if (!pendingExistingId) return;

        const formData = new FormData(form);

        fetch(`/patient-data/${pendingExistingId}`, {
            method: 'PUT',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(Object.fromEntries(formData)),
        })
        .then(response => {
            if (!response.ok) {
                return response.json().then(data => {
                    if (data.errors) {
                        Object.keys(data.errors).forEach(key => {
                            const errorElement = document.getElementById(key + '-error');
                            if (errorElement) {
                                errorElement.textContent = data.errors[key][0];
                                errorElement.classList.remove('hidden');
                            }
                        });
                    }
                    showNotification(data.message || 'Gagal memperbarui data', 'error');
                    throw new Error('Update failed');
                });
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                showNotification(data.message, 'success');

                // Display text output
                const textOutputContainer = document.getElementById('textOutputContainer');
                const textOutput = document.getElementById('textOutput');
                textOutput.value = data.text_output;
                textOutputContainer.classList.remove('hidden');

                // Scroll to and focus the text output
                textOutputContainer.scrollIntoView({ behavior: 'smooth', block: 'center' });
                setTimeout(() => { textOutput.focus(); textOutput.select(); }, 400);

                form.reset();
                document.querySelectorAll('[id$="-error"]').forEach(el => el.classList.add('hidden'));
                document.querySelectorAll('.numeric-field, .text-field').forEach(field => field.classList.remove('border-red-500'));
                autoCalculatedFields.forEach(field => { field.value = ''; });
            }
        })
        .catch(error => {
            console.error('Error:', error);
        })
        .finally(() => {
            hideDuplicateModal();
        });
    });

    // Function to validate numeric field
    function validateNumericField(field) {
        const value = field.value.trim();
        const errorElement = document.getElementById(field.id + '-error');
        const isRequired = field.dataset.required === 'true';
        const fieldName = field.dataset.fieldName;

        // Clear previous error
        errorElement.classList.add('hidden');
        field.classList.remove('border-red-500');

        if (isRequired && value === '') {
            errorElement.textContent = `${fieldName} harus diisi`;
            errorElement.classList.remove('hidden');
            field.classList.add('border-red-500');
            return false;
        }

        if (value !== '') {
            const numValue = parseInt(value, 10);
            if (isNaN(numValue) || numValue < 0 || numValue > 9999) {
                errorElement.textContent = `${fieldName} harus berupa angka antara 0-9999`;
                errorElement.classList.remove('hidden');
                field.classList.add('border-red-500');
                return false;
            }
        }

        return true;
    }

    // Function to validate text field
    function validateTextField(field) {
        const value = field.value.trim();
        const errorElement = document.getElementById(field.id + '-error');
        const isRequired = field.dataset.required === 'true';
        const fieldName = field.dataset.fieldName;

        // Clear previous error
        errorElement.classList.add('hidden');
        field.classList.remove('border-red-500');

        if (isRequired && value === '') {
            errorElement.textContent = `${fieldName} harus diisi`;
            errorElement.classList.remove('hidden');
            field.classList.add('border-red-500');
            return false;
        }

        return true;
    }

    // Function to calculate totals
    function calculateTotals() {
        autoCalculatedFields.forEach(field => {
            const calculation = field.dataset.calculation;
            const fieldKeys = calculation.match(/[a-z_]+/g);
            let total = 0;
            let allFieldsValid = true;

            fieldKeys.forEach(key => {
                const inputField = document.getElementById(key);
                if (inputField) {
                    const value = inputField.value.trim();
                    if (value === '') {
                        allFieldsValid = false;
                    } else {
                        const numValue = parseInt(value, 10);
                        if (!isNaN(numValue) && numValue >= 0 && numValue <= 9999) {
                            total += numValue;
                        } else {
                            allFieldsValid = false;
                        }
                    }
                }
            });

            if (allFieldsValid) {
                field.value = total;
            } else {
                field.value = '';
            }
        });
    }

    // Add event listeners to numeric fields for real-time validation and calculation
    numericFields.forEach(field => {
        field.addEventListener('blur', function() {
            validateNumericField(this);
            calculateTotals();
        });

        field.addEventListener('input', function() {
            calculateTotals();
        });
    });

    // Add event listeners to text fields for real-time validation
    textFields.forEach(field => {
        field.addEventListener('blur', function() {
            validateTextField(this);
        });
    });

    // Form submission validation
    form.addEventListener('submit', function(e) {
        e.preventDefault();

        let isValid = true;

        // Validate shift
        const shiftField = document.getElementById('shift');
        const shiftError = document.getElementById('shift-error');
        if (shiftField.value === '') {
            shiftError.textContent = 'Shift harus dipilih';
            shiftError.classList.remove('hidden');
            isValid = false;
        } else {
            shiftError.classList.add('hidden');
        }

        // Validate date
        const dateField = document.getElementById('date');
        const dateError = document.getElementById('date-error');
        if (dateField.value === '') {
            dateError.textContent = 'Tanggal harus diisi';
            dateError.classList.remove('hidden');
            isValid = false;
        } else {
            dateError.classList.add('hidden');
        }

        // Validate all numeric fields
        numericFields.forEach(field => {
            if (!validateNumericField(field)) {
                isValid = false;
            }
        });

        // Validate all text fields
        textFields.forEach(field => {
            if (!validateTextField(field)) {
                isValid = false;
            }
        });

        if (isValid) {
            // Submit form via AJAX
            const formData = new FormData(form);
            
            fetch('/patient-data/store', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
                    'Accept': 'application/json',
                },
                body: formData,
            })
            .then(response => {
                if (response.status === 409) {
                    // Duplicate entry - show confirmation modal
                    return response.json().then(data => {
                        showDuplicateModal(data.message, data.existing_id);
                        throw new Error('duplicate');
                    });
                }
                if (!response.ok) {
                    return response.json().then(data => {
                        // Handle validation errors
                        if (data.errors) {
                            Object.keys(data.errors).forEach(key => {
                                const errorElement = document.getElementById(key + '-error');
                                if (errorElement) {
                                    errorElement.textContent = data.errors[key][0];
                                    errorElement.classList.remove('hidden');
                                    const field = document.getElementById(key);
                                    if (field) {
                                        field.classList.add('border-red-500');
                                    }
                                }
                            });
                        }
                        throw new Error('Validation failed');
                    });
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    showNotification(data.message, 'success');
                    
                    // Display text output
                    const textOutputContainer = document.getElementById('textOutputContainer');
                    const textOutput = document.getElementById('textOutput');
                    textOutput.value = data.text_output;
                    textOutputContainer.classList.remove('hidden');
                    
                    // Scroll to and focus the text output
                    textOutputContainer.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    setTimeout(() => { textOutput.focus(); textOutput.select(); }, 400);

                    form.reset();
                    
                    // Clear all error messages
                    document.querySelectorAll('[id$="-error"]').forEach(el => {
                        el.classList.add('hidden');
                    });

                    // Remove error styling
                    document.querySelectorAll('.numeric-field, .text-field').forEach(field => {
                        field.classList.remove('border-red-500');
                    });

                    // Clear auto-calculated fields
                    autoCalculatedFields.forEach(field => {
                        field.value = '';
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
        }
    });

    // Reset form handler
    form.addEventListener('reset', function() {
        // Clear all error messages
        document.querySelectorAll('[id$="-error"]').forEach(el => {
            el.classList.add('hidden');
        });

        // Remove error styling
        document.querySelectorAll('.numeric-field, .text-field').forEach(field => {
            field.classList.remove('border-red-500');
        });

        // Clear auto-calculated fields
        autoCalculatedFields.forEach(field => {
            field.value = '';
        });
    });

    // Copy to clipboard button handler
    const copyButton = document.getElementById('copyButton');
    const copyFeedback = document.getElementById('copyFeedback');
    
    if (copyButton) {
        copyButton.addEventListener('click', function() {
            const textOutput = document.getElementById('textOutput');
            
            // Copy text to clipboard
            textOutput.select();
            document.execCommand('copy');
            
            // Show feedback
            copyFeedback.classList.remove('hidden');
            
            // Hide feedback after 2 seconds
            setTimeout(() => {
                copyFeedback.classList.add('hidden');
            }, 2000);
        });
    }
});
</script>
@endsection
