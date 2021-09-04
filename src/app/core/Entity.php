<?php

namespace app\core;

class Entity
{
    private $__collection;
    private $__name;

    private $_generatedFields = [];


    public $fields = [];

    public static function fromJSON($json)
    {
        $m = new self();
        foreach ($json as $key => $value) {
            switch ($key) {
                case "__entityName":
                    $m->__name = $m->snakeToCamel($value);
                    break;
                case "__CollectionName":
                    $m->__collection = $value;
                    break;
                default:
                    $m->fields[] = ["name" => $key, "type" => $value];
            }
        }
        return $m;
    }

    private function __construct()
    {
    }


    public function getCollectionConst()
    {
        $name = $this->snakeToCamel($this->getCollection());
        return "CollectionName" . $name;
    }

    public function getResource()
    {
        return $this->getName() . 'Resource';
    }

    public function getDepositor()
    {
        return $this->getName() . 'Depositor';
    }

    public function getRepository()
    {
        return $this->getName() . 'Repository';
    }

    public function getServiceStruct()
    {
        return lcfirst($this->getServiceInterface());
    }

    public function getServiceInterface()
    {
        return $this->getName() . 'Service';
//        "{{VALIDATION_FIELDS}}",
    }

    public function getValidationRules() {
        $rules = $this->getValidation();
        if (count($rules) == 0) return 'return nil';
        return str_replace('{{VALIDATION_FIELDS}}',implode("\n\t\t", $rules),"return validation.ValidateStruct(&c,{{VALIDATION_FIELDS}})");
    }
    public function getValidation() {
        $fields = $this->getCreateFields();
        $validation = [];
        foreach ($fields as $field) {
            $parts = explode("\t",$field);
            $validation[] = "validation.Field(&c.{$parts[0]}, validation.Required)";
        }
        return $validation;
    }
    public function getSearchField()
    {
        if (count($this->fields) == 1) return $this->fields[0]['name'];
        foreach ($this->fields as $field) {
            if ($field['name'] == "_id") continue;
            if (in_array($field['type'], ['interface{}', 'string'])) {
                return $field['name'];
            }
        }
        return "_id";
    }

    public function getCreateFields()
    {
        $fields = $this->getGeneratedFields();
        foreach ($fields as $i => $field) {
            if (strpos($field, "_id") !== false) {
                unset($fields[$i]);
                continue;
            }

            $c = substr($field, 0, 1);
            if ($c == "_" || ucfirst($c) != $c) {
                unset($fields[$i]);
                continue;
            }
        }
        return $fields;
    }

    private function snakeToCamel($data)
    {
        if ($data == "_id") return "ID";
        $parts = explode('_', $data);
        $output = [];
        foreach ($parts as $part) {
            $output[] = ucfirst($part);
        }
        return implode('', $output);
    }

    /**
     * @return mixed
     */
    public function getCollection()
    {
        return $this->__collection;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->__name;
    }

    public function generateAssignment() {
        $fields = $this->getCreateFields();
        $result = [];
        foreach ($fields as $field) {
            $parts = explode("\t",$field);
            $result[] = "m.{$parts[0]} = f.{$parts[0]}";
        }
        return $result;
    }
    /**
     * @return array
     */
    public function getGeneratedFields()
    {
        if (empty($this->_generatedFields)) {
            $result = [];
            foreach ($this->fields as $field) {
                if ($field['name'] == '_isDocumented') continue;
                $name = $this->snakeToCamel($field['name']);
                $type = $field['type'];
                $bsonName = $field['name'] == "_id" ? "_id,omitempty" : $field['name'];
                $jsonName = $field['name'] == "_id" ? "id,omitempty" : $field['name'];
                $tag = str_replace(['{{bname}}', '{{jname}}'], [$bsonName, $jsonName], '`bson:"{{bname}}" json:"{{jname}}"`');
                $result[] = "$name\t$type\t$tag";
            }
            $result[] = "_isDocumented bool";
            $this->_generatedFields = $result;
        }
        return $this->_generatedFields;
    }
}