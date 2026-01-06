<?php
require_once __DIR__ . '/../../classes/Includes.php';
requirePagePermission('View');

$company = new CompanyProfile();
$companyId = $_SESSION['company_id'] ?? 1;
$hasProfile = $company->loadActive($companyId);

if (!$hasProfile) {
    $company->company_id = $companyId;
    $company->name = APP_NAME ?? 'Your Company Name';
    $company->address = '';
    $company->mobile_number_1 = '';
    $company->mobile_number_2 = '';
    $company->email = '';
    $company->is_active = 1;
    if ($company->create()) {
        $company->loadActive($companyId);
    }
    $hasProfile = true;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Company Profile Settings</title>
    <?php include '../../includes/main-css.php'; ?>
    <style>
        :root {
            --primary-color: #7c3aed;
            --primary-hover: #6d28d9;
            --secondary-color: #64748b;
            --success-color: #059669;
            --danger-color: #dc2626;
            --border-color: #e2e8f0;
            --bg-light: #f8fafc;
            --text-dark: #1e293b;
            --text-muted: #64748b;
            --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
        }
        body { background-color: var(--bg-light); font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif; }
        .content { margin-top: 70px; padding: 1.5rem; }
        @media (min-width: 768px) { .content { padding: 2rem; } }
        .page-header { background: #fff; border-radius: 8px; box-shadow: var(--shadow-sm); margin-bottom: 1.5rem; padding: 1.25rem 1.5rem; border-left: 4px solid var(--primary-color); }
        .page-header-content { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 1rem; }
        .page-header .title-section h1 { font-size: 1.25rem; font-weight: 600; color: var(--text-dark); margin: 0; }
        .page-header .title-section p { font-size: 0.875rem; color: var(--text-muted); margin: 0.25rem 0 0; }
        .settings-card { background: #fff; border: 1px solid var(--border-color); border-radius: 8px; box-shadow: var(--shadow-sm); overflow: hidden; margin-bottom: 1.5rem; }
        .settings-card-header { padding: 1rem 1.5rem; border-bottom: 1px solid var(--border-color); background-color: var(--bg-light); }
        .settings-card-header h5 { margin: 0; font-size: 0.9375rem; font-weight: 600; color: var(--text-dark); }
        .settings-card-body { padding: 1.5rem; }
        .form-label { font-size: 0.8125rem; font-weight: 500; color: var(--text-dark); margin-bottom: 0.375rem; }
        .form-control, .form-select { font-size: 0.875rem; border-color: var(--border-color); border-radius: 6px; padding: 0.5rem 0.75rem; }
        .form-control:focus, .form-select:focus { border-color: var(--primary-color); box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.1); }
        .btn { font-size: 0.875rem; font-weight: 500; padding: 0.5rem 1rem; border-radius: 6px; }
        .btn-primary { background-color: var(--primary-color); border-color: var(--primary-color); }
        .btn-primary:hover { background-color: var(--primary-hover); border-color: var(--primary-hover); }
        .image-preview { max-width: 200px; max-height: 100px; margin-top: 10px; border: 2px solid var(--border-color); padding: 8px; border-radius: 8px; }
        .favicon-preview { width: 48px; height: 48px; margin-top: 10px; border: 2px solid var(--border-color); padding: 8px; border-radius: 8px; }
        .upload-area { border: 2px dashed var(--border-color); border-radius: 8px; padding: 1rem; text-align: center; background-color: var(--bg-light); transition: all 0.2s; }
        .upload-area:hover { border-color: var(--primary-color); background-color: rgba(124, 58, 237, 0.05); }
        .theme-card { cursor: pointer; text-align: center; transition: all 0.2s; }
        .theme-card input { display: none; }
        .theme-preview { width: 60px; height: 40px; border-radius: 8px; border: 3px solid transparent; transition: all 0.2s; box-shadow: 0 2px 8px rgba(0,0,0,0.15); }
        .theme-card:hover .theme-preview { transform: scale(1.05); }
        .theme-card.active .theme-preview, .theme-card input:checked + .theme-preview { border-color: var(--primary-color); box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.3); }
        .theme-name { display: block; font-size: 0.75rem; margin-top: 0.5rem; color: var(--text-muted); font-weight: 500; }
        .summary-card { background: #fff; border: 1px solid var(--border-color); border-radius: 8px; box-shadow: var(--shadow-sm); overflow: hidden; position: sticky; top: 90px; }
        .summary-item { margin-bottom: 1rem; }
        .summary-item strong { display: block; font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted); margin-bottom: 0.25rem; }
        .summary-item p { margin: 0; color: var(--text-dark); font-size: 0.9375rem; }
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
                        <div class="page-header-content">
                            <div class="title-section">
                                <h1><i class="fas fa-building me-2"></i>Company Profile Settings</h1>
                                <p>Configure your company information, branding, and financial settings</p>
                            </div>
                        </div>
                    </div>
                    <form id="companyProfileForm">
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="id" value="<?php echo $company->id; ?>">
                        <div class="row">
                            <div class="col-lg-8">
                                <div class="settings-card">
                                    <div class="settings-card-header"><h5><i class="fas fa-info-circle me-2"></i>Company Information</h5></div>
                                    <div class="settings-card-body">
                                        <div class="row">
                                            <div class="col-md-6 mb-3"><label class="form-label">Company Name <span class="text-danger">*</span></label><input type="text" class="form-control" name="name" value="<?php echo htmlspecialchars($company->name); ?>" required></div>
                                            <div class="col-md-6 mb-3"><label class="form-label">Company Code</label><input type="text" class="form-control" name="company_code" value="<?php echo htmlspecialchars($company->company_code); ?>"></div>
                                        </div>
                                        <div class="mb-3"><label class="form-label">Address <span class="text-danger">*</span></label><textarea class="form-control" name="address" rows="2" required><?php echo htmlspecialchars($company->address); ?></textarea></div>
                                        <div class="row">
                                            <div class="col-md-4 mb-3"><label class="form-label">Mobile Number 1 <span class="text-danger">*</span></label><input type="text" class="form-control" name="mobile_number_1" value="<?php echo htmlspecialchars($company->mobile_number_1); ?>" required></div>
                                            <div class="col-md-4 mb-3"><label class="form-label">Mobile Number 2</label><input type="text" class="form-control" name="mobile_number_2" value="<?php echo htmlspecialchars($company->mobile_number_2); ?>"></div>
                                            <div class="col-md-4 mb-3"><label class="form-label">Email <span class="text-danger">*</span></label><input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($company->email); ?>" required></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="settings-card">
                                    <div class="settings-card-header"><h5><i class="fas fa-palette me-2"></i>Branding</h5></div>
                                    <div class="settings-card-body">
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Company Logo</label>
                                                <div class="upload-area"><button type="button" class="btn btn-sm btn-primary mb-2" onclick="document.getElementById('logoUpload').click()"><i class="fas fa-upload me-1"></i>Upload Logo</button><input type="file" id="logoUpload" accept="image/*" style="display: none;"><p class="text-muted small mb-0">Max: 5MB (JPG, PNG, GIF)</p></div>
                                                <?php if ($company->image_name): ?><img src="../../uploads/company/<?php echo htmlspecialchars($company->image_name); ?>" id="logoPreview" class="image-preview" alt="Logo"><?php else: ?><img id="logoPreview" class="image-preview d-none" alt="Logo Preview"><?php endif; ?>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Favicon</label>
                                                <div class="upload-area"><button type="button" class="btn btn-sm btn-primary mb-2" onclick="document.getElementById('faviconUpload').click()"><i class="fas fa-upload me-1"></i>Upload Favicon</button><input type="file" id="faviconUpload" accept="image/x-icon,image/png" style="display: none;"><p class="text-muted small mb-0">Max: 1MB (ICO, PNG)</p></div>
                                                <?php if ($company->favicon): ?><img src="../../uploads/company/<?php echo htmlspecialchars($company->favicon); ?>" id="faviconPreview" class="favicon-preview" alt="Favicon"><?php else: ?><img id="faviconPreview" class="favicon-preview d-none" alt="Favicon Preview"><?php endif; ?>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Header Theme</label>
                                            <div class="d-flex flex-wrap gap-3 mt-2">
                                                <?php
                                                $themes = [
                                                    'default' => ['name' => 'Default', 'gradient' => 'linear-gradient(135deg, #1a1a2e 0%, #16213e 100%)'],
                                                    'purple' => ['name' => 'Purple', 'gradient' => 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)'],
                                                    'blue' => ['name' => 'Blue', 'gradient' => 'linear-gradient(135deg, #2193b0 0%, #6dd5ed 100%)'],
                                                    'green' => ['name' => 'Green', 'gradient' => 'linear-gradient(135deg, #11998e 0%, #38ef7d 100%)'],
                                                    'red' => ['name' => 'Red', 'gradient' => 'linear-gradient(135deg, #cb2d3e 0%, #ef473a 100%)'],
                                                    'orange' => ['name' => 'Orange', 'gradient' => 'linear-gradient(135deg, #f46b45 0%, #eea849 100%)'],
                                                    'dark' => ['name' => 'Dark', 'gradient' => 'linear-gradient(135deg, #232526 0%, #414345 100%)'],
                                                    'teal' => ['name' => 'Teal', 'gradient' => 'linear-gradient(135deg, #0f2027 0%, #2c5364 100%)'],
                                                ];
                                                foreach ($themes as $key => $themeData):
                                                    $isActive = ($company->theme === $key) ? 'active' : '';
                                                ?>
                                                <label class="theme-card <?php echo $isActive; ?>"><input type="radio" name="theme" value="<?php echo $key; ?>" <?php echo $isActive ? 'checked' : ''; ?>><div class="theme-preview" style="background: <?php echo $themeData['gradient']; ?>;"></div><span class="theme-name"><?php echo $themeData['name']; ?></span></label>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="settings-card">
                                    <div class="settings-card-header"><h5><i class="fas fa-receipt me-2"></i>Tax Configuration</h5></div>
                                    <div class="settings-card-body">
                                        <div class="row">
                                            <div class="col-md-4 mb-3"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" id="isVat" name="is_vat" value="1" <?php echo $company->is_vat ? 'checked' : ''; ?>><label class="form-check-label" for="isVat">Enable VAT/Tax</label></div></div>
                                            <div class="col-md-4 mb-3"><label class="form-label">Tax Number</label><input type="text" class="form-control" name="tax_number" value="<?php echo htmlspecialchars($company->tax_number); ?>"></div>
                                            <div class="col-md-4 mb-3"><label class="form-label">Tax Percentage (%)</label><input type="number" class="form-control" name="tax_percentage" value="<?php echo $company->tax_percentage; ?>" min="0" max="100" step="0.01"></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="settings-card">
                                    <div class="settings-card-header"><h5><i class="fas fa-dollar-sign me-2"></i>Financial Settings</h5></div>
                                    <div class="settings-card-body">
                                        <div class="row"><div class="col-md-6 mb-3"><label class="form-label">Cashbook Opening Balance</label><div class="input-group"><span class="input-group-text">LKR</span><input type="number" class="form-control" name="cashbook_opening_balance" value="<?php echo $company->cashbook_opening_balance; ?>" step="0.01"></div></div></div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-4">
                                <div class="summary-card">
                                    <div class="settings-card-header"><h5><i class="fas fa-clipboard-check me-2"></i>Current Settings</h5></div>
                                    <div class="settings-card-body">
                                        <div class="summary-item"><strong>Company Name:</strong><p><?php echo htmlspecialchars($company->name); ?></p></div>
                                        <div class="summary-item"><strong>Contact:</strong><p><?php echo htmlspecialchars($company->mobile_number_1); ?></p></div>
                                        <div class="summary-item"><strong>Email:</strong><p><?php echo htmlspecialchars($company->email); ?></p></div>
                                        <div class="summary-item"><strong>VAT Status:</strong><p><?php if ($company->is_vat): ?><span class="badge bg-success">Enabled</span><?php else: ?><span class="badge bg-secondary">Disabled</span><?php endif; ?></p></div>
                                        <div class="d-grid mt-4"><button type="submit" class="btn btn-primary btn-lg"><i class="fas fa-save me-1"></i>Save Changes</button></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <?php include '../../includes/main-js.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../../Ajax/js/company-profile.js"></script>
</body>
</html>
