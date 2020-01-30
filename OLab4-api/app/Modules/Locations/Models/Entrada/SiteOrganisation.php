<?php

namespace Entrada\Modules\Locations\Models\Entrada;

use Illuminate\Database\Eloquent\Model;

class SiteOrganisation extends Model
{
    protected $table = "global_lu_sites_organisation";
    protected $primaryKey = "site_id";
    protected $fillable = [
        "site_id",
        "organisation_id"
    ];

    public $timestamps = false;
}
