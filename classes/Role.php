<?php
class Role
{
    public $id;
    public $role_name;
    public $description;
    public $is_system_role = false;
    public $created_at;
    private $db;

    public function __construct($id = null){
        $this->db = new Database();
        if($id){
            $this->loadById($id);
        }
    }

    private function loadById($id){
        $query = "SELECT id, role_name, description, is_system_role, created_at 
                  FROM roles WHERE id = ?";
        $result = $this->db->prepareSelect($query, [$id]);
        if($result && $row = $result->fetch()){
            $this->id = $row['id'];
            $this->role_name = $row['role_name'];
            $this->description = $row['description'];
            $this->is_system_role = (bool)$row['is_system_role'];
            $this->created_at = $row['created_at'];
        }
    }

    public function create(){
        if(empty($this->role_name)) return false;

        if($this->checkRoleNameExists($this->role_name)) return false;

        $query = "INSERT INTO roles (role_name, description, is_system_role) VALUES (?, ?, ?)";
        $success = $this->db->prepareExecute($query, [
            $this->role_name,
            $this->description ?? '',
            (int)$this->is_system_role
        ]);

        if($success){
            $this->id = $this->db->getLastInsertId();
        }
        return $success;
    }

    public function update(){
        if(!$this->id) return false;

        if($this->checkRoleNameExists($this->role_name, $this->id)) return false;

        $query = "UPDATE roles SET role_name = ?, description = ?, is_system_role = ? WHERE id = ?";
        return $this->db->prepareExecute($query, [
            $this->role_name,
            $this->description ?? '',
            (int)$this->is_system_role,
            $this->id
        ]);
    }

    public function delete(){
        if(!$this->id || $this->is_system_role) return false;

        $query = "DELETE FROM roles WHERE id = ?";
        return $this->db->prepareExecute($query, [$this->id]);
    }

    public function all(){
        $query = "SELECT id, role_name, description, is_system_role, created_at 
                  FROM roles ORDER BY created_at DESC";
        $result = $this->db->prepareSelect($query);
        $roles = [];
        while($row = $result->fetch()){
            $roles[] = $row;
        }
        return $roles;
    }

    private function checkRoleNameExists($role_name, $excludeId = null){
        $query = "SELECT id FROM roles WHERE role_name = ?";
        $params = [$role_name];
        if($excludeId){
            $query .= " AND id != ?";
            $params[] = $excludeId;
        }
        $result = $this->db->prepareSelect($query, $params);
        return $result && $result->rowCount() > 0;
    }

    // Helper to get single role (used in AJAX get)
    public function getData(){
        return [
            'id'            => $this->id,
            'role_name'     => $this->role_name,
            'description'   => $this->description,
            'is_system_role'=> $this->is_system_role
        ];
    }
}
?>