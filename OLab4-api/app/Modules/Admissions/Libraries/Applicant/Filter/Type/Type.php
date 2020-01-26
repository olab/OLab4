<?php
/**
 * Type.php
 *
 * @author Scott Gibson <scott.gibson@queensu.ca>
 */

namespace Entrada\Modules\Admissions\Libraries\Applicant\Filter\Type;

interface Type
{
    public function compare($value1, $value2) : bool;
}