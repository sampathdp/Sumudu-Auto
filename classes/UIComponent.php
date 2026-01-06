<?php
class UIComponent
{
    private $db;

    public function __construct()
    {
        $this->db = new Database();
    }

    /**
     * Get all components grouped by category
     * @param bool $activeOnly
     * @return array
     */
    public function getAll($activeOnly = true)
    {
        $sql = "SELECT * FROM ui_components";
        if ($activeOnly) {
            $sql .= " WHERE is_active = 1";
        }
        $sql .= " ORDER BY category, name";
        
        $stmt = $this->db->prepareSelect($sql, []);
        return $stmt ? $stmt->fetchAll() : [];
    }

    /**
     * Get a single component by ID
     * @param int $id
     * @return array|false
     */
    public function getById($id)
    {
        $stmt = $this->db->prepareSelect("SELECT * FROM ui_components WHERE id = ?", [$id]);
        return $stmt ? $stmt->fetch() : false;
    }

    /**
     * Get a single component by Key
     * @param string $key
     * @return array|false
     */
    public function getByKey($key)
    {
        $stmt = $this->db->prepareSelect("SELECT * FROM ui_components WHERE component_key = ?", [$key]);
        return $stmt ? $stmt->fetch() : false;
    }

    /**
     * Create a new component
     * @return int|false ID of new component or false
     */
    public function create($data)
    {
        $sql = "INSERT INTO ui_components (component_key, name, category, icon, description, is_active) 
                VALUES (?, ?, ?, ?, ?, ?)";
        $params = [
            $data['component_key'],
            $data['name'],
            $data['category'],
            $data['icon'] ?? 'fa-cog',
            $data['description'] ?? null,
            isset($data['is_active']) ? (int)$data['is_active'] : 1
        ];
        
        if ($this->db->prepareExecute($sql, $params)) {
            // Retrieve the last inserted ID
             $stmt = $this->db->prepareSelect("SELECT LAST_INSERT_ID() as id", []);
             $res = $stmt ? $stmt->fetch() : false;
             return $res ? $res['id'] : true; 
             // Note: Database class might not return ID easily depending on implementation. 
             // Assuming return type of prepareExecute or manual fetch.
             // Usually getLastId() is a method on DB class. If not, this is a fallback.
        }
        return false;
    }

    /**
     * Update a component
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function update($id, $data)
    {
        $sql = "UPDATE ui_components SET name = ?, category = ?, icon = ?, description = ?, is_active = ? 
                WHERE id = ?";
        $params = [
            $data['name'],
            $data['category'],
            $data['icon'],
            $data['description'],
            (int)$data['is_active'],
            $id
        ];
        return $this->db->prepareExecute($sql, $params);
    }

    /**
     * Delete a component
     * @param int $id
     * @return bool
     */
    public function delete($id)
    {
        return $this->db->prepareExecute("DELETE FROM ui_components WHERE id = ?", [$id]);
    }
}
?>
