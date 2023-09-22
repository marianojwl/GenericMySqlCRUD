<?php
namespace marianojwl\GenericMySqlCRUD {
    class CRUD {
        protected $dbName;
        protected $name;
        protected $columns;

        public function __construct() {
            $this->columns = [];
        }
    }
}