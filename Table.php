<?php
namespace marianojwl\GenericMySqlCRUD {

    use Exception;

    class Table {
        protected $db;
        //protected $dbName;
        protected $name;
        protected $columns;
        protected $conn;
        protected $records;
        protected $primaryKey;
        protected $formValues;
        protected $tagClass;
        protected $customActions;

        public function __construct($conn, string $name, Database $db) {
            $this->db = $db;
            $this->name = $name;
            $this->conn = $conn;
            $this->tagClass = '';
            $this->customActions = [];
            $this->columns = $this->getAllColumns();
            foreach($this->columns as $col)
            if($col->isPrimaryKey())
                $this->primaryKey = $col->getField();
        }

        public function getColumnReferencingTable(string $tableName) {
            foreach($this->columns as $column)
                if($column->getForeignKeyTable() == $tableName)
                    return $column;
            return null;
        }

        public function column($columnName) : Column | null {
            foreach($this->columns as $column)
                if($column->getField() == $columnName)
                    return $column;
            return null;
        }


        public function customAction(string $action) : self {
            $this->customActions[] = $action;
            return $this;
        }
        public function getTotalRecords() {
            $r = $this->conn->query("SELECT COUNT(*) FROM ".$this->name);
            $row = $r->fetch_array();
            return $row[0];
        }
        public function setTagClass(string $class) : self {
            $this->tagClass = $class;
            return $this;
        }
        public function getPrimaryKey() {
            return $this->primaryKey;
        }
        public function showInfo() {
            $html = '<table class="'.$this->tagClass.'">';
            $html .= '<thead>';
            $html .= '<tr><th><a href="?table='.$this->name.'">'.$this->getName().'</a></th></tr>' . PHP_EOL;
            $html .= '</thead>';
            $html .= '<tbody>';
            foreach($this->columns as $column)
                $html .= '<tr><td>'.$column->getField().'</td></tr>' . PHP_EOL;
            $html .= '</tbody>';
            $html .= '<tfoot>';
            $html .= '<tr><th>Total: '.$this->getTotalRecords().' records</th></tr>' . PHP_EOL;
            $html .= '</tfoot>';
            $html .= '</table>' . PHP_EOL;

            return $html;
        }
        public function getSelectOptions($selected = null) {
            $html = '<option>-</option>' . PHP_EOL;
            $result = $this->conn->query("SELECT * FROM ".$this->name);
            while($row = $result->fetch_array()) {
                $html .= '<option value="'.$row[$this->primaryKey].'"';
                if($selected == $row[$this->primaryKey])
                    $html .= ' selected="selected"';
                $html .= '>';
                $html .= $row[1] ;
                //$html .= substr(implode(" - ", $row ),0,40) . "..." ;
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
                TABLE_SCHEMA = '".$this->db->getName()."'
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
                TABLE_SCHEMA = '".$this->db->getName()."' 
                AND TABLE_NAME = '".$this->name."' 
                AND REFERENCED_TABLE_NAME IS NOT NULL
        ) K
        ON 
            C.COLUMN_NAME = K.COLUMN_NAME
        ORDER BY `Order`;";
            $result = $this->conn->query($sql);
            if($result && $result->num_rows) {
                while($row = $result->fetch_assoc())
                $objs[] = new Column($this->conn, $this, $this->db->getName(), $row["Field"], $row["Type"], $row["Null"], $row["Key"], $row["Default"], $row["Extra"], $row["REFERENCED_TABLE_NAME"], $row["REFERENCED_COLUMN_NAME"]);
                return $objs;
            }

            $sql2 = "SHOW COLUMNS FROM ".$this->name;
            $result = $this->conn->query($sql2);
            if($result && $result->num_rows) {
                while($row = $result->fetch_assoc())
                $objs[] = new Column($this->conn, $this, $this->db->getName(), $row["Field"], $row["Type"], $row["Null"], $row["Key"], $row["Default"], $row["Extra"], null, null);
                return $objs;
            }

        }
        

