<?php
namespace marianojwl\GenericMySqlCRUD {
    class Column {
        protected $conn;
        protected $tableName;
        protected $dbName;
        protected $Field;
        protected $Type;
        protected $Null;
        protected $Key;
        protected $Default;
        protected $Extra;
        protected $ForeignKeyTable;
        protected $ForeignKeyField;

        public function isPrimaryKey() {
            return $this->Key == "PRI";
        }

        // Constructor
        public function __construct($conn, $tableName, $dbName, $Field, $Type, $Null, $Key, $Default, $Extra, $ForeignKeyTable, $ForeignKeyField) {
            $this->conn = $conn;
            $this->tableName = $tableName;
            $this->dbName = $dbName;
            $this->Field = $Field;
            $this->Type = $Type;
            $this->Null = $Null;
            $this->Key = $Key;
            $this->Default = $Default;
            $this->Extra = $Extra;
            $this->ForeignKeyTable = $ForeignKeyTable;
            $this->ForeignKeyField = $ForeignKeyField;
        }


        public function getFormField($value = ""){
            if($this->ForeignKeyTable && $this->ForeignKeyField) {
                $table = new Table($this->conn, $this->ForeignKeyTable, $this->dbName);
                $html = '<select';
                $html .= ' name="'.$this->Field.'"';
                $html .= '>';
                $html .= $table->getSelectOptions($value);
                $html .= '</select>';
                return $html;
            }
            if($this->Type == "longtext") {
                $html  = '<textarea';
                $html .= ' name="'.$this->Field.'"';
                $html .= '>';
                $html .= $value;
                $html .= '</textarea>';
                return $html;
            }
            @list($type,$size) = explode("(",$this->Type);
            $size = substr($size,0,-1);

            $html = '<input ';
            $formType = "text";
            switch($type) {
                case "date":
                    $formType = "date";
                    break;
                case "int":
                    $formType = "number";
                    break;
            }
            switch($formType) {
                case "text":
                case "password":
                    $html .= ' maxlength="'.$size.'"';
                    break;
                case "number":
                    $html .= ' max="'.(10 ** $size - 1).'"';
                    break;
            }

            $html .= ' type="'.$formType.'"';
            $html .= ' name="'.$this->Field.'"';
            $html .= ' value="'.$value.'"';
            if($this->isPrimaryKey())
                $html .= ' disabled="disabled"';
            $html .= ' />';

            return $html;
        }
        // Getters
        public function getField() {
            return $this->Field;
        }

        public function getType() {
            return $this->Type;
        }

        public function getNull() {
            return $this->Null;
        }

        public function getKey() {
            return $this->Key;
        }

        public function getDefault() {
            return $this->Default;
        }

        public function getExtra() {
            return $this->Extra;
        }

        public function getForeignKeyTable() {
            return $this->ForeignKeyTable;
        }

        public function getForeignKeyField() {
            return $this->ForeignKeyField;
        }

        // Setters
        public function setField($Field) {
            $this->Field = $Field;
        }

        public function setType($Type) {
            $this->Type = $Type;
        }

        public function setNull($Null) {
            $this->Null = $Null;
        }

        public function setKey($Key) {
            $this->Key = $Key;
        }

        public function setDefault($Default) {
            $this->Default = $Default;
        }

        public function setExtra($Extra) {
            $this->Extra = $Extra;
        }

        public function setForeignKeyTable($ForeignKeyTable) {
            $this->ForeignKeyTable = $ForeignKeyTable;
        }

        public function setForeignKeyField($ForeignKeyField) {
            $this->ForeignKeyField = $ForeignKeyField;
        }
    }
}
