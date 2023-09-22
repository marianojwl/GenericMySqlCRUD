<?php
namespace marianojwl\GenericMySqlCRUD {
    class Table {
        protected $dbName;
        protected $name;
        protected $columns;
        protected $conn;
        protected $records;

        public function __construct($dbName, $name) {
            $this->dbName = $dbName;
            $this->name = $name;
            $this->conn = new \mysqli("localhost","root","",$this->dbName);
            $this->columns = $this->getAllColumns();
            
        }
        public function getPrimaryKeyFieldName() {
            foreach($this->columns as $col)
                if($col->isPrimaryKey())
                    return $col->getField();
            return null;
        }
        public function addColumn(Column $column) {
            $this->columns[] = $column;
        }
        private function getAllColumns() {
            $objs = [];
            $sql = "SHOW COLUMNS FROM ".$this->name;
            $result = $this->conn->query($sql);
            while($row = $result->fetch_assoc())
                $objs[] = new Column($row["Field"], $row["Type"], $row["Null"], $row["Key"], $row["Default"], $row["Extra"], null, null);
            return $objs;
        }

        private function query($sql) {
            $this->records = [];
            $result = $this->conn->query($sql);
            while($row = $result->fetch_assoc())
                $this->records[] = $row;
        }

        public function getRecordsHTML() {
            $this->query("SELECT * FROM ".$this->name);
            $html = '';
            $html .= '<table class="table table-dark">' . PHP_EOL;
            $html .= '<thead>' . PHP_EOL;
            $html .= '<tr>' . PHP_EOL;
            foreach($this->columns as $col) {
                $html .= '<td>';
                $html .= $col->getField();
                $html .= '</td>' . PHP_EOL;
            }
            $html .= '<td>Edit</td>' . PHP_EOL;
            $html .= '<td>Del.</td>' . PHP_EOL;
            $html .= '</tr>' . PHP_EOL;
            $html .= '</thead>' . PHP_EOL;
            $html .= '<tbody>' . PHP_EOL;
            foreach($this->records as $record) {
                $html .= '<tr>' . PHP_EOL;
                foreach($this->columns as $col) {
                    $html .= '<td>';
                    $html .= $record[$col->getField()];
                    $html .= '</td>' . PHP_EOL;
                }
                $html .= '<td><a href="?action=edit&id='.$record[ $this->getPrimaryKeyFieldName() ].'">Edit</a></td>' . PHP_EOL;
                $html .= '<td><a href="?action=delete&id='.$record[ $this->getPrimaryKeyFieldName() ].'">Del.</a></td>' . PHP_EOL;
                $html .= '</tr>' . PHP_EOL;
            }
            $html .= '</tbody>' . PHP_EOL;
            $html .= '</table>' . PHP_EOL;
            return $html;
        }
        public function renderRecords() {
            echo $this->getRecordsHTML();
        }

        public function getFormHTML() {
            
            $html = '<form method="POST" action="?action=create">';
            $html .= '<table class="table table-dark">' . PHP_EOL;
            $html .= '<thead>' . PHP_EOL;
            $html .= '<tr>' . PHP_EOL;
            $html .= '<td>Field</td>' . PHP_EOL;
            $html .= '<td>Value</td>' . PHP_EOL;
            $html .= '</tr>' . PHP_EOL;
            $html .= '</thead>' . PHP_EOL;
            $html .= '<tbody>' . PHP_EOL;

            foreach($this->columns as $col) {
                $html .= '<tr>' . PHP_EOL;
                $html .= '<td>'.$col->getField().'</td>' . PHP_EOL;
                $html .= '<td>'.$col->getFormField().'</td>' . PHP_EOL;   
                $html .= '</tr>' . PHP_EOL;
            }
            $html .= '</tbody>' . PHP_EOL;
            $html .= '</table>';
            $html .= '<input type="submit" value="Save" />' . PHP_EOL;
            $html .= '</form>';
            
            return $html;
        }
        public function renderForm() {
            echo $this->getFormHTML();
        }
        /**
         * Get the value of dbName
         */
        public function getDbName()
        {
                return $this->dbName;
        }
        /**
         * Get the value of name
         */
        public function getName()
        {
                return $this->name;
        }
    }


}