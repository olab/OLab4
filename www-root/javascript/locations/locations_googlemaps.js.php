<script type="text/javascript">

    var googleMap = null;
    var updater = null;

    function initialize() {
        googleMap = new GMap2($('mapData'));
    }

    function getRegionId(text, li) {
        if (li.id) {
            $('region_id').setValue(li.id);
        }
    }

    function addPointToMap(lat, lng) {
        if (googleMap && lat != '' && lng != '' && GBrowserIsCompatible()) {
            point = new GLatLng(lat, lng);

            addMarker(point, lat, lng);
        }
    }

    function addAddressToMap(response) {
        if (googleMap && GBrowserIsCompatible()) {
            if (!response || response.Status.code != 200) {
            //alert("Sorry, we were unable to geocode that address");
            } else {
                place = response.Placemark[0];
                point = new GLatLng(place.Point.coordinates[1], place.Point.coordinates[0]);

                addMarker(point, place.Point.coordinates[1], place.Point.coordinates[0]);
            }
        }
    }

    function addMarker(point, lat, lng) {
        if (googleMap && point && lat && lng) {
            if (!$('mapContainer').visible()) {
                $('mapContainer').show();
            }

            googleMap = new GMap2($('mapData'));
            googleMap.setUIToDefault();
            googleMap.setCenter(point, 15);
            googleMap.clearOverlays();

            var icon = new GIcon();
            icon.image = '<?php echo ENTRADA_URL; ?>/images/icon-apartment.gif';
            icon.shadow = '<?php echo ENTRADA_URL; ?>/images/icon-apartment-shadow.png';
            icon.iconSize = new GSize(25, 34);
            icon.shadowSize = new GSize(35, 34);
            icon.iconAnchor = new GPoint(25, 34);
            icon.infoWindowAnchor = new GPoint(15, 5);

            var marker = new GMarker(point, icon);
            googleMap.addOverlay(marker);

        }
    }

    function updateBuildingLocation(address, country, city, province) {
        if (address = null) address = false;
        if (country = null) country = false;
        if (city = null) city = false;
        if (province = null) province = false;
        var thisaddress = ($('building_address1') ? $F('building_address1') : address);
        var thiscountry = ($F('country_id') ? $('country_id')[$('country_id').selectedIndex].text : country);
        var thiscity = ($F('building_city') ? $F('building_city') : city);
        if (thisaddress && thiscity && thiscountry && GBrowserIsCompatible()) {
            if (!googleMap) {
                initialize();
            }
            var geocoder = new GClientGeocoder();

            var search = [thisaddress, thiscity];
            if (province) {
                search.push(province);
            }
            search.push(thiscountry);

            searchFor = search.join(', ');
            geocoder.getLocations(searchFor, addAddressToMap);
        }

        return false;
    }
</script>