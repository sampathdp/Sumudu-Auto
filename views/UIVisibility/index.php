<?php
require_once __DIR__ . '/../../classes/Includes.php';

// Only allow admin users (role_id = 1)
if (!isset($_SESSION['role_id']) || $_SESSION['role_id'] != 1) {
    header('Location: ' . BASE_URL . 'views/Dashboard/');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Components Visibility</title>
    <?php include '../../includes/main-css.php'; ?>
    <style>
        :root {
            --primary-color: #7c3aed;
            --primary-hover: #6d28d9;
            --border-color: #e2e8f0;
            --bg-light: #f8fafc;
            --text-dark: #1e293b;
            --text-muted: #64748b;
            --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
        }

        body { background-color: var(--bg-light); font-family: 'Inter', sans-serif; }
        .content { margin-top: 70px; padding: 1.5rem; }
        @media (min-width: 768px) { .content { padding: 2rem; } }

        .page-header {
            background: #fff;
            border-radius: 8px;
            box-shadow: var(--shadow-sm);
            margin-bottom: 1.5rem;
            padding: 1.25rem 1.5rem;
            border-left: 4px solid var(--primary-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .page-header h1 { font-size: 1.25rem; font-weight: 600; color: var(--text-dark); margin: 0; }
        .page-header p { font-size: 0.875rem; color: var(--text-muted); margin: 0.25rem 0 0; }

        .company-select-wrapper {
            min-width: 250px;
        }

        .module-section {
            background: #fff;
            border: 1px solid var(--border-color);
            border-radius: 12px;
            margin-bottom: 1.5rem;
            overflow: hidden;
            box-shadow: var(--shadow-sm);
        }

        .section-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff;
            padding: 1rem 1.25rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .section-header h3 {
            margin: 0;
            font-size: 1rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .section-header i { font-size: 1.25rem; }

        .module-list { padding: 0; margin: 0; list-style: none; }

        .module-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1rem 1.25rem;
            border-bottom: 1px solid var(--border-color);
            transition: background 0.2s;
        }

        .module-item:last-child { border-bottom: none; }
        .module-item:hover { background: var(--bg-light); }

        .module-info {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .module-icon {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            background: rgba(124, 58, 237, 0.1);
            color: var(--primary-color);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .module-name { font-weight: 500; color: var(--text-dark); }

        /* Toggle Switch */
        .toggle-switch {
            position: relative;
            width: 50px;
            height: 26px;
        }

        .toggle-switch input { opacity: 0; width: 0; height: 0; }

        .toggle-slider {
            position: absolute;
            cursor: pointer;
            top: 0; left: 0; right: 0; bottom: 0;
            background-color: #cbd5e1;
            transition: 0.3s;
            border-radius: 26px;
        }

        .toggle-slider:before {
            content: "";
            position: absolute;
            height: 20px;
            width: 20px;
            left: 3px;
            bottom: 3px;
            background-color: white;
            transition: 0.3s;
            border-radius: 50%;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }

        input:checked + .toggle-slider { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        input:checked + .toggle-slider:before { transform: translateX(24px); }
        input:disabled + .toggle-slider { opacity: 0.5; cursor: not-allowed; }

        .loading-overlay {
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(255,255,255,0.8);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 9999;
        }

        .loading-overlay.show { display: flex; }

        .alert-info-box {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
            border: 1px solid rgba(102, 126, 234, 0.3);
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }

        .alert-info-box i { color: var(--primary-color); }
        
        /* Select overrides */
        .form-select-lg {
            font-size: 0.95rem;
            padding: 0.6rem 1rem;
            border-color: #e2e8f0;
            border-radius: 8px;
            cursor: pointer;
        }
        .form-select-lg:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px rgba(124, 58, 237, 0.2);
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <?php include '../../includes/sidebar.php'; ?>
        <div class="main-panel">
            <?php include '../../includes/header.php'; ?>
            <div class="content">
                <div class="container-fluid">
                    <div class="page-header">
                        <div>
                            <h1><i class="fas fa-layer-group me-2"></i>UI Components Visibility</h1>
                            <p>Control specific UI elements visibility per company</p>
                        </div>
                        <div class="d-flex gap-2">
                            <a href="../UIComponents/" class="btn btn-outline-primary">
                                <i class="fas fa-puzzle-piece me-2"></i> Manage Components
                            </a>
                            <div class="company-select-wrapper">
                                <select id="companySelect" class="form-select form-select-lg">
                                    <option value="" disabled selected>Select a Company...</option>
                                    <!-- Options loaded via AJAX -->
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="alert-info-box">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Note:</strong> Settings are saved automatically when toggled. Changes apply immediately to the selected company's users.
                    </div>

                    <div id="componentsContainer">
                        <div class="text-center py-5 text-muted">
                            <i class="fas fa-building fa-3x mb-3 text-secondary opacity-50"></i>
                            <p>Please select a company to configure UI settings.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="loading-overlay" id="loadingOverlay">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Saving...</span>
        </div>
    </div>

    <?php include '../../includes/main-js.php'; ?>
    <script>
    $(document).ready(function() {
        loadCompanies();

        // Load Companies Dropdown
        function loadCompanies() {
            $.get('../../Ajax/php/ui_settings.php', { action: 'get_companies' }, function(response) {
                if (response.status === 'success') {
                    const $select = $('#companySelect');
                    response.data.forEach(company => {
                        $select.append(`<option value="${company.id}">${company.company_name}</option>`);
                    });
                } else {
                    showAlert('error', 'Failed to load companies');
                }
            });
        }

        // On Company Select Change
        $('#companySelect').change(function() {
            const companyId = $(this).val();
            if(companyId) {
                loadSettings(companyId);
            }
        });

        // Load Settings for Company
        function loadSettings(companyId) {
            $('#componentsContainer').html(`
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>
                    <p class="mt-2 text-muted">Loading settings...</p>
                </div>
            `);

            $.get('../../Ajax/php/ui_settings.php', { action: 'list_settings', company_id: companyId }, function(response) {
                if (response.status === 'success') {
                    renderComponents(response.data);
                } else {
                    showAlert('error', response.message || 'Failed to load settings');
                }
            }).fail(function() {
                showAlert('error', 'Server connection failed');
            });
        }

        function renderComponents(categories) {
            if (!categories || categories.length === 0) {
                $('#componentsContainer').html('<div class="text-center py-5 text-muted">No configurable components found.</div>');
                return;
            }

            let html = '';
            
            categories.forEach(function(cat) {
                html += `
                    <div class="module-section">
                        <div class="section-header">
                            <h3><i class="fas fa-list"></i> ${cat.category}</h3>
                        </div>
                        <ul class="module-list">`;
                
                cat.items.forEach(function(item) {
                     html += `
                        <li class="module-item">
                            <div class="module-info">
                                <div class="module-icon">
                                    <i class="fas ${item.icon || 'fa-cog'}"></i>
                                </div>
                                <span class="module-name">${item.name}</span>
                            </div>
                            <label class="toggle-switch">
                                <input type="checkbox" class="component-toggle" 
                                       data-key="${item.key}" 
                                       ${item.is_visible ? 'checked' : ''}>
                                <span class="toggle-slider"></span>
                            </label>
                        </li>`;
                });
                
                html += `</ul></div>`;
            });
            
            $('#componentsContainer').html(html);
        }

        // Toggle component visibility
        $(document).on('change', '.component-toggle', function() {
            const $toggle = $(this);
            const componentKey = $toggle.data('key');
            const isVisible = $toggle.is(':checked') ? 1 : 0;
            const companyId = $('#companySelect').val();
            
            if (!companyId) return;

            $toggle.prop('disabled', true);
            $('#loadingOverlay').addClass('show');
            
            $.post('../../Ajax/php/ui_settings.php', {
                action: 'toggle',
                company_id: companyId,
                component_key: componentKey,
                is_visible: isVisible
            }, function(response) {
                if (response.status === 'success') {
                    showAlert('success', `Setting updated successfully`);
                } else {
                    $toggle.prop('checked', !isVisible); // Revert
                    showAlert('error', response.message || 'Failed to update');
                }
            }).fail(function() {
                $toggle.prop('checked', !isVisible);
                showAlert('error', 'Connection error');
            }).always(function() {
                $toggle.prop('disabled', false);
                $('#loadingOverlay').removeClass('show');
            });
        });

        function showAlert(type, message) {
            const Toast = Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true
            });
            Toast.fire({ icon: type, title: message });
        }
    });
    </script>
</body>
</html>
