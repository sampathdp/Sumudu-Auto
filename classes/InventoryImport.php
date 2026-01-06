<?php
/**
 * InventoryImport Class
 * Handles importing inventory items from Excel/CSV files
 * and generating downloadable templates
 */
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/InventoryItem.php';
require_once __DIR__ . '/InventoryCategory.php';

class InventoryImport {
    private $db;
    private $companyId;
    private $errors = [];
    private $successCount = 0;
    private $skipCount = 0;
    
    public function __construct($companyId) {
        $this->db = new Database();
        $this->companyId = $companyId;
    }
    
    /**
     * Get template columns for Excel/CSV export
     */
    public static function getTemplateColumns() {
        return [
            'item_code' => [
                'header' => 'Item Code *',
                'required' => true,
                'description' => 'Unique SKU/Barcode (e.g., OIL-001)',
                'example' => 'SKU-001'
            ],
            'item_name' => [
                'header' => 'Item Name *',
                'required' => true,
                'description' => 'Product name',
                'example' => 'Shell Helix 10W40 4L'
            ],
            'description' => [
                'header' => 'Description',
                'required' => false,
                'description' => 'Product description',
                'example' => 'Semi-synthetic motor oil'
            ],
            'category_name' => [
                'header' => 'Category',
                'required' => false,
                'description' => 'Category name (will be created if not exists)',
                'example' => 'Lubricants'
            ],
            'unit_of_measure' => [
                'header' => 'Unit *',
                'required' => true,
                'description' => 'Unit of measure (pcs, liters, kg, etc.)',
                'example' => 'pcs'
            ],
            'current_stock' => [
                'header' => 'Opening Stock',
                'required' => false,
                'description' => 'Initial stock quantity',
                'example' => '10'
            ],
            'reorder_level' => [
                'header' => 'Reorder Level',
                'required' => false,
                'description' => 'Minimum stock before reorder alert',
                'example' => '5'
            ],
            'max_stock_level' => [
                'header' => 'Max Stock',
                'required' => false,
                'description' => 'Maximum stock capacity',
                'example' => '100'
            ],
            'unit_cost' => [
                'header' => 'Cost Price',
                'required' => false,
                'description' => 'Purchase/cost price',
                'example' => '4500.00'
            ],
            'unit_price' => [
                'header' => 'Selling Price',
                'required' => false,
                'description' => 'Selling price',
                'example' => '5800.00'
            ],
            'is_active' => [
                'header' => 'Active (1/0)',
                'required' => false,
                'description' => '1 = Active, 0 = Inactive',
                'example' => '1'
            ]
        ];
    }
    
    /**
     * Generate CSV template for download
     */
    public function generateCSVTemplate() {
        $columns = self::getTemplateColumns();
        
        // Headers row
        $headers = array_column($columns, 'header');
        
        // Example row
        $examples = array_column($columns, 'example');
        
        // Description row
        $descriptions = array_column($columns, 'description');
        
        $output = fopen('php://temp', 'r+');
        
        // BOM for Excel UTF-8 compatibility
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Write headers
        fputcsv($output, $headers);
        
        // Write example row
        fputcsv($output, $examples);
        
        // Rewind and get content
        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);
        
