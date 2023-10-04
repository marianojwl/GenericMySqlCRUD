<?php
namespace marianojwl\GenericMySqlCRUD {
    class Column {
        protected $conn;
        protected $table;
        //protected $tableName;
        protected $dbName;
        protected $Field;
        protected $Type;
        protected $Null;
        protected $Key;
        protected $Default;
        protected $Extra;
        protected $ForeignKeyTable;
        protected $ForeignKeyField;
        protected $valueWrapper;
        protected $listedValueWrapper;

        public function isPrimaryKey() {
            return $this->Key == "PRI";
        }

        // Constructor
        public function __construct($conn, Table $table, $dbName, $Field, $Type, $Null, $Key, $Default, $Extra, $ForeignKeyTable, $ForeignKeyField) {
            $this->conn = $conn;
            $this->table = $table;
            $this->dbName = $dbName;
            $this->Field = $Field;
            $this->Type = $Type;
            $this->Null = $Null;
            $this->Key = $Key;
            $this->Default = $Default;
            $this->Extra = $Extra;
            $this->ForeignKeyTable = $ForeignKeyTable;
            $this->ForeignKeyField = $ForeignKeyField;
            //$this->valueWrapper = '{{value}}';
        }

        public function getExpressionForQuery($post = null) {
            if($this->isPrimaryKey() || $this->Extra == 'on update current_timestamp()' || $this->Extra == 'DEFAULT_GENERATED on update CURRENT_TIMESTAMP')
                return null;
            else {
                if($post === null)
                    return $this->getField();
                else {
                    switch($this->Type) {
                        case "tinyint(1)":
                            if(empty($post[$this->Field]))
                                return "'0'";
                            else
                                return "'".$this->conn->real_escape_string($post[$this->getField()])."'";
                            break;
                        default:
                            $val = $this->conn->real_escape_string($post[$this->getField()]??"");
                            if( empty($val) && $this->getDefault() ) {
                                switch($this->getDefault()) {
                                    case "current_timestamp()":
                                    case "CURRENT_TIMESTAMP":
                                        return "CURRENT_TIMESTAMP";
                                        break;
                                    case "NULL":
                                        return "NULL";
                                        break;
                                    default:
                                        return "'".$this->getDefault()."'";
                                    break;
                                }
                            } else {
                                return "'".$val."'";
                            }
                            break;
                    }
                    
                }
            } 
        }

        public function getFormField($value = ""){
            if($this->ForeignKeyTable && $this->ForeignKeyField) {
                $table = $this->table->db()->getTable($this->ForeignKeyTable);
                $html = '<select';
                $html .= ' name="'.$this->Field.'"';
                $html .= '>';
                $html .= $table->getSelectOptions($value);
                $html .= '</select>';
                return $html;
            }
            @list($type,$size) = explode("(",$this->Type);
            $size = substr($size,0,-1);

            if($this->Type == "tinyint(1)")
                $type = "bool";

            if($type == "enum") {
                $html  = '<select';
                $html .= ' name="'.$this->Field.'"';
                $html .= '>'.PHP_EOL;
                $html .= '<option>-</option>'.PHP_EOL;
                $html .= implode(PHP_EOL,
                    array_map(function($e) {
                        return '<option value="' . substr($e, 1, -1) . '">' . substr($e, 1, -1) . '</option>'.PHP_EOL;
                    },
                    explode(",", $size) )
                );
                $html .= '</select>';
                return $html;
            }


            
            $html = '';
            switch($type) {
                case "text":
                case "json":
                case "tinytext":
                case "longtext":
                    $html  .= '<textarea';
                    $html .= ' name="'.$this->Field.'"';
                    $html .= '>';
                    $html .= $value;
                    $html .= '</textarea>';
                    return $html;
                    break;
                case "int":
                case "tinyint":
                    $html .= '<input ';
                    $html .= ' type="number"'; 
                    $html .= ' max="'.(10 * ( (int)$size ) - 1).'"';
                    break;
                case "varchar":
                    $html .= '<input ';
                    $html .= ' type="text"';
                    $html .= ' maxlength="'.$size.'"';
                    break;
                case "date":
                    $html .= '<input ';
                    $html .= ' type="date"';
                    $html .= ' maxlength="'.$size.'"';
                    break;
                case "bool":
                    $html .= '<input ';
                    $html .= ' type="checkbox"';
                    if($value)
                        $html .= ' checked="checked"';
                    $value = 1;
                    break;
                default:
                    $html .= '<input ';
                    $html .= ' type="text"';
                    $html .= ' maxlength="'.$size.'"';
                    break;
            }

            $html .= ' name="'.$this->Field.'"';
            $html .= ' value="'.$value.'"';
            if($this->isPrimaryKey() )
                $html .= ' readonly';
            if($this->Extra == 'on update current_timestamp()')
                $html .= ' disabled="disabled"';
            $html .= ' />';

            return $html;
        }
        // Getters
        public function table() : Table {
            return $this->table;
        }
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
        public function wrapValue($value) : string {
            if(empty( $this->valueWrapper ))
                return $value??"";
            else
                return str_replace('{{value}}', $value??"", $this->valueWrapper);
        }
        public function wrapListedValue($value) : string {
            if($value === null)
                return "";
            if(empty( $this->listedValueWrapper ))
                return $value;
            else
                return str_replace('{{value}}', $value, $this->listedValueWrapper);
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
        /**
         * Get the value of valueWrapper
         */
        public function getValueWrapper()
        {
                return $this->valueWrapper;
        }

        /**
         * Set the value of valueWrapper
         */
        public function setValueWrapper($valueWrapper): self
        {
                $this->valueWrapper = $valueWrapper;

                return $this;
        }
        /**
         * Get the value of listedValueWrapper
         */
        public function getListedValueWrapper()
        {
                return $this->listedValueWrapper;
        }

        /**
         * Set the value of listedValueWrapper
         */
        public function setListedValueWrapper($listedValueWrapper): self
        {
                $this->listedValueWrapper = $listedValueWrapper;

                return $this;
        }
    }


}
