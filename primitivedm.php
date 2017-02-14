<?php

class PrimitiveDM {

    protected $db;
    protected $driver;
    
    public function __construct(\PDO $pdo, $driver = 'mysql')
    {
        $this->db = $pdo;
        $this->driver = $driver;
    }
    
    public function query($sql, $params = [])
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    protected function escapeIdent($ident)
    {
        switch ($this->driver)
        {
            case 'mysql':
                return "`".str_replace("`", "``", $ident)."`";
            
            default:
                throw new \Exception("You must define escape rules for the driver ($driver)");
        }
    }

    public function findBySql($class, $sql, $params = [])
    {
        return $this->query($sql, $params)->fetchAll(PDO::FETCH_CLASS, $class);
    }

    public function find($class, $id)
    {
        $table = $this->escapeIdent(strtolower(basename($class)));
        $sql = "SELECT * FROM $table WHERE id = ?";
        return $this->query($sql, [$id])->fetchObject($class);
        
    }

    public function delete($object)
    {
        $table = $this->escapeIdent(strtolower(basename(get_class($object))));
        $sql = "DELETE FROM $table WHERE id = ?";
        $this->query($sql, [$object->id]);
    }

    public function save($object)
    {
        $table = $this->escapeIdent(strtolower(basename(get_class($object))));
        $properties = get_object_vars($object);
        $params = array_values($properties);

        if (!empty($object->id))
        {
            $set = '';
            foreach($properties as $name => $value)
            {
                $set .= $this->escapeIdent($name) . " = ?,";
            }
            $set = substr($set, 0, -1);
            $sql = "UPDATE $table SET $set WHERE id = ?";
            $params[] = $object->id;
            $this->query($sql, $params);

        } else {

            $names = '';
            foreach($properties as $name => $value)
            {
                $names .= $this->escapeIdent($name) . ",";
            }
            $names = substr($names, 0, -1);
            $values = str_repeat('?,', count($params) - 1) . '?';
            $sql = "INSERT INTO $table ($names) VALUES ($values)";
            $this->query($sql, $params);
            $object->id = $this->db->lastInsertId();
        }
    }
}
