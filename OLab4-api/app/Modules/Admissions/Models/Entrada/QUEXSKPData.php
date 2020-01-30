<?php

namespace Entrada\Modules\Admissions\Models\Entrada;

class QUEXSKPData extends QUEXData
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'quex_skp_data';
    protected $primaryKey = 'quex_skp_data_id';
    protected static $ref_num = ['year', 'reference_number'];

    protected $fillable = [
        'year','reference_number','sketch_sequence','sketch_category','sub_sequence',
        'time_of_life','hours','learned','responsibilities','verifier_number','filler', 'file_number'
    ];


    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The storage format of the model's date columns.
     *
     * @var string
     */
    protected $dateFormat = 'U';

}
