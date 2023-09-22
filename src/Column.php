<?php
namespace marianojwl\GenericMySqlCRUD {
    class Column {
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
        public function __construct($Field, $Type, $Null, $Key, $Default, $Extra, $ForeignKeyTable, $ForeignKeyField) {
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
            $html .= ' value="'.$value.'"';
            
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