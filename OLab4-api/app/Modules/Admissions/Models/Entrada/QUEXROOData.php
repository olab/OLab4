<?php

namespace Entrada\Modules\Admissions\Models\Entrada;

class QUEXROOData extends QUEXData
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'quex_roo_data';
    protected $primaryKey = 'quex_roo_data_id';
    protected static $ref_num = ['year', 'reference_number'];
    protected $fillable = [
        'year','reference_number','surname','given_name','title','local_address1','local_address2',
        'local_address3','local_address4','local_telephone','local_county','local_country','use_home_address',
        'sex','birthdate','age','previously_enrolled_meds','failed_year_university',
        'undergrad_program_interrupted','last_mcat_test_date','mcat_verbal_reasoning','mcat_physical_science',
        'mcat_writing_sample','mcat_biological_science','mcat_accommodation','post_secondary_status',
        'academic_verification','graduate_verification','total_length_of_academic_records',
        'total_value_of_academic_records','cumulative_avg','assessment1','assessment2','assessment3',
        'academic_record_count','home_address_count','med_school_registered_at','registered_year_level',
        'average_last_2_years','last_average','mcat_sum_numeric_scores','mcat_sum_double_verbal','mcat_score_lt_7',
        'mcat_score_lt_8','mcat_writing_lt_m','med_school_decision','aboriginal_status',
        'email_address','last_university_name','total_supplementary_length','total_supplementary_value',
        'total_supplementary_avg','grad_indicator','applied_to_mcmaster','applied_to_noms','applied_to_ottawa',
        'applied_to_queens','applied_to_toronto','applied_to_western','international_postal_code','common_name',
        'mcat_score_report_date','mcat_investigation_report_filed','queens_student_number1','queens_student_number2',
        'queens_student_number3','apply_to_mdphd','date_of_offer','response_to_offer','response_to_offer_date',
        'offer_cancel_date','offer_withdraw_date','citizenship','immigration_status','date_of_entry','former_surname',
        'course_load_lt_full','course_load_gt_full','first_spoken_language','spoken_language', 'file_number'
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

    public $date_fields = [
        "last_mcat_test_date" => "mmyy",
        "date_of_offer" => "mmmyy",
        "response_to_offer_date" => "mmmyy",
        "date_of_entry" => "mmmyy",
    ];

}
