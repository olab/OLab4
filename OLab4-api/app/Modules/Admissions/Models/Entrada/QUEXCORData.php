<?php

namespace Entrada\Modules\Admissions\Models\Entrada;

class QUEXCORData extends QUEXData
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'quex_cor_data';
    protected $primaryKey = 'quex_cor_data_id';
    protected static $ref_num = ['year', 'reference_number'];

    protected $fillable = [
        'year','reference_number','corrob_sequence','corrob_title','corrob_given_name','corrob_surname',
        'corrob_address1','corrob_address2','corrob_city','corrob_province','corrob_country','corrob_postal',
        'corrob_zip','corrob_phone','corrob_phone_ext','corrob_comment','filler', 'file_number'
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
