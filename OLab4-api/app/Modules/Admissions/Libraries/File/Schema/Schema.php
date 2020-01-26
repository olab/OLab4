<?php
/**
 * Schema.php
 *
 * @author Scott Gibson <scott.gibson@queensu.ca>
 */

namespace Entrada\Modules\Admissions\Libraries\File\Schema;

class Schema
{
    /**
     * @var Field[]
     */
    private $fields = [];

    public function __construct($json_filename) {
        $definitions = json_decode(file_get_contents($json_filename), true);
        $this->hydrate($definitions);
    }

    public function hydrate(array $definitions) {
        foreach($definitions as $definition) {
            $this->defineField($definition['id'], $definition['name'], $definition['position'], $definition['length']);
        }
    }

    /**
     * @return Field[]
     */
    public function getFields() {
        return $this->fields;
    }

    /**
     * @param string $id
     * @return Field
     */
    public function findField ($id) {
        foreach($this->fields as $field) {
            if($field->id() === $id) {
                return $field;
            }
        }

        return null;
    }

    /**
     * @param Field $field
     */
    public function addField(Field $field) {
        $this->fields[] = $field;
    }

    /**
     * @param string $id
     * @param string $name
     * @param int $position
     * @param int $length
     */
    public function defineField($id, $name, $position, $length = 1){
        $this->addField(new Field($id, $name, $position, $length));
    }
}