<?php
require_once 'Zend/Search/Lucene/Document/OpenXml.php';
require_once 'Zend/Search/Lucene/Document/Pptx.php';
require_once 'Entrada/Search/Lucene/Document/Abstract.php';


class Entrada_Search_Lucene_Document
{
    protected static $_fileMap = array(
        'pdf' => array(
            'class' => 'Entrada_Search_Lucene_Document_Pdf',
            'factoryMethod' => 'loadPdfFile',
        ),
        'pptx' => array(
            'class' => 'Zend_Search_Lucene_Document_Pptx',
            'factoryMethod' => 'loadPptxFile',
        ),
        'docx' => array(
            'class' => 'Zend_Search_Lucene_Document_Docx',
            'factoryMethod' => 'loadDocxFile',
        ),
        'xlsx' => array(
            'class' => 'Zend_Search_Lucene_Document_Xlsx',
            'factoryMethod' => 'loadXlsxFile',
        ),
    );

    /**
     * @static
     * @param $filename
     * @param $type
     * @return Entrada_Search_Lucene_Document_Abstract
     */
    public static function factory($filename, $type)
    {
        $handler = self::getClassName($type);

        if (is_null($handler) || !self::isFileExists($filename)) {
            return null;
        }

        $classname = $handler['class'];
        $factoryMethod = $handler['factoryMethod'];
        $document = call_user_func($classname.'::'.$factoryMethod, $filename);
        //$document = new {$classname}::$factoryMethod($filename);
        return $document;
    }

    public static function getClassName($type)
    {
        if (isset(self::$_fileMap[$type])) {
            return self::$_fileMap[$type];
        } else {
            return null;
        }
    }

    public static function isFileExists($filename)
    {
        return file_exists($filename);
    }
}