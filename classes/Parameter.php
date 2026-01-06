<?php
class Parameter
{
    public $id;
    public $parameter_key;
    public $parameter_value = 0;
    private $db;

    public function __construct($db = null)
    {
        $this->db = $db ?? new Database();
    }

    /**
     * Get parameter value by key
     */
    public function get($key)
    {
        $query = "SELECT id, parameter_key, parameter_value FROM parameters WHERE parameter_key = ?";
        $result = $this->db->prepareSelect($query, [$key]);
        if ($result && $row = $result->fetch(PDO::FETCH_ASSOC)) {
            $this->id = $row['id'];
            $this->parameter_key = $row['parameter_key'];
            $this->parameter_value = (int)$row['parameter_value'];
            return $this->parameter_value;
        }
        return null;
    }

    /**
     * Increment parameter value by $by
     * Creates the parameter if it does not exist
     */
    public function increment($key, $by = 1)
    {
        $current = $this->get($key);

        if ($current === null) {
            // Create new parameter
            $query = "INSERT INTO parameters (parameter_key, parameter_value) VALUES (?, ?)";
            $this->db->prepareExecute($query, [$key, $by]);
            $this->id = $this->db->getLastInsertId();
            $this->parameter_key = $key;
            $this->parameter_value = $by;
            return $this->parameter_value;
        }

        // Update existing parameter
        $newValue = $current + $by;
        $query = "UPDATE parameters SET parameter_value = ? WHERE parameter_key = ?";
        $this->db->prepareExecute($query, [$newValue, $key]);
        $this->parameter_value = $newValue;
        return $this->parameter_value;
    }

    /**
     * List all parameters (optional, can be empty)
     */
    public function list()
    {
        $query = "SELECT id, parameter_key, parameter_value FROM parameters ORDER BY parameter_key ASC";
        $result = $this->db->prepareSelect($query);
        $params = [];
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $params[] = [
                'id' => (int)$row['id'],
                'parameter_key' => $row['parameter_key'],
                'parameter_value' => (int)$row['parameter_value']
            ];
        }
        return $params;
    }
}
?>
