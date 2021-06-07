<?php
namespace Shiningw\PdnsBundle\lib;

class Database
{

    protected $dns;
    private $dbh;
    public $table = 'isp';

    public function __construct($dns = null)
    {

        $this->dns = isset($dns) ? $dns : 'sqlite:/etc/powerdns/pdns.sqlite';

        try {
            $this->dbh = new \PDO("{$this->dns}");
            $this->dbh->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        } catch (\PDOException $e) {

            echo $e->getMessage();
        }

        if (!$this->dbExists($this->table)) {
            $this->createDb($this->defaultTable());
        }
        $this->dnstable = "records";
        $this->table = "isp";

    }
    public function setTable($table)
    {
        $this->table = $table;
    }
    public function setDnsTable($table)
    {
        $this->dnstable = $table;
    }

    protected function defaultTable()
    {

        return array(
            'CREATE TABLE isp (
                id                    INTEGER PRIMARY KEY,
                record_id             INTEGER DEFAULT NULL,
                name                  VARCHAR(255) DEFAULT NULL,
                isp                   INTEGER DEFAULT 1,
                isp_name              VARCHAR(255) DEFAULT NULL,
              );',
        );
    }

    public function createDb($queries = array())
    {

        if (!is_array($queries)) {

            throw new Exception('query is not correct');
        }

        foreach ($queries as $query) {
            $this->dbh->exec($query);
        }
    }

    public function fetch($rid)
    {

        $query = "SELECT * FROM {$this->table} WHERE record_id = :record_id";
        $st = $this->dbh->prepare($query);

        try {
            $st->execute(array(':record_id' => $key));
            $r = $st->fetchObject();
            return $r;
        } catch (PDOException $e) {

            echo $e->getMessage();
        }

        return false;
    }

    public function updateRecord($key, $value)
    {
        $query = "UPDATE {$this->table} SET isp = :isp, isp_name = :isp_name WHERE record_id = :record_id";

        $st = $this->dbh->prepare($query);
        $arr = array(
            ':isp' => $value['isp'],
            ':isp_name' => $value['isp_name'],
            ':record_id' => $key,
        );

        try {
            $st->execute($arr);
        } catch (PDOException $e) {

            echo $e->getMessage() . "\n";
        }
    }
    public function getRecordId($name, $content, $type)
    {
        $query = "SELECT id FROM {$this->dnstable} WHERE name = :name AND content = :content AND type = :type";
        $st = $this->dbh->prepare($query);
        $paras = array(':name' => $name, ':content' => $content, ':type' => $type);
        $st->execute($paras);
        $result = $st->fetchColumn();
        return $result;
    }
    public function insertRecord($value)
    {
        $query = "INSERT INTO {$this->table}(record_id,name,isp,isp_name) values(:record_id,:name,:isp,:isp_name)";
        $st = $this->dbh->prepare($query);
        $arr = array(
            ':record_id' => $value['record_id'],
            ':name' => $value['name'],
            ':isp' => $value['isp'],
            ':isp_name' => $value['isp_name'],
        );

        try {
            $st->execute($arr);
        } catch (PDOException $e) {

            echo $e->getMessage();
        }
    }

    public function delete($rid = null)
    {

        $query = sprintf("DELETE FROM %s WHERE record_id = '%s'", $this->table, $rid);

        try {

            $result = $this->dbh->exec("$query");
        } catch (Exception $e) {
            //echo "failed to delete entry\n";
        }

        if (isset($result)) {
            return $result;
        }

        return false;
    }

    protected function loadAll()
    {

        if (!$result = $this->dbh->query("SELECT * FROM {$this->table}")->fetchAll(PDO::FETCH_ASSOC)) {

            throw new Exception("Failed to get result from database.\n");
            return false;
        }

        return $result;
    }

    public function dbExists($table)
    {

        $sql = "SELECT 1 FROM {$table} LIMIT 1";

        try {

            $stmt = $this->dbh->query($sql);
        } catch (Exception $e) {

            echo "no such table exist!\n";
        }

        if (isset($stmt)) {

            return true;
        }

        return false;
    }

}
