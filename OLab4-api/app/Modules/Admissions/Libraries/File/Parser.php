<?php
/**
 * Parser.php
 *
 * @author Scott Gibson <scott.gibson@queensu.ca>
 */

namespace Entrada\Modules\Admissions\Libraries\File;

use Entrada\Modules\Admissions\Libraries\File\Lexer\Lexer;
use Entrada\Modules\Admissions\Libraries\File\Schema\Schema;

class Parser
{
    private $schema;
    private $lexer;

    public function __construct(Schema $schema) {
        $this->schema = $schema;
        $this->lexer = new Lexer($this->schema);
    }

    /**
     * @param string $string
     * @return array
     */
    public function parse($string) {
        $data = [];

        $tokens = $this->lexer->analyze($string);

        foreach($tokens as $token) {
            $field = $this->schema->findField($token->id());
            $data[$field->name()] = $token->value();
        }

        return $data;
    }
}