<?php
/**
 * Token.php
 *
 * @author Scott Gibson <scott.gibson@queensu.ca>
 */

namespace Entrada\Modules\Admissions\Libraries\File\Lexer;


class Token
{
    private $id;
    private $value;

    /**
     * Token constructor.
     * @param string $id
     * @param string $value
     */
    public function __construct($id, $value) {
        $this->id = $id;
        $this->value = $value;
    }

    public function id() { return $this->id; }
    public function value() { return $this->value; }
}