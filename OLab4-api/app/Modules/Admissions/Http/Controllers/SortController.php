<?php
/**
 * SortController.php
 *
 * @author Scott Gibson <scott.gibson@queensu.ca>
 */

namespace Entrada\Modules\Admissions\Http\Controllers;

use Entrada\Http\Controllers\Controller;
use Entrada\Modules\Admissions\Libraries\Applicant\Filter\Filter;
use Entrada\Modules\Admissions\Libraries\Applicant\Sorter\Sorter;
use Entrada\Modules\Admissions\Models\Entrada\Applicant;
use Entrada\Modules\Admissions\Models\Entrada\PoolFilter;
use Illuminate\Http\Request;

class SortController extends Controller
{
    private $filters = [];

    public function __construct() {
        $this->filters = [
            Filter::createNumericFilter('GPA Total', 'cumulative_avg'),
            Filter::createNumericFilter('GPA Avg (2Y)', 'average_last_2_years'),
            Filter::createNumericFilter('MCAT', 'mcat_total'),
            Filter::createNumericFilter('BBFL', 'bbfl'),
            Filter::createNumericFilter('PSBB', 'psbb'),
            Filter::createNumericFilter('CPBS', 'cpbs'),
            Filter::createNumericFilter('CARS', 'cars'),
            Filter::createBooleanFilter('Reference Letters', 'has_reference_letters'),
            Filter::createBooleanFilter('Sketch Review', 'has_sketch_review'),
        ];
    }

    public function sort(Request $request) {
        $this->validate($request, [
            'pool_id' => 'int|required',
            'subpool' => 'string|required'
        ]);

        $poolId = $request->get('pool_id');
        $subpool = $request->get('subpool');
        $gradIndicator = $request->get('subpool') === 'ug' ? '' : 'G';

        $filterSet = PoolFilter::where('pool_id', $request->get('pool_id'))
                        ->where('subpool', $subpool)
                        ->first();

        if(is_null($filterSet)) {
            return response([
                'error' => 'This filter set could not be found.'
            ]);
        }

        $applicants = Applicant::where('pool_id', $poolId)
            ->where('grad_indicator', $gradIndicator)
            ->get();

        if(empty($applicants)) {
            return response([
                'info' => 'There are no applicants in the pool/subpool combination, sort skipped.'
            ]);
        }

        $sorter = new Sorter($this->filters);
        $result = $sorter->sort($applicants->all(), $filterSet->toArray());

        return response([
            'matched' => $result->getMatched(),
            'unmatched' => $result->getUnmatched()
        ]);
    }
}