<?php

namespace Entrada\Modules\Admissions\Models\Entrada;

class QUEXHOMData extends QUEXData
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'quex_hom_data';
    protected $primaryKey = 'quex_hom_data_id';
    protected static $ref_num = ['year', 'reference_number'];
    protected $fillable = [
        'year','reference_number','address1',
        'address2','address3','address4','phone',
        'county','country','postal', 'file_number'
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
