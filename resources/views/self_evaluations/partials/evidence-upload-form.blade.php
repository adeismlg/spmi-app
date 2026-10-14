{{-- resources/views/self_evaluations/partials/evidence-upload-form.blade.php --}}
<div class="modal fade" id="evidenceUploadModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title">Unggah Bukti Kegiatan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="evidenceForm" method="POST" enctype="multipart/form-data" class="needs-validation">
                @csrf
                <div class="modal-body">
                    <div class="alert alert-info mb-3">
                        <strong>💡 Tips:</strong> Anda bisa mengunggah hingga 10 file sekaligus dengan memberikan deskripsi untuk masing-masing.
                    </div>

                    <!-- File Upload Area -->
                    <div class="mb-4">
                        <label class="form-label fw-semibold">Pilih File</label>
                        <div class="upload-zone border-2 border-dashed rounded p-4 text-center" 
                             id="uploadZone"
                             style="cursor: pointer; background-color: #f8f9fa; border-color: #dee2e6;">
                            <input type="file" id="fileInput" name="files[]" multiple hidden 
                                   accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.jpg,.jpeg,.png,.txt,.zip,.rar">
                            <div class="py-3">
                                <i class="bi bi-cloud-arrow-up" style="font-size: 2rem; color: #0d6efd;"></i>
                                <div class="mt-2">
                                    <strong>Klik atau seret file di sini</strong>
                                    <div class="small text-muted">PDF, Word, Excel, PowerPoint, Gambar, Txt, Zip (Max 10MB per file)</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- File List with Metadata -->
                    <div id="fileListContainer" class="mb-3" style="display: none;">
                        <label class="form-label fw-semibold">File yang Diupload</label>
                        <div id="fileList" class="d-flex flex-column gap-3"></div>
                    </div>

                    <!-- Alternative: Single File Upload (simpler format) -->
                    <div class="mt-4 pt-3 border-top">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="singleFileMode">
                            <label class="form-check-label" for="singleFileMode">
                                Upload file satu persatu (format sederhana)
                            </label>
                        </div>
                        
                        <div id="singleFileForm" style="display: none;" class="mt-3">
                            <div class="mb-3">
                                <label for="singleFileJudul" class="form-label">Nama/Judul Bukti</label>
                                <input type="text" class="form-control" id="singleFileJudul" name="judul" 
                                       placeholder="e.g., Laporan Kegiatan Semester 1" maxlength="255">
                            </div>

                            <div class="mb-3">
                                <label for="singleFileType" class="form-label">Jenis Dokumen</label>
                                <select class="form-select" id="singleFileType" name="document_type">
                                    <option value="laporan_akhir">📄 Laporan Akhir</option>
                                    <option value="struktur_organisasi">🏢 Struktur Organisasi</option>
                                    <option value="proker_kerja">📋 Program Kerja</option>
                                    <option value="data_statistik">📊 Data Statistik</option>
                                    <option value="dokumen_pendukung" selected>📑 Dokumen Pendukung</option>
                                    <option value="photo_evidence">📷 Bukti Foto</option>
                                    <option value="video_evidence">🎥 Bukti Video</option>
                                    <option value="catatan_bukti">📝 Catatan/Bukti Kegiatan</option>
                                    <option value="surat_pernyataan">✍️ Surat Pernyataan</option>
                                    <option value="lainnya">📦 Lainnya</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="singleFileKeterangan" class="form-label">Keterangan</label>
                                <textarea class="form-control" id="singleFileKeterangan" name="keterangan" 
                                          rows="2" placeholder="Jelaskan isi atau konteks dokumen ini..." maxlength="1000"></textarea>
                            </div>

                            <div class="mb-3">
                                <label for="singleFileInput" class="form-label">Pilih File atau Tautan URL</label>
                                <input type="file" class="form-control" id="singleFileInput" name="berkas"
                                       accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.jpg,.jpeg,.png,.txt,.zip,.rar">
                            </div>

                            <div class="mb-3">
                                <label for="singleFileUrl" class="form-label">Atau berikan Tautan URL</label>
                                <input type="url" class="form-control" id="singleFileUrl" name="url"
                                       placeholder="https://example.com/dokumen">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-cloud-arrow-up"></i> Upload
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    .upload-zone:hover {
        background-color: #e7f1ff !important;
        border-color: #0d6efd !important;
    }
    
    .upload-zone.dragover {
        background-color: #e7f1ff !important;
        border-color: #0d6efd !important;
    }
    
    .file-item {
        background: #f8f9fa;
        border: 1px solid #dee2e6;
        border-radius: 0.375rem;
        padding: 1rem;
    }
    
    .file-item-remove {
        cursor: pointer;
        opacity: 0.6;
        transition: opacity 0.2s;
    }
    
    .file-item-remove:hover {
        opacity: 1;
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const uploadZone = document.getElementById('uploadZone');
    const fileInput = document.getElementById('fileInput');
    const fileList = document.getElementById('fileList');
    const fileListContainer = document.getElementById('fileListContainer');
    const singleFileMode = document.getElementById('singleFileMode');
    const singleFileForm = document.getElementById('singleFileForm');
    const evidenceForm = document.getElementById('evidenceForm');
    
    let selectedFiles = [];

    // Upload zone drag and drop
    uploadZone.addEventListener('click', () => fileInput.click());
    
    uploadZone.addEventListener('dragover', (e) => {
        e.preventDefault();
        uploadZone.classList.add('dragover');
    });
    
    uploadZone.addEventListener('dragleave', () => {
        uploadZone.classList.remove('dragover');
    });
    
    uploadZone.addEventListener('drop', (e) => {
        e.preventDefault();
        uploadZone.classList.remove('dragover');
        handleFiles(e.dataTransfer.files);
    });

    fileInput.addEventListener('change', (e) => {
        handleFiles(e.target.files);
    });

    function handleFiles(files) {
        selectedFiles = Array.from(files);
        renderFileList();
    }

    function renderFileList() {
        fileList.innerHTML = '';
        
        if (selectedFiles.length === 0) {
            fileListContainer.style.display = 'none';
            return;
        }
        
        fileListContainer.style.display = 'block';
        
        selectedFiles.forEach((file, index) => {
            const docTypes = {
                'laporan_akhir': '📄 Laporan Akhir',
                'struktur_organisasi': '🏢 Struktur Organisasi',
                'proker_kerja': '📋 Program Kerja',
                'data_statistik': '📊 Data Statistik',
                'dokumen_pendukung': '📑 Dokumen Pendukung',
                'photo_evidence': '📷 Bukti Foto',
                'video_evidence': '🎥 Bukti Video',
                'catatan_bukti': '📝 Catatan/Bukti Kegiatan',
                'surat_pernyataan': '✍️ Surat Pernyataan',
                'lainnya': '📦 Lainnya'
            };
            
            const item = document.createElement('div');
            item.className = 'file-item';
            item.innerHTML = `
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div>
                        <div class="fw-semibold text-truncate" title="${file.name}">
                            <i class="bi bi-file"></i> ${file.name}
                        </div>
                        <small class="text-muted">${(file.size / 1024).toFixed(1)} KB</small>
                    </div>
                    <button type="button" class="file-item-remove btn btn-sm btn-link text-danger p-0" 
                            onclick="removeFile(${index})">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
                
                <div class="row g-2">
                    <div class="col-md-6">
                        <input type="text" name="file_metadata[${index}][judul]" class="form-control form-control-sm"
                               placeholder="Nama/Judul bukti" value="${file.name}" required>
                    </div>
                    <div class="col-md-6">
                        <select name="file_metadata[${index}][document_type]" class="form-select form-select-sm" required>
                            ${Object.entries(docTypes).map(([key, label]) => 
                                `<option value="${key}" ${key === 'dokumen_pendukung' ? 'selected' : ''}>${label}</option>`
                            ).join('')}
                        </select>
                    </div>
                </div>
                
                <div class="mt-2">
                    <textarea name="file_metadata[${index}][keterangan]" class="form-control form-control-sm"
                              placeholder="Keterangan (opsional)" rows="2" maxlength="1000"></textarea>
                </div>
            `;
            fileList.appendChild(item);
        });
    }

    window.removeFile = function(index) {
        selectedFiles.splice(index, 1);
        renderFileList();
    };

    // Single file mode toggle
    singleFileMode.addEventListener('change', () => {
        if (singleFileMode.checked) {
            uploadZone.style.display = 'none';
            fileListContainer.style.display = 'none';
            singleFileForm.style.display = 'block';
            fileInput.removeAttribute('name'); // Don't submit multi-file input
        } else {
            uploadZone.style.display = 'block';
            singleFileForm.style.display = 'none';
            fileInput.setAttribute('name', 'files[]');
        }
    });

    // Form submission
    evidenceForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Create FormData to handle file uploads
        const formData = new FormData(this);
        
        // Add selected files to FormData
        if (!singleFileMode.checked && selectedFiles.length > 0) {
            // Clear files array and add selected ones
            formData.delete('files[]');
            selectedFiles.forEach(file => {
                formData.append('files[]', file);
            });
        }
        
        // Get form action URL
        const action = this.getAttribute('action');
        
        // Submit form
        fetch(action, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success || data.message) {
                // Close modal
                bootstrap.Modal.getInstance(document.getElementById('evidenceUploadModal')).hide();
                // Reset form
                evidenceForm.reset();
                selectedFiles = [];
                renderFileList();
                // Reload page or update UI
                location.reload();
            } else if (data.errors) {
                alert('Error: ' + Object.values(data.errors).flat().join('\n'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Terjadi kesalahan saat upload. Silakan coba lagi.');
        });
    });
});
</script>
