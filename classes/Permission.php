<?php
class Permission
{
    public $id;
    public $permission_name;
    public $permission_code;
    public $description;
    public $created_at;
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
        $query = "SELECT id, permission_name, permission_code, description, created_at 
                  FROM permissions WHERE id = ?";
        $result = $this->db->prepareSelect($query, [$id]);
        if ($result && $row = $result->fetch()) {
            foreach ($row as $key => $value) {
                if (property_exists($this, $key)) {
                    $this->$key = $value;
                }
            }
        }
    }

    public function create()
    {
        if (empty($this->permission_name) || empty($this->permission_code)) {
            return false;
        }
        if ($this->checkCodeExists($this->permission_code)) {
            return false;
        }

        $query = "INSERT INTO permissions (permission_name, permission_code, description) 
                  VALUES (?, ?, ?)";
        $success = $this->db->prepareExecute($query, [
            $this->permission_name,
            $this->permission_code,
            $this->description ?? ''
        ]);

        if ($success) {
            $this->id = $this->db->getLastInsertId();
        }
        return $success;
    }

    public function update()
    {
        if (!$this->id) return false;

        if ($this->checkCodeExists($this->permission_code, $this->id)) {
            return false;
        }

        $query = "UPDATE permissions 
                  SET permission_name = ?, permission_code = ?, description = ? 
                  WHERE id = ?";
        return $this->db->prepareExecute($query, [
            $this->permission_name,
            $this->permission_code,
            $this->description ?? '',
            $this->id
        ]);
    }

    public function delete()
    {
        if (!$this->id) return false;

        $query = "DELETE FROM permissions WHERE id = ?";
        return $this->db->prepareExecute($query, [$this->id]);
    }

    public function all()
    {
        $query = "SELECT id, permission_name, permission_code, description, created_at 
                  FROM permissions 
                  ORDER BY created_at DESC";
        $result = $this->db->prepareSelect($query);
        $permissions = [];
        while ($row = $result->fetch()) {
            $permissions[] = $row;
        }
        return $permissions;
    }

    private function checkCodeExists($code, $excludeId = null)
    {
        $query = "SELECT id FROM permissions WHERE permission_code = ?";
        $params = [$code];
        if ($excludeId) {
            $query .= " AND id != ?";
            $params[] = $excludeId;
        }
        $result = $this->db->prepareSelect($query, $params);
        return $result && $result->rowCount() > 0;
    }

    public function getData()
    {
        return [
            'id'              => $this->id,
            'permission_name' => $this->permission_name,
            'permission_code' => $this->permission_code,
            'description'     => $this->description,
            'created_at'      => $this->created_at
        ];
    }
}
?>