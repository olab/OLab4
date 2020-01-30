<?php
/**
 * FilterInterface.php
 *
 * @author Scott Gibson <scott.gibson@queensu.ca>
 */

namespace Entrada\Modules\Admissions\Libraries\Applicant\Filter;

interface FilterInterface
{
    public function compare($expectedValue, $actualValue) : bool;

    public function getName() : string;
    public function getKey() : string;
}