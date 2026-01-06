<?php
class Page
{
    public $id;
    public $page_name;
    public $page_route;
    public $page_category;
    public $description;
    public $icon;
    public $display_order;
    public $is_active;
    public $parent_page_id;
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
        $query = "SELECT * FROM pages WHERE id = ?";
        $result = $this->db->prepareSelect($query, [$id]);

        if ($result && $row = $result->fetch(PDO::FETCH_ASSOC)) {
            foreach ($row as $key => $value) {
                if (property_exists($this, $key)) {
                    $this->$key = $value;
                }
            }
        }
    }

    private function routeExists($route, $excludeId = null)
    {
        $query = "SELECT id FROM pages WHERE page_route = ?";
        $params = [$route];

        if ($excludeId) {
            $query .= " AND id != ?";
            $params[] = $excludeId;
        }

        $res = $this->db->prepareSelect($query, $params);
        return $res && $res->fetch(PDO::FETCH_ASSOC);
    }

    private function hasChildren($id)
    {
        $query = "SELECT id FROM pages WHERE parent_page_id = ?";
        $result = $this->db->prepareSelect($query, [$id]);
        return $result && $result->fetch(PDO::FETCH_ASSOC);
    }

    public function create()
    {
        if (empty($this->page_name) || empty($this->page_route)) return false;

        // Prevent duplicate routes
        if ($this->routeExists($this->page_route)) return false;

        // Prevent parent referencing non-existing ID
        if ($this->parent_page_id && !$this->isValidParent($this->parent_page_id)) {
            return false;
        }

        $query = "INSERT INTO pages 
            (page_name, page_route, page_category, description, icon, display_order, is_active, parent_page_id)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

        $success = $this->db->prepareExecute($query, [
            $this->page_name,
            $this->page_route,
            $this->page_category,
            $this->description,
            $this->icon,
            $this->display_order ?? 0,
            $this->is_active ?? 1,
            $this->parent_page_id
        ]);

        if ($success) {
            $this->id = $this->db->getLastInsertId();
        }

        return $success;
    }

    private function isValidParent($parentId)
    {
        $query = "SELECT id FROM pages WHERE id = ?";
        $result = $this->db->prepareSelect($query, [$parentId]);
        return $result && $result->fetch(PDO::FETCH_ASSOC);
    }

    public function update()
    {
        if (!$this->id) return false;

        // Prevent self-parenting
        if ($this->parent_page_id == $this->id) return false;

        // Prevent duplicate route
        if ($this->routeExists($this->page_route, $this->id)) return false;

        // Validate parent exists
        if ($this->parent_page_id && !$this->isValidParent($this->parent_page_id)) return false;

        $query = "UPDATE pages SET
                    page_name = ?, 
                    page_route = ?, 
                    page_category = ?, 
                    description = ?, 
                    icon = ?, 
                    display_order = ?, 
                    is_active = ?, 
                    parent_page_id = ?
                  WHERE id = ?";

        return $this->db->prepareExecute($query, [
            $this->page_name,
            $this->page_route,
            $this->page_category,
            $this->description,
            $this->icon,
            $this->display_order,
            $this->is_active,
            $this->parent_page_id,
            $this->id
        ]);
    }

    public function delete()
    {
        if (!$this->id) return false;

        // Do not delete if it has children
        if ($this->hasChildren($this->id)) return false;

        $query = "DELETE FROM pages WHERE id = ?";
        return $this->db->prepareExecute($query, [$this->id]);
    }

    public function all()
    {
        $query = "SELECT * FROM pages ORDER BY display_order ASC, page_name ASC";
        $result = $this->db->prepareSelect($query);

        $pages = [];
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $pages[] = $row;
        }
        return $pages;
    }

    // For dropdown: id + page_name (without children)
    public function getAllForSelect()
    {
        $query = "SELECT id, page_name FROM pages WHERE is_active = 1 ORDER BY page_name ASC";
        $result = $this->db->prepareSelect($query);

        $list = [];
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $list[] = $row;
        }
        return $list;
    }

    public function getData()
    {
        return [
            'id'             => $this->id,
            'page_name'      => $this->page_name,
            'page_route'     => $this->page_route,
            'page_category'  => $this->page_category,
            'description'    => $this->description,
            'icon'           => $this->icon,
            'display_order'  => $this->display_order,
            'is_active'      => $this->is_active,
            'parent_page_id' => $this->parent_page_id,
        ];
    }
}
?>
