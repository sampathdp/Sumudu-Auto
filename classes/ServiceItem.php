<?php
/**
 * ServiceItem Class
 * Manages individual items/packages within a service job
 */
class ServiceItem
{
    public $id;
    public $company_id;
    public $service_id;
    public $item_type;      // 'package', 'custom', 'inventory'
    public $related_id;     // package_id or inventory_item_id
    public $item_name;
    public $description;
    public $unit_price;
    public $quantity;
    public $total_price;
    public $created_at;
    public $updated_at;

    private $db;

    public function __construct($id = null)
    {
        $this->db = new Database();
        if ($id) {
            $this->loadById($id);
        }
    }

    private function loadById($id)
    {
        $query = "SELECT * FROM service_items WHERE id = ?";
        $stmt = $this->db->prepareSelect($query, [$id]);
        if ($stmt && $row = $stmt->fetch()) {
            $this->populateFromArray($row);
        }
    }

    private function populateFromArray($row)
    {
        foreach ($row as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
    }

    /**
     * Create a new service item
     */
    public function create()
    {
        if (empty($this->company_id) || empty($this->service_id) || empty($this->item_name)) {
            return false;
        }

        // Calculate total_price
        $this->total_price = $this->unit_price * $this->quantity;

        $query = "INSERT INTO service_items 
            (company_id, service_id, item_type, related_id, item_name, description, unit_price, quantity, total_price)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $success = $this->db->prepareExecute($query, [
            $this->company_id,
            $this->service_id,
            $this->item_type ?? 'package',
            $this->related_id,
            $this->item_name,
            $this->description,
            $this->unit_price ?? 0,
            $this->quantity ?? 1,
            $this->total_price
        ]);

        if ($success) {
            $this->id = $this->db->getLastInsertId();
        }
        return $success;
    }

    /**
     * Update an existing service item
     */
    public function update()
    {
        if (!$this->id) {
            return false;
        }

        // Recalculate total_price
        $this->total_price = $this->unit_price * $this->quantity;

        $query = "UPDATE service_items SET
            item_type = ?, related_id = ?, item_name = ?, description = ?,
            unit_price = ?, quantity = ?, total_price = ?
            WHERE id = ?";

        return $this->db->prepareExecute($query, [
            $this->item_type,
            $this->related_id,
            $this->item_name,
            $this->description,
            $this->unit_price,
            $this->quantity,
            $this->total_price,
            $this->id
        ]);
    }

    /**
     * Delete a service item
     */
    public function delete()
    {
        if (!$this->id) {
            return false;
        }
        $query = "DELETE FROM service_items WHERE id = ?";
        return $this->db->prepareExecute($query, [$this->id]);
    }

    /**
     * Get all items for a service
     */
    public static function getByServiceId($serviceId)
    {
        $db = new Database();
        $query = "SELECT si.*, sp.package_name as package_name_ref
                  FROM service_items si
                  LEFT JOIN service_packages sp ON si.related_id = sp.id AND si.item_type = 'package'
                  WHERE si.service_id = ?
                  ORDER BY si.created_at ASC";
        $stmt = $db->prepareSelect($query, [$serviceId]);
        return $stmt ? $stmt->fetchAll() : [];
    }

    /**
     * Add a package to a service
     */
    public static function addPackageToService($serviceId, $packageId, $companyId, $quantity = 1, $customPrice = null)
    {
        $package = new ServicePackage($packageId);
        if (!$package->id) {
            return false;
        }

        $item = new self();
        $item->company_id = $companyId;
        $item->service_id = $serviceId;
        $item->item_type = 'package';
        $item->related_id = $packageId;
        $item->item_name = $package->package_name;
        $item->description = $package->description;
        $item->unit_price = $customPrice !== null ? $customPrice : $package->base_price;
        $item->quantity = $quantity;

        return $item->create();
    }

    /**
     * Calculate total for a service
     */
    public static function calculateServiceTotal($serviceId)
    {
        $db = new Database();
        $query = "SELECT COALESCE(SUM(total_price), 0) as total FROM service_items WHERE service_id = ?";
        $stmt = $db->prepareSelect($query, [$serviceId]);
        if ($stmt && $row = $stmt->fetch()) {
            return (float) $row['total'];
        }
        return 0;
    }

    /**
     * Delete all items for a service
     */
    public static function deleteByServiceId($serviceId)
    {
        $db = new Database();
        return $db->prepareExecute("DELETE FROM service_items WHERE service_id = ?", [$serviceId]);
    }
}
