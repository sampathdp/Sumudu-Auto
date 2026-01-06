<?php
require_once __DIR__ . '/../../classes/Includes.php';
requirePagePermission('View');

// Only allow Admins (Role ID 1) to access this page
if ($_SESSION['role_id'] != 1) {
    echo '<div class="alert alert-danger">Access Denied</div>';
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Module Visibility</title>
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
        .module-route { font-size: 0.75rem; color: var(--text-muted); display: block; }

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
                            <h1><i class="fas fa-eye me-2"></i>Module Visibility</h1>
                            <p>Control which modules are visible for this company</p>
                        </div>
                        <div class="company-select-wrapper">
                            <select id="companySelect" class="form-select form-select-lg">
                                <?php
                                // Populate companies if super admin, else just show current
                                echo '<option value="' . $_SESSION['company_id'] . '">' . $_SESSION['company_name'] . '</option>';
                                ?>
                            </select>
                        </div>
                    </div>

                    <div class="alert-info-box">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Note:</strong> Settings are saved automatically when toggled. Changes apply immediately to the selected company's users.
                    </div>

                    <div id="visibilityContainer">
                        <div class="text-center py-5">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
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
            // loadVisibilityTree(); // Called inside loadCompanies now

            // Load Companies Dropdown (Reuse UI Settings endpoint for company list if compatible, or just rely on session for now as per previous logic)
            // For now, logic remains: user sees their session company. If we want super admin to select, we'd need that logic.
            // Keeping it simple as per previous version:
            
            function loadCompanies() {
                const currentCompanyId = '<?php echo $_SESSION['company_id']; ?>';
                
                $.post('../../Ajax/php/module_visibility.php', { action: 'get_companies' }, function(response) {
                    if (response.status === 'success') {
                        let options = '';
                        response.data.forEach(company => {
                            const selected = (company.id == currentCompanyId) ? 'selected' : '';
                            options += `<option value="${company.id}" ${selected}>${company.company_name}</option>`;
                        });
                        $('#companySelect').html(options);
                        
                        // Trigger load for the selected company immediately after populating
                        loadVisibilityTree(); 
                    } else {
                        console.error('Failed to load companies:', response.message);
                    }
                }, 'json');
            }

            // Trigger load from within loadCompanies callback instead of parallel
            // loadVisibilityTree();

            function loadVisibilityTree() {
                const companyId = $('#companySelect').val();
                
                $('#visibilityContainer').html(`
                    <div class="text-center py-5">
                        <div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>
                        <p class="mt-2 text-muted">Loading modules...</p>
                    </div>
                `);

                $.post('../../Ajax/php/module_visibility.php', { 
                    action: 'get_tree',
                    company_id: companyId 
                }, function(response) {
                    if (response.status === 'success') {
                        renderTree(response.data);
                    } else {
                        $('#visibilityContainer').html(`<div class="alert alert-danger">${response.message}</div>`);
                    }
                }, 'json').fail(function() {
                     $('#visibilityContainer').html('<div class="alert alert-danger">Failed to load data</div>');
                });
            }

            function renderTree(tree) {
                if (Object.keys(tree).length === 0) {
                     $('#visibilityContainer').html('<div class="text-center py-5 text-muted">No modules found.</div>');
                     return;
                }

                let html = '';
                
                for (const [category, data] of Object.entries(tree)) {
                    html += `
                        <div class="module-section">
                            <div class="section-header">
                                <h3><i class="${data.icon} me-2"></i>${category}</h3>
                            </div>
                            <ul class="module-list">`;
                    
                    data.pages.forEach(page => {
                        const isChecked = page.is_visible ? 'checked' : '';
                        html += `
                            <li class="module-item">
                                <div class="module-info">
                                    <div class="module-icon"><i class="${page.icon}"></i></div>
                                    <div>
                                        <div class="module-name">${page.name}</div>
                                        <span class="module-route">${page.route}</span>
                                    </div>
                                </div>
                                <label class="toggle-switch">
                                    <input type="checkbox" class="toggle-visibility" 
                                        data-page-id="${page.id}" ${isChecked}>
                                    <span class="toggle-slider"></span>
                                </label>
                            </li>`;
                    });

                    html += `</ul></div>`;
                }

                $('#visibilityContainer').html(html);
            }

            // Handle Toggle
            $(document).on('change', '.toggle-visibility', function() {
                const $toggle = $(this);
                const pageId = $toggle.data('page-id');
                const isVisible = $toggle.is(':checked') ? 1 : 0;
                const companyId = $('#companySelect').val();

                $toggle.prop('disabled', true);
                $('#loadingOverlay').addClass('show');

                $.post('../../Ajax/php/module_visibility.php', {
                    action: 'toggle',
                    company_id: companyId,
                    page_id: pageId,
                    is_visible: isVisible
                }, function(response) {
                    if (response.status === 'success') {
                         const Toast = Swal.mixin({
                            toast: true,
                            position: 'top-end',
                            showConfirmButton: false,
                            timer: 1500,
                            timerProgressBar: true
                        });
                        Toast.fire({
                            icon: 'success',
                            title: 'Visibility updated'
                        });
                    } else {
                        Swal.fire('Error', response.message, 'error');
                        $toggle.prop('checked', !isVisible); // Revert
                    }
                }, 'json').fail(function() {
                    Swal.fire('Error', 'Connection failed', 'error');
                    $toggle.prop('checked', !isVisible);
                }).always(function() {
                    $toggle.prop('disabled', false);
                    $('#loadingOverlay').removeClass('show');
                });
            });
            
            // Reload on company change if we enable that later
            $('#companySelect').change(function() {
                 loadVisibilityTree();
            });
        });
    </script>
</body>
</html>
