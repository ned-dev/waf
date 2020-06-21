<?php
/**
 * Basic database abstraction layer class for communicating with MySQL 5 database.
 *
 * PHP version 7.1
 *
 * @author Ned Andonov <neoplovdiv@gmail.com>
 */

namespace WAF;
use \PDO;

/**
 * Basic database abstraction layer class for communicating with MySQL 5 database.
 */
class DB
{
    /**
     * Holds PDO instance
     *
     * @var object PDO
     */
    protected $_pdo;
    
    /**
     * Holds server connection details
     * It has keys like 'host', 'db_name', 'port', 'username' and 'password'
     *
     * @var array
     */
    protected $_server_details = [];

    /**
     * Sets details for MySQL server
     *
     * @param string $host     Hostname or IP of the MySQL server
     * @param string $username MySQL connection username
     * @param string $password MySQL connection password
     * @param string $db_name  The name of the database
     * @param int    $port     The port on which MySQL resides on the host
     *
     * @throws Exception
     * @return void
     */
    public function setServerDetails($host, $username, $password, $db_name, $port = 3306)
    {
        // Check connected status
        if (!is_null($this->_pdo)) {
            throw new Exception('Database connections are already active. Disconnect all connections before setting new details!');
        }

        // Check params
        if (empty($host)) {
            throw new Exception('No host specified!');
        }

        if (empty($db_name)) {
            throw new Exception('No database name specified!');
        }

        if (empty($username)) {
            throw new Exception('No username specified!');
        }

        if (!intval($port)) {
            throw new Exception('Invalid port specified!');
        }

        // Set server details
        $this->_server_details = array(
            'host'     => $host,
            'username' => $username,
            'password' => $password,
            'db_name'  => $db_name,
            'port'     => $port
        );
    }

    /**
     * Connect method
     *
     */
    public function connect()
    {
        // Build DSN
        $dsn = "mysql:host={$this->_server_details['host']};" .
        "port={$this->_server_details['port']};" .
        "dbname={$this->_server_details['db_name']}";

        // Connect to DB
        try {

            // Create the object
            $this->_pdo = new PDO($dsn, $this->_server_details['username'], $this->_server_details['password']);

            // Set attributes
            $this->_pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->_pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);

        } catch(PDOException $e) {

            echo $e->getMessage(); exit(1);

        }
    }

    /**
     * Query method
     *
     * @param sting $sql  SQL statement
     * @param array $bind Params to bind
     *
     * @return PDOStatement
     */
    public function query($sql, $bind = [])
    {
        // Connect to database if there is no connection already
        if (!$this->_pdo) {
            $this->connect();
        }

        $sql = trim($sql);
        try {
            $result = $this->_pdo->prepare($sql);
            $result->execute($bind);
            return $result;
        } catch (PDOException $e) {
            echo $e->getMessage(); exit(1);
        }
    }

    /**
     * Insert method
     *
     * @param string $table Table name
     * @param array  $data  Table data
     *
     * @return int
     */
    public function insert($table, $data)
    {
        $fields = $this->_filter($table, $data);

        $sql = 'INSERT INTO ' . $table . ' (' . implode($fields, ', ') . ') VALUES (:' . implode($fields, ', :') . ');';

        $bind = array();
        foreach($fields as $field)
        $bind[":$field"] = $data[$field];

        $this->query($sql, $bind);
        return (int)$this->_pdo->lastInsertId();
    }

    /**
     * getOne method
     *
     * @param string $query SQL query
     * @param array  $bind   Parameters to bind
     *
     * @return string
     */
    public function getOne($query, $bind = [])
    {
        $result = $this->query($query, $bind);
        $result->setFetchMode(PDO::FETCH_ASSOC);
        return $result->fetch();
    }

    /**
     * getAll method
     *
     * @param string $query SQL query
     * @param array  $bind   Parameters to bind
     *
     * @return string
     */
    public function getAll($query, $bind = [])
    {
        $result = $this->query($query, $bind);
        $result->setFetchMode(PDO::FETCH_ASSOC);
        return $result->fetchAll();
    }


    /**
     * Select method
     *
     * @param string $table  Table name
     * @param string $where  Where clause
     * @param array  $bind   Parameters to bind
     * @param string $fields Fields to select
     *
     * @return array
     */
    public function select($table, $where = '', $bind = [], $fields = '*')
    {
        $sql = "SELECT " . $fields . " FROM " . $table;
        if(!empty($where)) {
            $sql .= " WHERE " . $where;
        }
        $sql .= ";";

        $result = $this->query($sql, $bind);
        $result->setFetchMode(PDO::FETCH_ASSOC);

        $rows = array();
        while($row = $result->fetch()) {
            $rows[] = $row;
        }

        return $rows;
    }

    /**
     * Update method
     *
     * @param string $table Table name
     * @param array  $data  Table data
     * @param string $where Table where clause
     * @param array  $bind  Params to bind
     *
     * @return int
     */
    public function update($table, $data, $where, $bind = [])
    {
        // Filter fields data
        $fields = $this->_filter($table, $data);
        $fieldSize = sizeof($fields);

        // Prepare update statement
        $sql = "UPDATE $table SET ";
        for($f = 0; $f < $fieldSize; ++$f) {
            if($f > 0)
            $sql .= ', ';
            $sql .= $fields[$f] . ' = :update_' . $fields[$f];
        }
        $sql .= " WHERE $where;";

        // Prepare update fields
        foreach($fields as $field)
        $bind[":update_$field"] = $data[$field];

        // Execute query
        $result = $this->query($sql, $bind);
        return $result->rowCount();
    }

    /**
     * Delete method
     *
     * @param string $table Table name
     * @param string $where Where statement
     * @param array  $bind  Data to bind
     *
     * @return int Deleted rows count
     */
    public function delete($table, $where, $bind = []) {
        $sql = "DELETE FROM $table WHERE $where;";
        $result = $this->query($sql, $bind);
        return $result->rowCount();
    }

    /**
     * Filter method
     *
     * @param sting $table MySQL table name
     * @param array $data  Input data
     *
     * @return array
     */
    private function _filter($table, $data)
    {
        // Prepare filter
        $sql = 'DESCRIBE ' . $table . ';';
        $key = 'Field';
        if(false !== ($list = $this->query($sql))) {
            $fields = array();
            foreach($list as $record)
            $fields[] = $record[$key];
            return array_values(array_intersect($fields, array_keys($data)));
        }
        return array();
    }
}