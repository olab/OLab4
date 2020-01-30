<?php

namespace Entrada\Modules\Admissions\Models\Entrada;

class QUEXCRSData extends QUEXData
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'quex_crs_data';
    protected $primaryKey = 'quex_crs_data_id';
    protected static $ref_num = ['year', 'reference_number'];
    protected $fillable = [
        'year','reference_number','postsec_sequence_number','year_sequence_number',
        'course_sequence_number','course_number','course_numeric_grade','course_alpha_grade','course_scale',
        'course_length','converted_value','gpa','filler', 'file_number'
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
