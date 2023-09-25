<?php
namespace marianojwl\GenericMySqlCRUD {
    class Database {
        protected $conn;
        protected $name;
        protected $tables;
        protected $ignoredTables;

        /**
         * CONSTRUCTOR
         * @param mixed $host Usually 'localhost'
         * @param string $user Username.
         * @param string $password Password.
         * @param string $name Database name.
         * @param array $ignore Array containing table names to ignore.
         */
        public function __construct(string $host, string $user, string $password , string $name, array $ignore = []) {
            $this->conn = new \mysqli($host, $user, $password , $name);
            $this->name = $name;
            $this->tables = [];
            $this->ignoredTables = [];
            $tableNames = $this->getTableNames();
            foreach($tableNames as $tn)
                if(!in_array($tn,$ignore)) 
                    $this->tables[] = new Table($this->conn, $tn, $name);
        }

        public function getTables() {
            return $this->tables;
        }
        public function getTable(string $name) : Table | null {
            if(empty($name))
                return null;
            foreach($this->tables as $table)
                if($table->getName() == $name)
                    return $table;
            return null;
        }
        public function getTableNames() {
            $names = [];
            $sql = "SHOW TABLES";
            $result = $this->conn->query($sql);
            while($row = $result->fetch_row())
                $names[] = $row[0]; 
            return $names;            
        }
        public function getReferencialTables(Table $selectedTable) {
            $objs = [];
            foreach($this->tables as $table)
                if($table->getName() != $selectedTable->getName()) {
                    $column = $table->getColumnReferencingTable( $selectedTable->getName() );
                    if($column !== null)
                        $objs[] = $table;
                }

            return $objs;
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