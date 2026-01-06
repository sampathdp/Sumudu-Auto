<?php
require_once __DIR__ . '/../../classes/Includes.php';
requirePagePermission('Create');

$companyId = $_SESSION['company_id'] ?? 1;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Import Inventory</title>
    <?php include '../../includes/main-css.php'; ?>
    <style>
        :root {
            --primary-color: #4f46e5;
            --primary-hover: #4338ca;
            --success-color: #059669;
            --warning-color: #d97706;
            --danger-color: #dc2626;
            --border-color: #e2e8f0;
            --bg-light: #f8fafc;
            --text-dark: #1e293b;
            --text-muted: #64748b;
        }

        body { background-color: var(--bg-light); }
        .content { margin-top: 70px; padding: 1.5rem; max-width: 1200px; margin-left: auto; margin-right: auto; }

        .page-header {
            background: linear-gradient(135deg, var(--primary-color), #7c3aed);
            border-radius: 12px;
            padding: 2rem;
            color: white;
            margin-bottom: 2rem;
        }

        .page-header h1 { margin: 0; font-size: 1.5rem; font-weight: 600; }
        .page-header p { margin: 0.5rem 0 0; opacity: 0.9; }

        .card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            margin-bottom: 1.5rem;
            overflow: hidden;
        }

        .card-header {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .card-header i { font-size: 1.25rem; color: var(--primary-color); }
        .card-header h3 { margin: 0; font-size: 1.1rem; font-weight: 600; color: var(--text-dark); }

        .card-body { padding: 1.5rem; }

        .step-indicator {
            display: flex;
            justify-content: center;
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .step {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--text-muted);
            font-weight: 500;
        }

        .step.active { color: var(--primary-color); }
        .step.completed { color: var(--success-color); }

        .step-number {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--border-color);
            font-weight: 600;
        }

        .step.active .step-number { background: var(--primary-color); color: white; }
        .step.completed .step-number { background: var(--success-color); color: white; }

        .download-options {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
        }

        .download-card {
            border: 2px solid var(--border-color);
            border-radius: 12px;
            padding: 1.5rem;
            text-align: center;
            transition: all 0.2s;
            cursor: pointer;
        }

        .download-card:hover {
            border-color: var(--primary-color);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.15);
        }

        .download-card i { font-size: 3rem; margin-bottom: 1rem; }
        .download-card.csv i { color: var(--success-color); }
        .download-card.excel i { color: #217346; }
        .download-card.export i { color: var(--primary-color); }

        .download-card h4 { margin: 0 0 0.5rem; color: var(--text-dark); }
        .download-card p { margin: 0; color: var(--text-muted); font-size: 0.875rem; }

        .upload-zone {
            border: 2px dashed var(--border-color);
            border-radius: 12px;
            padding: 3rem 2rem;
            text-align: center;
            transition: all 0.2s;
            cursor: pointer;
        }

        .upload-zone:hover, .upload-zone.dragover {
            border-color: var(--primary-color);
            background: rgba(79, 70, 229, 0.05);
        }

        .upload-zone i { font-size: 4rem; color: var(--text-muted); margin-bottom: 1rem; }
        .upload-zone h4 { margin: 0 0 0.5rem; color: var(--text-dark); }
        .upload-zone p { margin: 0; color: var(--text-muted); }

        .upload-zone input[type="file"] { display: none; }

        .file-selected {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 1rem;
            padding: 1rem;
            background: #f0fdf4;
            border: 1px solid #86efac;
            border-radius: 8px;
            margin-top: 1rem;
        }

        .file-selected i { color: var(--success-color); font-size: 1.5rem; }
        .file-selected .file-info h5 { margin: 0; color: var(--success-color); }
        .file-selected .file-info p { margin: 0; color: var(--text-muted); font-size: 0.875rem; }

        .import-options {
            display: flex;
            flex-wrap: wrap;
            gap: 1.5rem;
            margin: 1.5rem 0;
        }

        .option-checkbox {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            cursor: pointer;
        }

        .option-checkbox input { width: 18px; height: 18px; accent-color: var(--primary-color); }

        .preview-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.875rem;
            margin-top: 1rem;
        }

        .preview-table th, .preview-table td {
            padding: 0.75rem;
            border: 1px solid var(--border-color);
            text-align: left;
        }

        .preview-table th { background: var(--bg-light); font-weight: 600; }

        .results-summary {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .result-stat {
            padding: 1rem;
            border-radius: 8px;
            text-align: center;
        }

        .result-stat.success { background: #f0fdf4; color: var(--success-color); }
        .result-stat.warning { background: #fffbeb; color: var(--warning-color); }
        .result-stat.danger { background: #fef2f2; color: var(--danger-color); }

        .result-stat h3 { margin: 0; font-size: 2rem; }
        .result-stat p { margin: 0.25rem 0 0; font-size: 0.875rem; }

        .error-list {
            max-height: 300px;
            overflow-y: auto;
            background: #fef2f2;
            border-radius: 8px;
            padding: 1rem;
        }

        .error-item {
            display: flex;
            gap: 0.5rem;
            padding: 0.5rem 0;
            border-bottom: 1px solid #fecaca;
        }

        .error-item:last-child { border-bottom: none; }
        .error-item .row-num { font-weight: 600; color: var(--danger-color); }

        .btn-group { display: flex; gap: 1rem; justify-content: flex-end; margin-top: 1.5rem; }

        .btn {
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.2s;
        }

        .btn-primary { background: var(--primary-color); color: white; }
        .btn-primary:hover { background: var(--primary-hover); }
        .btn-secondary { background: var(--border-color); color: var(--text-dark); }
        .btn-secondary:hover { background: #cbd5e1; }
        .btn-success { background: var(--success-color); color: white; }

        .template-info {
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            border-radius: 8px;
            padding: 1rem;
            margin-top: 1rem;
        }

        .template-info h5 { margin: 0 0 0.75rem; color: #1e40af; }
        .template-info ul { margin: 0; padding-left: 1.25rem; color: #1e40af; }
        .template-info li { margin-bottom: 0.25rem; }

        .hidden { display: none; }

        @media (max-width: 768px) {
            .step-indicator { flex-direction: column; align-items: flex-start; }
            .download-options { grid-template-columns: 1fr; }
            .results-summary { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <?php include '../../includes/sidebar.php'; ?>
    <?php include '../../includes/topbar.php'; ?>

    <div class="content">
        <div class="page-header">
            <h1><i class="fas fa-file-import me-2"></i>Import Inventory Items</h1>
            <p>Upload a CSV/Excel file to bulk import products into your inventory</p>
        </div>

        <!-- Step Indicator -->
        <div class="step-indicator">
            <div class="step active" id="step1-indicator">
                <span class="step-number">1</span>
                <span>Download Template</span>
            </div>
            <div class="step" id="step2-indicator">
                <span class="step-number">2</span>
                <span>Upload File</span>
            </div>
            <div class="step" id="step3-indicator">
                <span class="step-number">3</span>
                <span>Review & Import</span>
            </div>
        </div>

        <!-- Step 1: Download Template -->
        <div class="card" id="step1">
            <div class="card-header">
                <i class="fas fa-download"></i>
                <h3>Step 1: Download Template</h3>
            </div>
            <div class="card-body">
                <p class="text-muted mb-4">Download a template file, fill in your product data, then upload it in the next step.</p>
                
                <div class="download-options">
                    <div class="download-card csv" onclick="downloadTemplate('csv')">
                        <i class="fas fa-file-csv"></i>
                        <h4>CSV Template</h4>
                        <p>Compatible with Excel, Google Sheets, and any spreadsheet app</p>
                    </div>
                    
                    <div class="download-card excel" onclick="downloadTemplate('excel')">
                        <i class="fas fa-file-excel"></i>
                        <h4>Excel Template</h4>
                        <p>Pre-formatted Excel file with column hints</p>
                    </div>
                    
                    <div class="download-card export" onclick="exportExisting()">
                        <i class="fas fa-file-export"></i>
                        <h4>Export Current Items</h4>
                        <p>Download existing inventory as CSV for reference or backup</p>
                    </div>
                </div>

                <div class="template-info">
                    <h5><i class="fas fa-info-circle me-1"></i>Template Columns</h5>
                    <ul>
                        <li><strong>Item Code *</strong> - Unique SKU or barcode (required)</li>
                        <li><strong>Item Name *</strong> - Product name (required)</li>
                        <li><strong>Unit *</strong> - Unit of measure: pcs, liters, kg, etc. (required)</li>
                        <li><strong>Category</strong> - Category name (auto-created if doesn't exist)</li>
                        <li><strong>Cost Price / Selling Price</strong> - Prices for the item</li>
                        <li><strong>Opening Stock / Reorder Level</strong> - Stock quantities</li>
                    </ul>
                </div>

                <div class="btn-group">
                    <button class="btn btn-primary" onclick="goToStep(2)">
                        Continue to Upload <i class="fas fa-arrow-right"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Step 2: Upload File -->
        <div class="card hidden" id="step2">
            <div class="card-header">
                <i class="fas fa-upload"></i>
                <h3>Step 2: Upload Your File</h3>
            </div>
            <div class="card-body">
                <div class="upload-zone" id="uploadZone" onclick="document.getElementById('fileInput').click()">
                    <i class="fas fa-cloud-upload-alt"></i>
                    <h4>Drag & drop your file here</h4>
                    <p>or click to browse (CSV files only)</p>
                    <input type="file" id="fileInput" accept=".csv,.txt" onchange="handleFileSelect(this.files[0])">
                </div>

                <div class="file-selected hidden" id="fileSelected">
                    <i class="fas fa-check-circle"></i>
                    <div class="file-info">
                        <h5 id="fileName">file.csv</h5>
                        <p id="fileSize">0 KB</p>
                    </div>
                    <button class="btn btn-secondary" onclick="clearFile()">
                        <i class="fas fa-times"></i> Remove
                    </button>
                </div>

                <div class="import-options">
                    <label class="option-checkbox">
                        <input type="checkbox" id="skipHints" checked>
                        Skip hint/description row (row 2)
                    </label>
                    <label class="option-checkbox">
                        <input type="checkbox" id="updateExisting">
                        Update existing items (by Item Code)
                    </label>
                </div>

                <div class="btn-group">
                    <button class="btn btn-secondary" onclick="goToStep(1)">
                        <i class="fas fa-arrow-left"></i> Back
                    </button>
                    <button class="btn btn-primary" id="previewBtn" onclick="previewFile()" disabled>
                        Preview Data <i class="fas fa-eye"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Step 3: Preview & Import -->
        <div class="card hidden" id="step3">
            <div class="card-header">
                <i class="fas fa-check-double"></i>
                <h3>Step 3: Review & Import</h3>
            </div>
            <div class="card-body">
                <div id="previewSection">
                    <h5><i class="fas fa-table me-1"></i>Data Preview (first 10 rows)</h5>
                    <div style="overflow-x: auto;">
                        <table class="preview-table" id="previewTable">
                            <thead></thead>
                            <tbody></tbody>
                        </table>
                    </div>
                    <p class="text-muted mt-2" id="totalRowsInfo"></p>

                    <div class="btn-group">
                        <button class="btn btn-secondary" onclick="goToStep(2)">
                            <i class="fas fa-arrow-left"></i> Back
                        </button>
                        <button class="btn btn-success" onclick="startImport()">
                            <i class="fas fa-file-import"></i> Start Import
                        </button>
                    </div>
                </div>

                <div id="resultsSection" class="hidden">
                    <h5><i class="fas fa-chart-bar me-1"></i>Import Results</h5>
                    <div class="results-summary">
                        <div class="result-stat success">
                            <h3 id="resultSuccess">0</h3>
                            <p>Items Imported</p>
                        </div>
                        <div class="result-stat warning">
                            <h3 id="resultSkipped">0</h3>
                            <p>Items Skipped</p>
                        </div>
                        <div class="result-stat danger">
                            <h3 id="resultErrors">0</h3>
                            <p>Errors</p>
                        </div>
                    </div>

                    <div id="errorsList" class="error-list hidden"></div>

                    <div class="btn-group">
                        <button class="btn btn-secondary" onclick="location.reload()">
                            <i class="fas fa-redo"></i> Import More
                        </button>
                        <a href="index.php" class="btn btn-primary">
                            <i class="fas fa-boxes"></i> View Inventory
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include '../../includes/main-js.php'; ?>
    <script>
        let selectedFile = null;

        function goToStep(step) {
            // Hide all steps
            document.querySelectorAll('.card').forEach(c => c.classList.add('hidden'));
            document.querySelectorAll('.step').forEach(s => s.classList.remove('active', 'completed'));
            
            // Show current step
            document.getElementById('step' + step).classList.remove('hidden');
            document.getElementById('step' + step + '-indicator').classList.add('active');
            
            // Mark previous steps as completed
            for (let i = 1; i < step; i++) {
                document.getElementById('step' + i + '-indicator').classList.add('completed');
            }
        }

        function downloadTemplate(format) {
            window.location.href = '../../Ajax/php/inventory-import.php?action=download_template&format=' + format;
        }

        function exportExisting() {
            window.location.href = '../../Ajax/php/inventory-import.php?action=export';
        }

        // Drag and drop
        const uploadZone = document.getElementById('uploadZone');
        
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
            if (e.dataTransfer.files.length) {
                handleFileSelect(e.dataTransfer.files[0]);
            }
        });

        function handleFileSelect(file) {
            if (!file) return;
            
            const ext = file.name.split('.').pop().toLowerCase();
            if (!['csv', 'txt'].includes(ext)) {
                Swal.fire('Invalid File', 'Please select a CSV file', 'error');
                return;
            }
            
            selectedFile = file;
            
            document.getElementById('uploadZone').classList.add('hidden');
            document.getElementById('fileSelected').classList.remove('hidden');
            document.getElementById('fileName').textContent = file.name;
            document.getElementById('fileSize').textContent = formatFileSize(file.size);
            document.getElementById('previewBtn').disabled = false;
        }

        function clearFile() {
            selectedFile = null;
            document.getElementById('fileInput').value = '';
            document.getElementById('uploadZone').classList.remove('hidden');
            document.getElementById('fileSelected').classList.add('hidden');
            document.getElementById('previewBtn').disabled = true;
        }

        function formatFileSize(bytes) {
            if (bytes < 1024) return bytes + ' B';
            if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
            return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
        }

        function previewFile() {
            if (!selectedFile) return;
            
            const formData = new FormData();
            formData.append('file', selectedFile);
            formData.append('action', 'preview');
            
            fetch('../../Ajax/php/inventory-import.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    renderPreview(data.preview, data.total_rows);
                    goToStep(3);
                } else {
                    Swal.fire('Error', data.error, 'error');
                }
            })
            .catch(err => {
                Swal.fire('Error', 'Failed to preview file', 'error');
            });
        }

        function renderPreview(rows, totalRows) {
            const thead = document.querySelector('#previewTable thead');
            const tbody = document.querySelector('#previewTable tbody');
            
            thead.innerHTML = '';
            tbody.innerHTML = '';
            
            if (rows.length === 0) return;
            
            // Headers
            const headerRow = document.createElement('tr');
            rows[0].forEach(cell => {
                const th = document.createElement('th');
                th.textContent = cell;
                headerRow.appendChild(th);
            });
            thead.appendChild(headerRow);
            
            // Data rows (skip header)
            for (let i = 1; i < rows.length; i++) {
                const tr = document.createElement('tr');
                rows[i].forEach(cell => {
                    const td = document.createElement('td');
                    td.textContent = cell || '-';
                    tr.appendChild(td);
                });
                tbody.appendChild(tr);
            }
            
            document.getElementById('totalRowsInfo').textContent = 
                `Showing ${Math.min(rows.length - 1, 10)} of ${totalRows} total rows`;
        }

        function startImport() {
            if (!selectedFile) return;
            
            Swal.fire({
                title: 'Importing...',
                text: 'Please wait while we process your file',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });
            
            const formData = new FormData();
            formData.append('file', selectedFile);
            formData.append('action', 'import');
            formData.append('skip_hints', document.getElementById('skipHints').checked ? '1' : '0');
            formData.append('update_existing', document.getElementById('updateExisting').checked ? '1' : '0');
            
            fetch('../../Ajax/php/inventory-import.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                Swal.close();
                showResults(data);
            })
            .catch(err => {
                Swal.fire('Error', 'Import failed', 'error');
            });
        }

        function showResults(data) {
            document.getElementById('previewSection').classList.add('hidden');
            document.getElementById('resultsSection').classList.remove('hidden');
            
            document.getElementById('resultSuccess').textContent = data.imported || 0;
            document.getElementById('resultSkipped').textContent = data.skipped || 0;
            document.getElementById('resultErrors').textContent = data.errors?.length || 0;
            
            if (data.errors && data.errors.length > 0) {
                const errorsList = document.getElementById('errorsList');
                errorsList.classList.remove('hidden');
                errorsList.innerHTML = data.errors.map(e => `
                    <div class="error-item">
                        <span class="row-num">Row ${e.row}:</span>
                        <span>${e.error}</span>
                    </div>
                `).join('');
            }
            
            if (data.imported > 0) {
                Swal.fire({
                    icon: 'success',
                    title: 'Import Complete!',
                    text: `Successfully imported ${data.imported} items`,
                    timer: 3000
                });
            }
        }
    </script>
</body>
</html>
