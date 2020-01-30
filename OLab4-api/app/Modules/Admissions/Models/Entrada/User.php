<?php

namespace Entrada\Modules\Admissions\Models\Entrada;

use Illuminate\Database\Eloquent\Relations\Relation;

class User extends \Entrada\Models\Auth\User {

    /**
     * The CycleContact Relation for this User
     *
     * @return Relation
     */
    public function contact() {

        $cycle = Cycle::cycleFromRequest();

        return $this->hasMany(CycleContact::class, "proxy_id", "id")->where("cycle_id", "=", $cycle);
    }


    /**
     * Returns all Cycles viewable to this user
     *
     * note: by default, Users cannot see any Cycles. This function is overloaded by child classes
     *
     * @return array the array of Cycles
     */
    public function cycles() {
        return [];
    }


    /**
     * Returns all applicants visible to this User
     *
     * return array the array of
     */
    public function allApplicants() {
        return collect([]);
    }

    /**
     * Returns all Applicants viewable to this user
     *
     * note: by default, Users cannot see any Applicants. This function is overloaded by child classes
     *
     * @return array the array of Applicants
     */
    public function applicants() {
        return [];
    }

    /**
     * Return all Applicants and data required for the File Review process
     * based on the applicants returned from $this->applicants, or as overloaded by a child class
     *
     * @return array the array of Applicants
     */
    public function fileReviewApplicants() {

        return Applicant::select(Applicant::fileReviewParams())
            ->fileReview()
            ->byPool()
            ->with(["files", "files.flags", "files.flags.flagger"])
            ->whereIn(
                "applicant_id" , $this->allApplicants()->pluck("applicant_id")->toArray()
            )
            ->get()
            ->makeVisible(["scores"])
            ->toArray();
    }

    /**
     * Return all Pools viewable to this User
     *
     * note: by default, Users cannot see any Pools. This function is overloaded by child classes
     *
     * @return array the array of Pools
     */
    public function pools() {
        return [];
    }

    /**
     * Return all Readers viewable to this User
     *
     * @return array an array of Readers
     */
    public function admissionsReaders() {
        return [];
    }

    /**
     * Return all ReaderTypes viewable to this User
     *
     * @return array an array of ReaderTypes
     */
    public function readerTypes() {

        return ReaderType::byPool($this)->get();
    }


    /**
     * Return all Filters viewable to this User
     *
     * note: by default, Users cannot see any Filters. This function is overloaded by child classes
     *
     * @return array the array of Filters
     */
    public function filters() {
        return [];
    }

    /**
     * Returns a list of Files that have been assigned to this User's Applicants
     *
     * @return []|Collection a collection of Files with Flags
     */
    public function files() {
        $files = [];
        foreach ($this->applicants() as $applicant) {
            $files[] = $applicant->files;
        }

        return collect($files)->flatten();
    }

    /**
     * Return Flags by this User
     *
     * As this User is the Flagger, we don't need to display that data
     *
     * @return \Illuminate\Database\Eloquent\Collection|static[]
     */
    public function flags() {
        return Flag::where(["flagged_by" => $this->id])
            ->setHidden(["flagged_by", "flagger_name", "flagger"])
            ->all();
    }

    /**
     * Return all Scores based on this User's viewable Applicants
     *
     * @return Collection
     */
    public function scores() {
        $scores = [];
        foreach ($this->applicants() as $applicant) {
            $scores[] = $applicant->scores();
        }

        return collect($scores)->flatten();
    }

    /**
     * Returns the Pool ID of this User in the current or specified Cycle
     *

     * @return bool|int returns false by default unless overridden
     */
    public function getPoolID() {
        return false;
    }

    /**
     * By default, a User is not an admin, but this can be overloaded by a child class
     *
     * @return bool true if this user is an admin user, false otherwise
     */
    public function isAdmin() {
        return false;
    }
}