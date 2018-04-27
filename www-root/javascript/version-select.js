function version_select() {

    jQuery(function () {

        jQuery('#curriculum_type_id').change(function() {
            refresh_period_select();
        });

        jQuery('#version_cperiod_id').change(function() {
            refresh_version_select();
        });
    });

    function refresh_period_select() {

        var select = jQuery("#version_cperiod_id");

        select.find("option:nth-child(n+2)").remove();

        var curriculum_type_id = parseInt(jQuery('#curriculum_type_id').val());

        fetch_curriculum_periods(curriculum_type_id, ORGANISATION).done(function(periods) {

            if (Array.isArray(periods)) {

                jQuery.each(periods, function(index, period) {

                    var option = jQuery(document.createElement("option"));

                    option.html(period['title']);
                    option.val(period['id']);

                    select.append(option);
                });

                if (periods.length) {
                    select.val(periods[0]['id']);
                }

                select.change();
            }
        }).fail(function(message) {

            alert('Error getting curriculum periods: ' + message);
        });
    }

    function refresh_version_select() {

        var select = jQuery("#version_id");

        select.find("option:nth-child(n+2)").remove();

        var cperiod_id = parseInt(jQuery('#version_cperiod_id').val());

        fetch_curriculum_map_versions(cperiod_id, ORGANISATION).done(function(versions) {

            if (Array.isArray(versions)) {

                jQuery.each(versions, function(index, version) {

                    var option = jQuery(document.createElement("option"));

                    option.html(version['title']);
                    option.val(version['id']);

                    select.append(option);
                });

                if (versions.length) {
                    select.val(versions[0]['id']);
                }

                select.change();
            }
        }).fail(function(message) {

            alert('Error getting curriculum map versions: ' + message);
        });
    }
}

function fetch_curriculum_periods(curriculum_type_id, organisation_id) {

    if (curriculum_type_id) {

        var promise = call_api('curriculum-periods', 'GET', 'get-periods', {
            'type_id': curriculum_type_id,
            'org_id': organisation_id
        });

        return promise;

    } else {

        var periods = [];
        var deferred = jQuery.Deferred();

        deferred.resolve(periods);

        return deferred.promise();
    }
}

function fetch_curriculum_map_versions(cperiod_id, organisation_id) {

    if (cperiod_id) {

        var promise = call_api('curriculum-map-versions', 'GET', 'get-versions', {
            'cperiod_id': cperiod_id,
            'org_id': organisation_id
        });

        return promise;

    } else {

        var versions = [];
        var deferred = jQuery.Deferred();

        deferred.resolve(versions);

        return deferred.promise();
    }
}
