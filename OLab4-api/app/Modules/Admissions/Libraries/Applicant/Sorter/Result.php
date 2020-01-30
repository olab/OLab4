<?php
/**
 * Result.php
 *
 * @author Scott Gibson <scott.gibson@queensu.ca>
 */

namespace Entrada\Modules\Admissions\Libraries\Applicant\Sorter;


class Result
{
    private $matched = [];
    private $unmatched = [];

    public function __construct(array $matched, array $unmatched) {
        $this->matched = $matched;
        $this->unmatched = $unmatched;
    }

    public function getMatched() {
        return $this->matched;
    }

    public function getUnmatched() {
        return $this->unmatched;
    }
}