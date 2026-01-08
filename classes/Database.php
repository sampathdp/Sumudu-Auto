<?php
class Database
{
    private $host;
    private $db_name;
    private $username;
    private $password;

    public $pdo;  // Changed from $DB_CON to $pdo for consistency with modern PDO usage

    // Automatically determine if environment is production
    private $isProduction;

    public function __construct()
    {
        // Use configuration from config.php
        $this->host = DB_HOST;
        $this->db_name = DB_NAME;
        $this->username = DB_USER;
        $this->password = DB_PASS;

        // Detect environment
        $this->isProduction = !$this->isLocalEnvironment();

        // Enable detailed error reporting only for local
        if (!$this->isProduction) {
            error_reporting(E_ALL);
            ini_set('display_errors', 1);
        } else {
            error_reporting(0);
            ini_set('display_errors', 0);
        }

        // Create connection
        $dsn = "mysql:host={$this->host};dbname={$this->db_name};charset=" . DB_CHARSET;

        $options = [
            PDO::ATTR_ERRMODE            => $this->isProduction ? PDO::ERRMODE_SILENT : PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false, // Real prepared statements
        ];

        try {
            $this->pdo = new PDO($dsn, $this->username, $this->password, $options);
        } catch (PDOException $e) {
            $errorMsg = "Failed to connect to MySQL: " . $e->getMessage();
            if ($this->isProduction) {
                error_log($errorMsg);
                die('Database connection error. Please try again later.');
            } else {
                die($errorMsg);
            }
        }
    }

    private function isLocalEnvironment()
    {
        $serverName = $_SERVER['SERVER_NAME'] ?? 'localhost';
        $localHosts = ['localhost', '127.0.0.1', '::1'];

        foreach ($localHosts as $localHost) {
            if (stripos($serverName, $localHost) !== false) {
                return true;
            }
        }
        return false;
    }

    /**
     * Optimized prepared statement execution for SELECT queries.
     * Returns a PDOStatement (fetchable) or false on failure.
     */
    public function prepareSelect($query, $params = [])
    {
        return $this->executePrepared($query, $params, true);
    }

    /**
     * Optimized prepared statement execution for INSERT/UPDATE/DELETE.
     * Returns true on success, false on failure.
     */
    public function prepareExecute($query, $params = [])
    {
        $result = $this->executePrepared($query, $params, false);
        return $result !== false;
    }

    /**
     * Internal method to handle prepared statement execution.
     *
     * @param string $query
     * @param array  $params
     * @param bool   $returnStatement  If true, returns PDOStatement; else returns true on success
     * @return PDOStatement|true|false
     */
    public $lastErrorMessage = '';

    /**
     * Internal method to handle prepared statement execution.
     *
     * @param string $query
     * @param array  $params
     * @param bool   $returnStatement  If true, returns PDOStatement; else returns true on success
     * @return PDOStatement|true|false
     */
    private function executePrepared($query, $params = [], $returnStatement = false)
    {
        try {
            $stmt = $this->pdo->prepare($query);
            if (!$stmt) {
                $error = 'Prepare failed.';
                $this->lastErrorMessage = $error;
                if ($this->isProduction) {
                    error_log($error);
                } else {
                    die($error);
                }
                return false;
            }

            $stmt->execute($params);

            if ($returnStatement) {
                return $stmt; // Caller can fetch rows
            }

            return true;
        } catch (PDOException $e) {
            $error = 'Query error: ' . $e->getMessage();
            $this->lastErrorMessage = $error;
            if ($this->isProduction) {
                error_log($error . ' | Query: ' . $query);
            } else {
                 // If we are seeing JSON 'Failed to create...', and logic reaches here, 
                 // it means we are in 'production' mode or die() is not stopping execution (impossible).
                 // So we must be in production mode logic path.
                 // But strictly following existing logic:
                die($error . ' | Query: ' . $query);
            }
            return false;
        }
    }

    /**
     * Legacy method for backward compatibility (deprecated).
     * Avoid using this; prefer prepareSelect/prepareExecute.
     */
    public function readQuery($query)
    {
        if (!$this->isProduction) {
            trigger_error('readQuery is deprecated. Use prepareSelect or prepareExecute for security.', E_USER_DEPRECATED);
        }

        try {
            $stmt = $this->pdo->query($query);
            return $stmt; // Returns PDOStatement
        } catch (PDOException $e) {
            $error = 'Database query error: ' . $e->getMessage();
            if ($this->isProduction) {
                error_log($error);
                die('Database error occurred.');
            } else {
                die($error);
            }
        }
    }

    /**
     * Escape string safely using PDO (not needed with prepared statements, but kept for legacy).
     */
    public function escapeString($string)
    {
        return substr($this->pdo->quote($string), 1, -1); // Removes surrounding quotes
    }

    /**
     * Get last insert ID
     */
    public function getLastInsertId()
    {
        return $this->pdo->lastInsertId();
    }

    /**
     * Get the PDO connection instance
     */
    public function getConnection()
    {
        return $this->pdo;
    }

    /**
     * Get the last database error (useful in production when ERRMODE_SILENT)
     */
    public function getLastError()
    {
        $error = $this->pdo->errorInfo();
        return $error[2] ?? 'Unknown database error';
    }

    /**
     * Begin a transaction
     */
    public function beginTransaction()
    {
        return $this->pdo->beginTransaction();
    }

    /**
     * Commit a transaction
     */
    public function commit()
    {
        return $this->pdo->commit();
    }

    /**
     * Roll back a transaction
     */
    public function rollBack()
    {
        return $this->pdo->rollBack();
    }
}