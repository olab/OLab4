<?php

namespace Entrada\Modules\Admissions\Database\Seeds;

use Entrada\Modules\Admissions\Models\Entrada\Applicant;
use Entrada\Modules\Admissions\Models\Entrada\ApplicantFile;
use Illuminate\Database\Seeder;

class AdmissionsDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     *
     */
    public function run()
    {
        $files = ApplicantFile::demoFiles();
        //
        return factory(Applicant::class, 1000)->create()->each(function(Applicant $applicant) use ($files) {

            if ($applicant->has_sketch_review) {

                $applicant->files()->saveMany(factory(ApplicantFile::class, 1)->create([
                    "applicant_id" => $applicant->applicant_id,
                    "cycle_id" => $applicant->cycle_id,
                    "type" => "sketch",
                    "subtype" => "auto_sketch",
                    "path" => $files['auto_sketch'],
                    "filename" => "fake_".$applicant->reference_number."_".rand(15000, 50000).".pdf",
                ]));

                $applicant->files()->saveMany(factory(ApplicantFile::class, 1)->create([
                    "applicant_id" => $applicant->applicant_id,
                    "cycle_id" => $applicant->cycle_id,
                    "type" => "sketch",
                    "subtype" => "detail_sketch",
                    "path" => $files['detail_sketch'],
                    "filename" => "fake_".$applicant->reference_number."_".rand(15000, 50000).".pdf",
                ]));

                $applicant->files()->saveMany(factory(ApplicantFile::class, 1)->create([
                    "applicant_id" => $applicant->applicant_id,
                    "cycle_id" => $applicant->cycle_id,
                    "type" => "datasheet",
                    "subtype" => "datasheet",
                    "path" => $files['datasheet'],
                    "filename" => "fake_".$applicant->reference_number."_".rand(15000, 50000).".pdf",
                ]));
            }

            if ($applicant->has_reference_letters) {
                $applicant->files()->saveMany(factory(ApplicantFile::class, 3)->create([
                    "applicant_id" => $applicant->applicant_id,
                    "cycle_id" => $applicant->cycle_id,
                    "type" => "letter",
                    "subtype" => "reference_letter",
                    "path" => $files['other'],
                    "filename" => "fake_".$applicant->reference_number."_".rand(15000, 50000).".pdf",
                ]));
            }


            $applicant->files()->saveMany(factory(ApplicantFile::class, 1)->create([
                "applicant_id" => $applicant->applicant_id,
                "cycle_id" => $applicant->cycle_id,
                "type" => "other",
                "subtype" => "verifier",
                "path" => $files['other'],
                "filename" => "fake_".$applicant->reference_number."_".rand(15000, 50000).".pdf",
            ]));
        });
    }
}
