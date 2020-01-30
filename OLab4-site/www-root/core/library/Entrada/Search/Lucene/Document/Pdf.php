<?php
class Entrada_Search_Lucene_Document_Pdf extends Entrada_Search_Lucene_Document_Abstract {

    public function getBody()
    {
        $output = null;
        exec('pdftotext '.$this->_filename.' -', $output);
        return implode(' ', $output);
    }


    /**
     * Load Pptx document from a file
     *
     * @param string  $fileName
     * @param boolean $storeContent
     * @return Zend_Search_Lucene_Document_Pptx
     */
    public static function loadPdfFile($fileName, $storeContent = false)
    {
        return new Entrada_Search_Lucene_Document_Pdf($fileName, $storeContent);
    }
}