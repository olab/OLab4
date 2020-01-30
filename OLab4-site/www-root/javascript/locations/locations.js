jQuery(document).ready(function ($) {
    provStateFunction(country_id, province_id);

    $("#country_id").on("change", function(e) {
        var country = $(this).val();
        provStateFunction(country);
    });

    function provStateFunction(country_id, province_id) {
        if (country_id != undefined) {
            url_country_id = country_id;
        } else if ($("#country_id")) {
            url_country_id = $("#country_id").val();
        }

        if (province_id != undefined) {
            url_province_id = province_id;
        } else if ($("#province_id")) {
            url_province_id = $("#province_id").val();
        } else if (province_id == "undefined") {
            province_id = 0;
        }

        var url = province_web_url + "?countries_id=" + url_country_id + "&prov_state=" + url_province_id;
        var data_string = {
            "country_id": country_id,
            "province_id": province_id
        };

        $.ajax({
            url: url,
            data: data_string,
            type: "GET",
            success: function (html) {
                $("#prov_state_div").html(html);

                if ($("#prov_state").type == "text") {
                    $("#prov_state").clear();
                } else {
                    $("#prov_state").selectedIndex = 0;
                }
            }
        });
    }
});