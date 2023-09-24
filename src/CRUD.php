<?php
namespace marianojwl\GenericMySqlCRUD {
    class CRUD {
        protected $database;

        public function __construct(string $host, string $user, string $password , string $name, array $ignore = []) {
            $this->database = new Database("localhost","root","","muvidb", ["afiches_alta"] );
        }

        
    }
}