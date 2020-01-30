<?php
use Entrada\Modules\Admissions\Models\Entrada\Applicant;
use Entrada\Modules\Admissions\Models\Entrada\ApplicantFile;

$factory->define(Applicant::class, function(Faker\Generator $faker) {

    $randGender = rand(0, 1);
    $randomBirthdate = $faker->dateTimeBetween("-35 years", "-21 years");
    $now = new DateTime("now");
    $avg_last_2 = rand(23, 45) / 10;
    $cum_avg = rand(23, 45) / 10;

    $grad = rand(1, 3) == 3 ? "G" : "";
    $country = rand(1, 14) == 10 ? strtoupper($faker->country) : "CANADA";
    $abor = rand(1, 10) == 10 && $country == "CANADA" ? "Y" : "";
    $mdphd = rand(1, 6) == 6 ? "Y" : "N";

    if ($country !== "CANADA") {
        $pool_id = 1;
    } elseif ($abor == "Y") {
        $pool_id = 4;
    } elseif ($mdphd == "Y") {
        $pool_id = 3;
    } else {
        $pool_id = 2;
    }

    $bbfl = rand(118, 132);
    $cpbs = rand(118, 132);
    $cars = rand(118, 132);
    $psbb = rand(118, 132);
    $mcat = $bbfl + $cpbs + $cars + $psbb;

    return [
        "year" => 2018,
        "pool_id" => $pool_id,
        "cycle_id" => rand(1, 3),
        "reference_number" => rand(99000000, 99999999),
        "given_name" => $randGender ? $faker->firstNameMale : $faker->firstNameFemale,
        "surname" => $faker->lastName,
        "sex" => $randGender ? "M" : "F",
        "birthdate" => $randomBirthdate->format("Y-m-d"),
        "age" => $randomBirthdate->diff($now)->y,
        "total_credits" => rand(24, 40),
        "cumulative_avg" => min(4, $avg_last_2),
        "average_last_2_years" =>  min(4, $cum_avg),
        "grad_indicator" => $grad,
        "aboriginal_status" => $abor,
        "apply_to_mdphd" => $mdphd,
        "citizenship" => $country,
        "mcat_total" => $mcat,
        "bbfl" => $bbfl, // biological and biochemical foundations of living systems
        "cpbs" => $cpbs, // chemical and physical foundations of biological sciences
        "cars" => $cars, // critical analysis and reasoning skills
        "psbb" => $psbb, // psychological, social and biological foundations of behaviour
        'local_address1' => $faker->streetAddress,
        'local_address2' => $faker->city." ".$faker->postcode,
        'local_address3' => "",
        'local_address4' => "",
        'local_telephone' => preg_replace("/[^0-9]/", "",$faker->phoneNumber),
        'email_address' => $faker->email,
        'last_university_name' => strtoupper($faker->word)." ".["UNIVERSITY", "COLLEGE"][rand(0,1)],

        "has_reference_letters" => rand(1, 20) == 1 ? 0 : 1,
        "has_sketch_review" => rand(1, 20) == 1 ? 0 : 1,
        "application_status" => "",
        'emp_id' => "",
        "last_roo_num" => 0,
        "last_hom_num" => 0,
        "last_crs_num" => 0,
        "last_cor_num" => 0,
        "last_skp_num" => 0,
        "last_abs_num" => 0,
        "last_aca_num" => 0
    ];
});

$factory->define(ApplicantFile::class, function(\Faker\Generator $faker) {

    return [
        "file_num" => "999",
        "doc_id" => rand(990000, 999999),
        "additional" => null
    ];
});