        return $csv;
    }
    
    /**
     * Generate Excel-compatible XML template (SpreadsheetML)
     */
    public function generateExcelTemplate() {
        $columns = self::getTemplateColumns();
        
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<?mso-application progid="Excel.Sheet"?>' . "\n";
        $xml .= '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"
                 xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">' . "\n";
        
        // Styles
        $xml .= '<Styles>
            <Style ss:ID="Header">
                <Font ss:Bold="1" ss:Color="#FFFFFF"/>
                <Interior ss:Color="#4F46E5" ss:Pattern="Solid"/>
                <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>
            </Style>
            <Style ss:ID="Required">
                <Font ss:Bold="1" ss:Color="#FFFFFF"/>
                <Interior ss:Color="#DC2626" ss:Pattern="Solid"/>
                <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>
            </Style>
            <Style ss:ID="Normal">
                <Alignment ss:Vertical="Center"/>
            </Style>
            <Style ss:ID="Hint">
                <Font ss:Italic="1" ss:Color="#6B7280"/>
                <Alignment ss:Vertical="Center"/>
            </Style>
        </Styles>' . "\n";
        
        // Worksheet
        $xml .= '<Worksheet ss:Name="Inventory Import">' . "\n";
        $xml .= '<Table ss:DefaultColumnWidth="120">' . "\n";
        
        // Header Row
        $xml .= '<Row ss:Height="30">' . "\n";
        foreach ($columns as $key => $col) {
            $style = $col['required'] ? 'Required' : 'Header';
            $xml .= '<Cell ss:StyleID="' . $style . '"><Data ss:Type="String">' . htmlspecialchars($col['header']) . '</Data></Cell>' . "\n";
        }
        $xml .= '</Row>' . "\n";
        
        // Hint Row (descriptions)
        $xml .= '<Row ss:Height="20">' . "\n";
        foreach ($columns as $col) {
            $xml .= '<Cell ss:StyleID="Hint"><Data ss:Type="String">' . htmlspecialchars($col['description']) . '</Data></Cell>' . "\n";
        }
        $xml .= '</Row>' . "\n";
        
        // Example Row
        $xml .= '<Row ss:StyleID="Normal">' . "\n";
        foreach ($columns as $col) {
            $type = is_numeric($col['example']) ? 'Number' : 'String';
            $xml .= '<Cell><Data ss:Type="' . $type . '">' . htmlspecialchars($col['example']) . '</Data></Cell>' . "\n";
        }
        $xml .= '</Row>' . "\n";
        
        // 10 empty rows for user data
        for ($i = 0; $i < 10; $i++) {
            $xml .= '<Row ss:StyleID="Normal">' . "\n";
            foreach ($columns as $col) {
                $xml .= '<Cell><Data ss:Type="String"></Data></Cell>' . "\n";
            }
            $xml .= '</Row>' . "\n";
        }
        
        $xml .= '</Table>' . "\n";
        $xml .= '</Worksheet>' . "\n";
        $xml .= '</Workbook>';
        
        return $xml;
    }
    
    /**
     * Import from CSV file
     */
    public function importFromCSV($filePath, $skipFirstRow = true, $updateExisting = false) {
        $this->errors = [];
        $this->successCount = 0;
        $this->skipCount = 0;
        
        if (!file_exists($filePath)) {
            $this->errors[] = ['row' => 0, 'error' => 'File not found'];
            return false;
        }
        
        $handle = fopen($filePath, 'r');
        if (!$handle) {
            $this->errors[] = ['row' => 0, 'error' => 'Could not open file'];
            return false;
        }
        
        // Detect delimiter
        $firstLine = fgets($handle);
        rewind($handle);
        $delimiter = (substr_count($firstLine, ';') > substr_count($firstLine, ',')) ? ';' : ',';
        
        $rowNum = 0;
        $headers = [];
        
        // Get category cache
        $categories = $this->getCategoryCache();
        
        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            $rowNum++;
            
            // First row is headers
            if ($rowNum === 1) {
                $headers = array_map('trim', $row);
                continue;
            }
            
            // Skip second row if it's description hints
            if ($skipFirstRow && $rowNum === 2) {
                // Check if it looks like hints (contains "Unique" or description-like text)
                if (isset($row[0]) && (stripos($row[0], 'unique') !== false || stripos($row[0], 'product') !== false)) {
                    continue;
                }
            }
            
            // Map row to associative array
            $data = [];
            foreach ($headers as $index => $header) {
                $key = $this->mapHeaderToField($header);
                if ($key && isset($row[$index])) {
                    $data[$key] = trim($row[$index]);
                }
            }
            
            // Skip empty rows
            if (empty($data['item_code']) && empty($data['item_name'])) {
                continue;
            }
            
            // Validate and import
            $result = $this->importRow($data, $categories, $updateExisting, $rowNum);
            
            if ($result === true) {
                $this->successCount++;
            } elseif ($result === 'skipped') {
                $this->skipCount++;
            }
        }
        
        fclose($handle);
        
        return [
            'success' => $this->successCount,
            'skipped' => $this->skipCount,
            'errors' => $this->errors
        ];
    }
    
    /**
     * Map header text to database field
     */
    private function mapHeaderToField($header) {
        $header = strtolower(trim($header));
        $header = preg_replace('/[^a-z0-9]/', '', $header); // Remove special chars
        
        $mapping = [
            'itemcode' => 'item_code',
            'sku' => 'item_code',
            'barcode' => 'item_code',
            'code' => 'item_code',
            'itemname' => 'item_name',
            'productname' => 'item_name',
            'name' => 'item_name',
            'description' => 'description',
            'desc' => 'description',
            'category' => 'category_name',
            'categoryname' => 'category_name',
            'unit' => 'unit_of_measure',
            'uom' => 'unit_of_measure',
            'unitofmeasure' => 'unit_of_measure',
            'openingstock' => 'current_stock',
            'stock' => 'current_stock',
            'currentstock' => 'current_stock',
            'qty' => 'current_stock',
            'quantity' => 'current_stock',
            'reorderlevel' => 'reorder_level',
            'minstock' => 'reorder_level',
            'minimumstock' => 'reorder_level',
            'maxstock' => 'max_stock_level',
            'maxstocklevel' => 'max_stock_level',
            'maximumstock' => 'max_stock_level',
            'costprice' => 'unit_cost',
            'cost' => 'unit_cost',
            'unitcost' => 'unit_cost',
            'purchaseprice' => 'unit_cost',
            'sellingprice' => 'unit_price',
            'price' => 'unit_price',
            'unitprice' => 'unit_price',
            'saleprice' => 'unit_price',
            'active' => 'is_active',
            'isactive' => 'is_active',
            'status' => 'is_active'
        ];
        
        return $mapping[$header] ?? null;
    }
    
    /**
     * Get category ID cache
     */
    private function getCategoryCache() {
        $stmt = $this->db->prepareSelect(
            "SELECT id, category_name FROM inventory_categories WHERE company_id = ?",
            [$this->companyId]
        );
        
        $cache = [];
        if ($stmt) {
            while ($row = $stmt->fetch()) {
                $cache[strtolower(trim($row['category_name']))] = $row['id'];
            }
        }
        return $cache;
    }
    
    /**
     * Import a single row
     */
    private function importRow($data, &$categories, $updateExisting, $rowNum) {
        // Validate required fields
        if (empty($data['item_code'])) {
            $this->errors[] = ['row' => $rowNum, 'error' => 'Item code is required'];
            return false;
        }
        
        if (empty($data['item_name'])) {
            $this->errors[] = ['row' => $rowNum, 'error' => 'Item name is required'];
            return false;
        }
        
        if (empty($data['unit_of_measure'])) {
            $data['unit_of_measure'] = 'pcs'; // Default
        }
        
        // Handle category
        $categoryId = null;
        if (!empty($data['category_name'])) {
            $catKey = strtolower(trim($data['category_name']));
            if (isset($categories[$catKey])) {
                $categoryId = $categories[$catKey];
            } else {
                // Create new category
                $categoryId = $this->createCategory($data['category_name']);
                if ($categoryId) {
                    $categories[$catKey] = $categoryId;
                }
            }
        }
        
        // Check if item exists
        $existing = $this->db->prepareSelect(
            "SELECT id FROM inventory_items WHERE company_id = ? AND item_code = ?",
            [$this->companyId, $data['item_code']]
        );
        
        $existingItem = $existing ? $existing->fetch() : null;
        
        if ($existingItem) {
            if ($updateExisting) {
                return $this->updateItem($existingItem['id'], $data, $categoryId);
            } else {
                $this->errors[] = ['row' => $rowNum, 'error' => 'Item code "' . $data['item_code'] . '" already exists (skipped)'];
                return 'skipped';
            }
        }
        
        // Insert new item
        return $this->insertItem($data, $categoryId, $rowNum);
    }
    
    /**
     * Insert new inventory item with opening stock handling
     * Creates batch and movement records for FIFO tracking
     */
    private function insertItem($data, $categoryId, $rowNum) {
        $openingStock = (float)($data['current_stock'] ?? 0);
        $unitCost = (float)($data['unit_cost'] ?? 0);
        
        // Start transaction for data integrity
        $this->db->beginTransaction();
        
        try {
            // 1. Insert the inventory item
            $query = "INSERT INTO inventory_items 
                      (company_id, item_code, item_name, description, category_id, unit_of_measure, 
                       current_stock, reorder_level, max_stock_level, unit_cost, unit_price, is_active)
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $params = [
                $this->companyId,
                $data['item_code'],
                $data['item_name'],
                $data['description'] ?? null,
                $categoryId,
                $data['unit_of_measure'],
                $openingStock,
                (float)($data['reorder_level'] ?? 0),
                !empty($data['max_stock_level']) ? (float)$data['max_stock_level'] : null,
                $unitCost,
                (float)($data['unit_price'] ?? 0),
                isset($data['is_active']) ? (int)$data['is_active'] : 1
            ];
            
            $result = $this->db->prepareExecute($query, $params);
            
            if (!$result) {
                throw new Exception('Failed to insert item');
            }
            
            $itemId = $this->db->getLastInsertId();
            
            // 2. If opening stock > 0, create batch and movement records
            if ($openingStock > 0) {
                $today = date('Y-m-d');
                
                // Create opening batch for FIFO tracking
                $batchQuery = "INSERT INTO inventory_batches 
                              (company_id, item_id, batch_number, quantity_initial, quantity_remaining, 
                               unit_cost, received_date, is_active)
                              VALUES (?, ?, ?, ?, ?, ?, ?, 1)";
                
                $batchNumber = 'OPENING-' . $data['item_code'];
                
                $batchResult = $this->db->prepareExecute($batchQuery, [
                    $this->companyId,
                    $itemId,
                    $batchNumber,
                    $openingStock,
                    $openingStock,
                    $unitCost,
                    $today
                ]);
                
                if (!$batchResult) {
                    throw new Exception('Failed to create opening batch');
                }
                
                // Create stock movement for audit trail
                $movementQuery = "INSERT INTO stock_movements 
                                 (company_id, item_id, movement_type, reference_type, 
                                  quantity_change, balance_after, unit_cost, notes, created_at)
                                 VALUES (?, ?, 'adjustment', 'opening_balance', ?, ?, ?, ?, NOW())";
                
                $movementResult = $this->db->prepareExecute($movementQuery, [
                    $this->companyId,
                    $itemId,
                    $openingStock,
                    $openingStock,
                    $unitCost,
                    'Opening balance from import - ' . date('Y-m-d H:i:s')
                ]);
                
                if (!$movementResult) {
                    throw new Exception('Failed to create stock movement');
                }
            }
            
            $this->db->commit();
            return true;
            
        } catch (Exception $e) {
            $this->db->rollback();
            $this->errors[] = ['row' => $rowNum, 'error' => $e->getMessage()];
            return false;
        }
    }
    
    /**
     * Update existing inventory item
     */
    private function updateItem($itemId, $data, $categoryId) {
        $query = "UPDATE inventory_items SET 
                  item_name = ?, description = ?, category_id = ?, unit_of_measure = ?,
                  reorder_level = ?, max_stock_level = ?, unit_cost = ?, unit_price = ?, is_active = ?
                  WHERE id = ? AND company_id = ?";
        
        $params = [
            $data['item_name'],
            $data['description'] ?? null,
            $categoryId,
            $data['unit_of_measure'],
            (float)($data['reorder_level'] ?? 0),
            !empty($data['max_stock_level']) ? (float)$data['max_stock_level'] : null,
            (float)($data['unit_cost'] ?? 0),
            (float)($data['unit_price'] ?? 0),
            isset($data['is_active']) ? (int)$data['is_active'] : 1,
            $itemId,
            $this->companyId
        ];
        
        return $this->db->prepareExecute($query, $params);
    }
    
    /**
     * Create new category
     */
    private function createCategory($name) {
        $query = "INSERT INTO inventory_categories (company_id, category_name, is_active) VALUES (?, ?, 1)";
        if ($this->db->prepareExecute($query, [$this->companyId, $name])) {
            return $this->db->getLastInsertId();
        }
        return null;
    }
    
    /**
     * Get errors
     */
    public function getErrors() {
        return $this->errors;
    }
    
    /**
     * Export existing items to CSV
     */
    public function exportToCSV() {
        $query = "SELECT ii.item_code, ii.item_name, ii.description, 
                         ic.category_name, ii.unit_of_measure, ii.current_stock,
                         ii.reorder_level, ii.max_stock_level, ii.unit_cost, ii.unit_price, ii.is_active
                  FROM inventory_items ii
                  LEFT JOIN inventory_categories ic ON ii.category_id = ic.id
                  WHERE ii.company_id = ?
                  ORDER BY ii.item_name";
        
        $stmt = $this->db->prepareSelect($query, [$this->companyId]);
        $items = $stmt ? $stmt->fetchAll() : [];
        
        $output = fopen('php://temp', 'r+');
        
        // BOM for Excel UTF-8
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Headers
        $columns = self::getTemplateColumns();
        fputcsv($output, array_column($columns, 'header'));
        
        // Data rows
        foreach ($items as $item) {
            fputcsv($output, [
                $item['item_code'],
                $item['item_name'],
                $item['description'],
                $item['category_name'],
                $item['unit_of_measure'],
                $item['current_stock'],
                $item['reorder_level'],
                $item['max_stock_level'],
                $item['unit_cost'],
                $item['unit_price'],
                $item['is_active']
            ]);
        }
        
        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);
        
        return $csv;
    }
}
