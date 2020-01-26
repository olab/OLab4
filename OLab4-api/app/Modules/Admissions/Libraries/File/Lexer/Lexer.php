<?php
/**
 * Lexer.php
 *
 * @author Scott Gibson <scott.gibson@queensu.ca>
 */

namespace Entrada\Modules\Admissions\Libraries\File\Lexer;

use Entrada\Modules\Admissions\Libraries\File\Schema\Schema;

class Lexer
{
    private $schema;

    public function __construct(Schema $schema) {
        $this->schema = $schema;
    }

    public function setSchema(Schema $schema) {
        $this->schema = $schema;
    }

    public function getSchema() {
        return $this->schema;
    }

    /**
     * @param string $string
     * @return Token[]
     */
    public function analyze($string)  {
        $tokens = [];

        foreach($this->schema->getFields() as $field) {
            $value = trim(substr($string, $field->position(), $field->length()));
            $tokens[] = new Token($field->id(), $value);
        }

        return $tokens;
    }
}