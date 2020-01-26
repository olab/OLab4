<?php
require_once 'Zend/Search/Lucene/Document.php';

abstract class Entrada_Search_Lucene_Document_Abstract extends Zend_Search_Lucene_Document {
    protected $_filename = null;

    public function __construct($fileName, $storeContent) {
        // Store filename
        $this->addField(Zend_Search_Lucene_Field::Text('filename', $fileName, 'UTF-8'));
        $this->_filename = $fileName;

        // Store contents
        if ($storeContent) {
            $this->addField(Zend_Search_Lucene_Field::Text('body', implode(' ', $this->getBody()), 'UTF-8'));
        } else {
            $this->addField(Zend_Search_Lucene_Field::UnStored('body', implode(' ', $this->getBody()), 'UTF-8'));
        }
    }

    public abstract function getBody();
}