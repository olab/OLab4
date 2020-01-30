<?php
/**
 * Filter.php
 *
 * @author Scott Gibson <scott.gibson@queensu.ca>
 */

namespace Entrada\Modules\Admissions\Libraries\Applicant\Filter;

use Entrada\Modules\Admissions\Libraries\Applicant\Filter\Type\Boolean;
use Entrada\Modules\Admissions\Libraries\Applicant\Filter\Type\Numeric;
use Entrada\Modules\Admissions\Libraries\Applicant\Filter\Type\Text;
use Entrada\Modules\Admissions\Libraries\Applicant\Filter\Type\Type;

class Filter implements FilterInterface
{
    private $name;
    private $type;
    private $key;

    public function __construct(string $name, Type $type, string $key) {
        $this->name = $name;
        $this->type = $type;
        $this->key = $key;
    }

    public function compare($expectedValue, $actualValue) : bool {
        return $this->type->compare($expectedValue, $actualValue);
    }

    public static function createBooleanFilter(string $name, string $key) : FilterInterface {
        return new static($name, new Boolean(), $key);
    }

    public static function createNumericFilter(string $name, string $key) : FilterInterface {
        return new static($name, new Numeric(), $key);
    }

    public static function createTextFilter(string $name, string $key) : FilterInterface {
        return new static($name, new Text(), $key);
    }

    public function getName() : string {
        return $this->name;
    }

    public function getType() : Type {
        return $this->type;
    }

    public function getKey() : string {
        return $this->key;
    }
}