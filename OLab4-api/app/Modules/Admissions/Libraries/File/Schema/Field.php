<?php
/**
 * Field.php
 *
 * @author Scott Gibson <scott.gibson@queensu.ca>
 */

namespace Entrada\Modules\Admissions\Libraries\File\Schema;

class Field
{
    private $id;
    private $name;
    private $position;
    private $length;

    /**
     * Field constructor.
     * @param string $id
     * @param string $name
     * @param int $position
     * @param int $length
     */
    public function __construct($id, $name, $position, $length = 1) {
        $this->id = $id;
        $this->name = $name;
        $this->position = $position;
        $this->length = $length;
    }

    public function id() { return $this->id; }
    public function name() { return $this->name; }
    public function position() { return $this->position; }
    public function length() { return $this->length; }
}