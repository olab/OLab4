<?php
/**
 * Text.php
 *
 * @author Scott Gibson <scott.gibson@queensu.ca>
 */

namespace Entrada\Modules\Admissions\Libraries\Applicant\Filter\Type;


class Text implements Type
{
    public function compare($value1, $value2) : bool {
        return $value1 === $value2;
    }
}