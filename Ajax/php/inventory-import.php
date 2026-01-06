<?php
/**
 * Inventory Import/Export AJAX Handler
 */
session_start();
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../classes/Database.php';
require_once __DIR__ . '/../../classes/InventoryImport.php';

header('Content-Type: application/json');

$companyId = $_SESSION['company_id'] ?? 1;
$action = $_REQUEST['action'] ?? '';

try {
    $importer = new InventoryImport($companyId);
    
    switch ($action) {
        case 'download_template':
            $format = $_GET['format'] ?? 'csv';
            
            if ($format === 'excel') {
                header('Content-Type: application/vnd.ms-excel');
                header('Content-Disposition: attachment; filename="inventory_import_template.xls"');
                header('Cache-Control: max-age=0');
                echo $importer->generateExcelTemplate();
            } else {
                header('Content-Type: text/csv; charset=UTF-8');
                header('Content-Disposition: attachment; filename="inventory_import_template.csv"');
                header('Cache-Control: max-age=0');
                echo $importer->generateCSVTemplate();
            }
            exit;
            
        case 'export':
            header('Content-Type: text/csv; charset=UTF-8');
            header('Content-Disposition: attachment; filename="inventory_export_' . date('Y-m-d') . '.csv"');
            header('Cache-Control: max-age=0');
            echo $importer->exportToCSV();
            exit;
            
        case 'import':
            if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
                throw new Exception('No file uploaded or upload error');
            }
            
            $file = $_FILES['file'];
            $allowedTypes = ['text/csv', 'text/plain', 'application/vnd.ms-excel', 'application/csv'];
            $allowedExtensions = ['csv', 'txt'];
            
            // Validate file type
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, $allowedExtensions)) {
                throw new Exception('Invalid file type. Please upload a CSV file.');
            }
            
            // Move to temp location
            $tempPath = sys_get_temp_dir() . '/' . uniqid('inv_import_') . '.csv';
            if (!move_uploaded_file($file['tmp_name'], $tempPath)) {
                throw new Exception('Failed to process uploaded file');
            }
            
            // Import options
            $skipHints = isset($_POST['skip_hints']) && $_POST['skip_hints'] === '1';
            $updateExisting = isset($_POST['update_existing']) && $_POST['update_existing'] === '1';
            
            // Perform import
            $result = $importer->importFromCSV($tempPath, $skipHints, $updateExisting);
            
            // Cleanup
            unlink($tempPath);
            
            echo json_encode([
                'success' => true,
                'imported' => $result['success'],
                'skipped' => $result['skipped'],
                'errors' => $result['errors'],
                'message' => "Imported {$result['success']} items. Skipped {$result['skipped']}. Errors: " . count($result['errors'])
            ]);
            break;
            
        case 'preview':
            if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
                throw new Exception('No file uploaded');
            }
            
            $file = $_FILES['file'];
            $handle = fopen($file['tmp_name'], 'r');
            
            $rows = [];
            $count = 0;
            $maxPreview = 10;
            
            while (($row = fgetcsv($handle)) !== false && $count < $maxPreview + 2) {
                $rows[] = $row;
                $count++;
            }
            
            fclose($handle);
            
            // Get total line count
            $totalRows = count(file($file['tmp_name'])) - 1; // Exclude header
            
            echo json_encode([
                'success' => true,
                'preview' => $rows,
                'total_rows' => $totalRows
            ]);
            break;
            
        case 'get_template_info':
            $columns = InventoryImport::getTemplateColumns();
            echo json_encode([
                'success' => true,
                'columns' => $columns
            ]);
            break;
            
        default:
            throw new Exception('Invalid action');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
