<?php
abstract class Entrada_Filter_Base {
    protected static $key = "";
    protected static $label = "";
    protected static $sortable = false;
    
    protected $items = array();
    protected $objArray = array();
    
    public function __construct() {
        $this->init();
        $this->objArray[$this->key] = array("label"=> $this->label, "items" => $this->items, "sortable" => $this->sortable);
    }
    
    abstract function init();
    
    public function toArray() {
        return $this->objArray;
    }
    
    public function setKey($key) {
        $this->key = $key;
    }
    
    public function getKey() {
        return $this->key;
    }
    
    public function getLabel() {
        return $this->label;
    }
    
    public function setLabel($label) {
        $this->label = $label;
    }
    
    public function setItems($items) {
        $this->items = $items;
    }
    
    public function getItems() {
        return $this->items;
    }
    
    public function setSortable($sortable) {
        $this->sortable = $sortable;
    }
    
    public function getSortable() {
        return $this->sortable;
    }
}