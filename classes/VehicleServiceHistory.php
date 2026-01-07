<?php
/**
 * VehicleServiceHistory Class
 * Manages service history records for vehicles
 */
class VehicleServiceHistory
{
    public $id;
    public $company_id;
    public $vehicle_id;
    public $invoice_id;
    public $service_date;
    public $current_mileage;
    public $next_service_mileage;
    public $next_service_date;
    public $notes;
    public $created_at;

    private $db;

    public function __construct($id = null)
    {
        $this->db = new Database();
        if ($id) {
            $this->loadById($id);
        }
    }

    /**
     * Load record by ID
     */
    public function loadById($id)
    {
        $query = "SELECT * FROM vehicle_service_history WHERE id = ?";
        $stmt = $this->db->prepareSelect($query, [$id]);
        if ($stmt) {
            $row = $stmt->fetch();
            if ($row) {
                $this->populateFromArray($row);
                return true;
            }
        }
        return false;
    }

    /**
     * Populate object from array
     */
    private function populateFromArray($row)
    {
        $this->id = $row['id'];
        $this->company_id = $row['company_id'];
        $this->vehicle_id = $row['vehicle_id'];
        $this->invoice_id = $row['invoice_id'];
        $this->service_date = $row['service_date'];
        $this->current_mileage = $row['current_mileage'];
        $this->next_service_mileage = $row['next_service_mileage'];
        $this->next_service_date = $row['next_service_date'];
        $this->notes = $row['notes'];
        $this->created_at = $row['created_at'];
    }

    /**
     * Create a new service history record
     */
    public function create()
    {
        if (empty($this->company_id) || empty($this->vehicle_id) || empty($this->service_date)) {
            return false;
        }

        $query = "INSERT INTO vehicle_service_history 
                  (company_id, vehicle_id, invoice_id, service_date, current_mileage, next_service_mileage, next_service_date, notes)
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

        $success = $this->db->prepareExecute($query, [
            $this->company_id,
            $this->vehicle_id,
            $this->invoice_id ?: null,
            $this->service_date,
            $this->current_mileage ?: null,
            $this->next_service_mileage ?: null,
            $this->next_service_date ?: null,
            $this->notes ?: null
        ]);

        if ($success) {
            $this->id = $this->db->getLastInsertId();
        }
        return $success;
    }

    /**
     * Get service history for a vehicle
     */
    public function getByVehicle($vehicleId, $companyId = null)
    {
        $query = "SELECT * FROM vehicle_service_history WHERE vehicle_id = ?";
        $params = [$vehicleId];

        if ($companyId) {
            $query .= " AND company_id = ?";
            $params[] = $companyId;
        }

        $query .= " ORDER BY service_date DESC, id DESC";

        $stmt = $this->db->prepareSelect($query, $params);
        return $stmt ? $stmt->fetchAll() : [];
    }

    /**
     * Get the latest service record for a vehicle
     */
    public function getLatestByVehicle($vehicleId, $companyId = null)
    {
        $query = "SELECT * FROM vehicle_service_history WHERE vehicle_id = ?";
        $params = [$vehicleId];

        if ($companyId) {
            $query .= " AND company_id = ?";
            $params[] = $companyId;
        }

        $query .= " ORDER BY service_date DESC, id DESC LIMIT 1";

        $stmt = $this->db->prepareSelect($query, $params);
        if ($stmt) {
            $row = $stmt->fetch();
            if ($row) {
                $this->populateFromArray($row);
                return $row;
            }
        }
        return null;
    }

    /**
     * Delete a record
     */
    public function delete()
    {
        if (!$this->id) {
            return false;
        }
        $query = "DELETE FROM vehicle_service_history WHERE id = ?";
        return $this->db->prepareExecute($query, [$this->id]);
    }
}
?>
