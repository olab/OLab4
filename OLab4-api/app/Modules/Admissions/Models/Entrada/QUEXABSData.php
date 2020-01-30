<?php

namespace Entrada\Modules\Admissions\Models\Entrada;

class QUEXABSData extends QUEXData
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'quex_abs_data';
    protected $primaryKey = 'quex_abs_data_id';
    protected static $ref_num = ['year', 'reference_number'];

    protected $fillable = [
        'year', 'reference_number', 'sequence','category', 'from_date','to_date','sketch_sequence_number',
        'description', 'location','filler', 'file_number'
    ];

    public $date_fields = [
        "to_date" => "yyyymm",
        "from_date" => "yyyymm"
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