        private function query2($sql) {
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
        public function getExtendedQuery() : string {
            $sql = "SELECT ";
            foreach($this->columns as $col) {
                $sql .=  $this->name . "." . $col->getField() . ", ";
                if($col->getForeignKeyTable() && $col->getForeignKeyField()) {
                    $foreignFieldToShow = $col->table()->db()->table($col->getForeignKeyTable())->getColumns()[1]->getField();
                    $sql .= $col->getForeignKeyTable() . "." . $foreignFieldToShow ." AS " . $col->getForeignKeyTable() . "_" . $foreignFieldToShow. ", ";
                }
                    
            }
            $sql = substr($sql, 0, -2);
            $sql .= " FROM " . $this->name;
            foreach($this->columns as $col) {
                if($col->getForeignKeyTable() && $col->getForeignKeyField()) 
                    $sql .= " LEFT JOIN " . $col->getForeignKeyTable() . " ON " . $col->getForeignKeyTable() . "." . $col->getForeignKeyField() ." = " . $this->name . "." . $col->getField() . " ";
                
            }
            return $sql;
        }
        public function getRecordsHTML() {
            //$this->query2("SELECT * FROM ".$this->name);
            $this->query2( $this->getExtendedQuery() );
            $html = '';
            $html .= '<table class="'.$this->tagClass.'">' . PHP_EOL;
            $html .= '<thead>' . PHP_EOL;
            $html .= '<tr>';
            foreach($this->columns as $col) {
                if($col->getForeignKeyTable() && $col->getForeignKeyField()) {
                    $foreignFieldToShow = $col->table()->db()->table($col->getForeignKeyTable())->getColumns()[1]->getField();
                    $html .= '<th>'.$col->getForeignKeyTable() ."_". $foreignFieldToShow.'</th>' . PHP_EOL;
                } else {
                    $html .= '<th>';
                    $html .= $col->getField();
                    $html .= '</th>';
                }
            }
            foreach($this->customActions as $ca)
                $html .= '<th>'.$ca.'</th>';    
            $html .= '<th>View</th>' ;
            $html .= '<th>Edit</th>' ;
            $html .= '<th>Del.</th>';
            $html .= '</tr>' . PHP_EOL;
            $html .= '</thead>' . PHP_EOL;
            $html .= '<tbody>' . PHP_EOL;
            foreach($this->records as $record) {
                $html .= '<tr>';
                foreach($this->columns as $col) {
                    if($col->getForeignKeyTable() && $col->getForeignKeyField()) {
                        $forCol = $col->table()->db()->table($col->getForeignKeyTable())->getColumns()[1];
                        $foreignFieldToShow = $forCol->getField();
                        $html .= '<td>'. $forCol->wrapListedValue( $record[ $col->getForeignKeyTable() ."_". $foreignFieldToShow ] ) .'</td>' . PHP_EOL;   
                    } else {
                        $html .= '<td>';
                        $html .= $col->wrapListedValue( $record[$col->getField()] );
                        $html .= '</td>';
                    }
                }

                foreach($this->customActions as $ca)
                $html .= '<td><a href="?table='.$this->name.'&action='.$ca.'&id='.$record[ $this->primaryKey ].'">'.$ca.'</a></td>';
                $html .= '<td><a href="?table='.$this->name.'&action=view&id='.$record[ $this->primaryKey ].'">View</a></td>';
                $html .= '<td><a href="?table='.$this->name.'&action=edit&id='.$record[ $this->primaryKey ].'">Edit</a></td>';
                $html .= '<td><a href="?table='.$this->name.'&action=confirmDelete&id='.$record[ $this->primaryKey ].'">Del.</a></td>';
                $html .= '</tr>' . PHP_EOL;
            }
            $html .= '</tbody>' . PHP_EOL;
            $html .= '</table>' . PHP_EOL;
            return $html;
        }
        public function renderRecords() {
            echo $this->getRecordsHTML();
        }
        public function getReferrerRecordSheets(int $keyValue, string $referredTable,Table $rt) {
            $html = '<div class="col">'.PHP_EOL;
            $html .= '<h4>'.$rt->getName().'</h4>'.PHP_EOL;
            $field = $this->getForeignKeyFieldReferring($referredTable, $rt);
            $sql = "SELECT * FROM ".$rt->getName()." WHERE ".$field."='".$keyValue."'";
            $result = $this->query($sql);
            while($row = $result->fetch_assoc()) {
                $html .= '<div class="row">'.PHP_EOL;
                $html .= $this->getRecordSheet($row[$this->primaryKey]).PHP_EOL;
                $html .= '</div><!-- row -->'.PHP_EOL;
            }
            $html .= '</div><!-- col -->'.PHP_EOL;
            return $html;
        }
        public function getForeignKeyFieldReferring(string $referredTable, Table $rt) {
            foreach($rt->columns as $column)
                if($column->getForeignKeyTable() == $referredTable)
                    return $column->getField();
            return null;
        }
        public function getRecordSheet(int $primaryKeyValue) {
            $html = '';
            $sql = $this->getExtendedQuery()." WHERE " . $this->name . "." . $this->primaryKey . "='" . $primaryKeyValue . "'";
            $result = $this->query($sql);
            if($row = $result->fetch_assoc()) {
                $html .= '<table class="'.$this->tagClass.'">' . PHP_EOL;
                $html .= '<thead>' . PHP_EOL;
                $html .= '<tr>' . PHP_EOL;
                $html .= '<th>Field</th>' . PHP_EOL;
                $html .= '<th>Value</th>' . PHP_EOL;
                $html .= '</tr>' . PHP_EOL;
                $html .= '</thead>' . PHP_EOL;
                $html .= '<tbody>' . PHP_EOL;
    
                foreach($this->columns as $col) {

                    if($col->getForeignKeyTable() && $col->getForeignKeyField()) {
                        $foreignFieldToShow = $col->table()->db()->table($col->getForeignKeyTable())->getColumns()[1]->getField();
                        if($row[ $col->getForeignKeyTable() ."_". $foreignFieldToShow ]) {
                            $html .= '<tr>' . PHP_EOL;
                            $html .= '<td>'.$col->getForeignKeyTable() ."_". $foreignFieldToShow.'</td>' . PHP_EOL;
                            $html .= '<td>'. $row[ $col->getForeignKeyTable() ."_". $foreignFieldToShow ] .'</td>' . PHP_EOL;   
                            $html .= '</tr>' . PHP_EOL;
                        }
                    } else {
                        $html .= '<tr>' . PHP_EOL;
                        $html .= '<td>'.$col->getField().'</td>' . PHP_EOL;
                        $html .= '<td>'.$col->wrapValue( $row[ $col->getField() ] ).'</td>' . PHP_EOL;   
                        $html .= '</tr>' . PHP_EOL;
                    }
                }
                $html .= '</tbody>' . PHP_EOL;
                $html .= '</table>';
            }
            
            $html .= '';
            return $html;
        }

        public function getFormHTML( $record = [] ) {
            
            $html = '<form method="POST" action="?table='.$this->name.'&action='.(empty($record)?'insert':'update').'">';
            $html .= '<table class="'.$this->tagClass.'">' . PHP_EOL;
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
        public function getColmunsExpressionForInsertQuery($post = null) {
            return array_filter( 
                array_map(
                    function($c) use ($post) { return $c->getExpressionForQuery($post); },
                    $this->columns) ,
                    function($element) {
                        return $element !== null;
                    } );
        }
        private function query($sql) {
            try {
                return $this->conn->query($sql);
            } catch(Exception $e) {
                echo 'Error: ' . $e->getMessage();
            }
        }
        public function insert($post = null) {
            if($post === null)
                $post = $_POST;
            $sql = "INSERT INTO ".$this->name." ";
            $sql .= "(";
            $sql .= implode(", ", $this->getColmunsExpressionForInsertQuery());
            $sql .= ") VALUES (";
            $sql .= implode(", ", $this->getColmunsExpressionForInsertQuery($post));
            $sql .= ")";
            //echo $sql;

            $this->query($sql);
        }

        public function update() {
            $keyValue = (int) $_POST[$this->primaryKey];
            $sql = "UPDATE ".$this->name." SET ";
            $sql .= implode(", ", array_filter( array_map(function($column) {
                if( !( $column->isPrimaryKey() || $column->getExtra() == 'on update current_timestamp()' ) )
                    return $column->getExpressionForQuery() . "=" . $column->getExpressionForQuery($_POST);
            }, $this->columns) , function($elemntToFilrer) { return $elemntToFilrer !== null; } ) );
            $sql .= " WHERE ".$this->primaryKey."='".$keyValue."'";
            //echo $sql;

            $this->query($sql);
        }
        public function delete() {
            $keyValue = (int) $_GET[$this->primaryKey];
            $sql = "DELETE FROM ".$this->name." WHERE ".$this->primaryKey."='".$keyValue."'";
            $this->query($sql);
        }
        public function confirmDelete() {
            $keyValue = (int) $_GET[$this->primaryKey];
            $html = '';
            $html .= '<div><a class="btn btn-primary" href="?table='.$this->name.'&action=delete&'.$this->getPrimaryKey().'='. $keyValue .'">Confirm Delete</a></div>';
            $html .= $this->getRecordSheet($keyValue);
            return $html;
        }
        public function renderForm($formValues = []) {
            echo $this->getFormHTML( $formValues );
        }
        /**
         * Get the value of dbName
         */
        public function db() : Database {
            return $this->db;
        }
        public function getDbName()
        {
                return $this->db->getName();
        }
        /**
         * Get the value of name
         */
        public function getName()
        {
                return $this->name;
        }
        public function getColumns() {
            return $this->columns;
        }
    }


}