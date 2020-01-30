<?php

namespace Entrada\Modules\Admissions\Models\Entrada;

class QUEXACAData extends QUEXData
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'quex_aca_data';
    protected $primaryKey = 'quex_aca_data_id';
    protected static $ref_num = ['year', 'reference_number','sequence_number'];
    protected $fillable = [
        'year','reference_number','sequence_number','from_date','to_date',
        'institution_name','faculty_name','degree_type','degree_code','degree_date',
        'degree_diploma','institution_length','institution_value','institution_gpa',
        'exception_noted','verification_code','additional_details1',
        'additional_details2','additional_details3','additional_details4','additional_details5',
        'additional_details6','additional_details7','additional_details8','additional_details9',
        'additional_details10','additional_details11','additional_details12','filler', 'file_number'
    ];


    public $date_fields = [
        "to_date" => "yyyymm",
        "from_date" => "yyyymm",
        "degree_date" => "yyyymm"
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
