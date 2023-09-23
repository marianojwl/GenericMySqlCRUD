<?php
namespace marianojwl\GenericMySqlCRUD {
    class Table {
        protected $dbName;
        protected $name;
        protected $columns;
        protected $conn;
        protected $records;
        protected $primaryKey;
        protected $formValues;

        public function __construct($conn, $name, $dbName) {
            $this->dbName = $dbName;
            $this->name = $name;
            $this->conn = $conn;
            $this->columns = $this->getAllColumns();
            foreach($this->columns as $col)
            if($col->isPrimaryKey())
                $this->primaryKey = $col->getField();
        }
        public function getPrimaryKey() {
            return $this->primaryKey;
        }
        public function getSelectOptions($selected = null) {
            $html = '';
            $result = $this->conn->query("SELECT * FROM ".$this->name);
            while($row = $result->fetch_assoc()) {
                $html .= '<option value=""';
                if($selected == $row[$this->primaryKey])
                    $html .= ' selected="selected"';
                $html .= '>';
                $html .= implode(" - ", $row);
                $html .= '</option>' . PHP_EOL;
            }

            return $html;
        }
        public function addColumn(Column $column) {
            $this->columns[] = $column;
        }
        private function getAllColumns() {
            $objs = [];
            
            $sql = "SELECT 
            C.ORDINAL_POSITION AS 'Order',
            C.COLUMN_NAME AS 'Field',
            C.COLUMN_TYPE AS 'Type',
            C.IS_NULLABLE AS 'Null',
            C.COLUMN_KEY AS 'Key',
            C.COLUMN_DEFAULT AS 'Default',
            C.EXTRA AS 'Extra',
            K.REFERENCED_TABLE_NAME,
            K.REFERENCED_COLUMN_NAME
        FROM (
            SELECT 
                ORDINAL_POSITION,
                COLUMN_NAME, 
                DATA_TYPE, 
                COLUMN_TYPE, 
                IS_NULLABLE,
                COLUMN_DEFAULT,
                EXTRA,
                COLUMN_KEY
            FROM 
                information_schema.COLUMNS
            WHERE 
                TABLE_SCHEMA = '".$this->dbName."'
                AND TABLE_NAME = '".$this->name."'
        ) C
        LEFT JOIN (
            SELECT 
                COLUMN_NAME, 
                REFERENCED_TABLE_NAME, 
                REFERENCED_COLUMN_NAME
            FROM 
                information_schema.KEY_COLUMN_USAGE
            WHERE 
                TABLE_SCHEMA = '".$this->dbName."' 
                AND TABLE_NAME = '".$this->name."' 
                AND REFERENCED_TABLE_NAME IS NOT NULL
        ) K
        ON 
            C.COLUMN_NAME = K.COLUMN_NAME
        ORDER BY `Order`;";
            $result = $this->conn->query($sql);
            if($result && $result->num_rows) {
                while($row = $result->fetch_assoc())
                $objs[] = new Column($this->conn, $this->name, $this->dbName, $row["Field"], $row["Type"], $row["Null"], $row["Key"], $row["Default"], $row["Extra"], $row["REFERENCED_TABLE_NAME"], $row["REFERENCED_COLUMN_NAME"]);
                return $objs;
            }

            $sql2 = "SHOW COLUMNS FROM ".$this->name;
            $result = $this->conn->query($sql2);
            if($result && $result->num_rows) {
                while($row = $result->fetch_assoc())
                $objs[] = new Column($this->conn, $this->name, $this->dbName, $row["Field"], $row["Type"], $row["Null"], $row["Key"], $row["Default"], $row["Extra"], null, null);
                return $objs;
            }

        }
        

        private function query($sql) {
            $this->records = [];
            $result = $this->conn->query($sql);
            while($row = $result->fetch_assoc())
                $this->records[] = $row;
        }
        public function getRecordByPrimaryKey(int $keyValue) {
            $sql = "SELECT * FROM ".$this->name." WHERE ".$this->primaryKey."='".$keyValue."'";
            $result = $this->conn->query($sql);
            if($row = $result->fetch_assoc())
                return $row;
            else
                return null;
        }

        public function getRecordsHTML() {
            $this->query("SELECT * FROM ".$this->name);
            $html = '';
            $html .= '<table class="table table-dark table-responsive">' . PHP_EOL;
            $html .= '<thead>' . PHP_EOL;
            $html .= '<tr>';
            foreach($this->columns as $col) {
                $html .= '<td>';
                $html .= $col->getField();
                $html .= '</td>';
            }
            $html .= '<td>Edit</td>' ;
            $html .= '<td>Del.</td>';
            $html .= '</tr>' . PHP_EOL;
            $html .= '</thead>' . PHP_EOL;
            $html .= '<tbody>' . PHP_EOL;
            foreach($this->records as $record) {
                $html .= '<tr>';
                foreach($this->columns as $col) {
                    $html .= '<td>';
                    $html .= $record[$col->getField()];
                    $html .= '</td>';
                }
                $html .= '<td><a href="?table='.$this->name.'&action=edit&id='.$record[ $this->primaryKey ].'">Edit</a></td>';
                $html .= '<td><a href="?table='.$this->name.'&action=delete&id='.$record[ $this->primaryKey ].'">Del.</a></td>';
                $html .= '</tr>' . PHP_EOL;
            }
            $html .= '</tbody>' . PHP_EOL;
            $html .= '</table>' . PHP_EOL;
            return $html;
        }
        public function renderRecords() {
            echo $this->getRecordsHTML();
        }

        public function getFormHTML( $record = [] ) {
            
            $html = '<form method="POST" action="?table='.$this->name.'&action='.(empty($record)?'insert':'update').'">';
            $html .= '<table class="table table-dark table-responsive">' . PHP_EOL;
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
                $html .= '<td>'.$col->getFormField( $record[ $col->getField() ] ?? "" ).'</td>' . PHP_EOL;   
                $html .= '</tr>' . PHP_EOL;
            }
            $html .= '</tbody>' . PHP_EOL;
            $html .= '</table>';
            $html .= '<input type="submit" value="Save" class="btn btn-primary" />' . PHP_EOL;
            $html .= '</form>';
            
            return $html;
        }

        public function insert() {
            $sql = "INSERT INTO ".$this->name." ";
            $sql .= "(". implode(", ", array_filter( 
                array_map(
                    function($c) {
                        if($c->isPrimaryKey())
                            return null;
                        else
                            return $c->getField();
                    },
                    $this->columns) ,
                    function($element) {
                        return $element !== null;
                    } )
            ) .")";
            $sql .= " VALUES";
            $sql .= "(". implode(", ", array_filter( 
                array_map(
                    function($c) {
                        if($c->isPrimaryKey())
                            return null;
                        else {
                            $val = $this->conn->real_escape_string($_POST[$c->getField()]);
                            if( empty($val) && $c->getDefault() ) {
                                switch($c->getDefault()) {
                                    case "current_timestamp()":
                                        return "CURRENT_TIMESTAMP";
                                        break;
                                    default:
                                        return "'".$c->getDefault()."'";
                                    break;
                                }
                            } else {
                                return "'".$val."'";
                            }
                        }

                            
                            
                    },
                    $this->columns) ,
                    function($element) {
                        return $element !== null;
                    } )
            ) .")";
            //echo $sql;
            $this->conn->query($sql);
        }
        public function renderForm($formValues = []) {
            echo $this->getFormHTML( $formValues );
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