<?php

namespace Entrada\Modules\Admissions\Models\Entrada\UserRoles;

use Entrada\Modules\Admissions\Models\Entrada\Applicant;
use Entrada\Modules\Admissions\Models\Entrada\Pool;
use Entrada\Modules\Admissions\Models\Entrada\Reader;
use Entrada\Modules\Admissions\Models\Entrada\ReaderGroup;
use Entrada\Modules\Admissions\Models\Entrada\ReaderType;
use Entrada\Modules\Admissions\Models\Entrada\User;
use Illuminate\Support\Collection;

class ReaderUser extends User {

    /**
     * Return the first Reader object related to this ReaderUser (on proxy_id)
     *
     * filtered through CycleScope
     * note: a User should only be on Reader per Cycle, but this isn't a strict relation
     *
     * @return mixed
     */
    public function reader() {
        $readerIds = $this->readers()->pluck("reader_id")->all();

        return Reader::whereIn("reader_id", $readerIds)->first();
    }

    /**
     * The relation to fetch all Readers connected to this user
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function readers() {
        return $this->hasMany(Reader::class, "proxy_id", "id");
    }

    public function readerTypes() {
        return $this->hasManyThrough(ReaderType::class, Reader::class, "proxy_id", "reader_type_id")->byPool()->get();
    }

    /**
     * Returns the Reader objects for this user filtered by Pool
     *
     * @return mixed
     */
    public function admissionsReaders() {
        return $this->readers()->byPool($this)->get();
    }

    /**
     * Fetches the available Pools accessible to this User view their Reader relations
     *
     * @param bool $justIds
     * @return \Illuminate\Database\Eloquent\Collection|mixed
     */
    public function pools($justIds = false) {
        $readerObj = new Reader();
        $poolObj = new Pool();

        $pools = $this->belongsToMany(Pool::class, $readerObj->getTable(), "proxy_id", "pool_id");

        return ($justIds) ? $pools->pluck($poolObj->getTable().".pool_id") : $pools->get();
    }

    /**
     * Gets ALL the Applicants visible to this user, regardless of endpoint
     *
     * @return Collection
     */
    public function allApplicants() {

        if (empty($this->reader()->group)) {
            $collection = new Collection();
            return $collection;
        } else {
            return $this->reader()->group->applicants->makeVisible(["scores"]);
        }
    }


    /**
     * Returns the Applicants visible to this user for the main applicant endpoint
     *
     * ReaderUsers can only view applicants for FileReview, so this is empty
     *
     * @return array
     */
    public function applicants() {
        return [];
    }


    /**
     * Returns the Pool ID of this User in the current or specified Cycle
     *
     * If this user does not have a Pool ID, returns false
     *
     * @return bool|int the ID of the Pool this User is assigned to, false if none
     */
    public function getPoolID() {
        if (empty($this->reader()->group->pool_id)) {
            return false;
        } else {
            return $this->reader()->group->pool_id;
        }
    }

}