<?php
/**
 * Sorter.php
 *
 * @author Scott Gibson <scott.gibson@queensu.ca>
 */

namespace Entrada\Modules\Admissions\Libraries\Applicant\Sorter;

use Entrada\Modules\Admissions\Libraries\Applicant\Filter\Filter;
use Entrada\Modules\Admissions\Models\Entrada\Applicant;
use Illuminate\Support\Facades\Log;

class Sorter
{
    /**
     * @var Filter[]
     */
    private $filters = [];

    public function __construct(array $filters = []) {
        $this->filters = $filters;
    }

    public function addFilter(Filter $filter) : void {
        $this->filters[] = $filter;
    }

    public function getFilters() : array {
        return $this->filters;
    }

    /**
     * @param Applicant[] $applicants
     * @param array $filterValues
     * @return Result
     */
    public function sort(array $applicants, array $filterValues) : Result {
        $matchedApplicants = $applicants;

        foreach($this->filters as $filter) {
            $filter_value = $filterValues[$filter->getKey()] ?? null;

            $matchedApplicants = array_filter($matchedApplicants, function(Applicant $applicant) use ($filter, $filter_value) {
                $key = $filter->getKey();
                $value = null;

                if(isset($applicant->$key)) {
                    $value = $applicant->$key;
                }
                else {
                    Log::warning('Warning: Attempting to sort on a missing property: ' . $key);
                }

                return $filter->compare($filter_value, $value);
            });
        }

        $unmatchedApplicants = array_diff($applicants, $matchedApplicants);

        return new Result($matchedApplicants, $unmatchedApplicants);
    }
}