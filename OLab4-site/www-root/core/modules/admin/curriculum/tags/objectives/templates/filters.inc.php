<?php
    $HEAD[] = "<script type=\"text/javascript\" src=\"".$ENTRADA_TEMPLATE->relative()."/js/libs/bootstrap-accordion-filter.js?release=".APPLICATION_VERSION."\"></script>\n";
    $HEAD[] = "<link href=\"".$ENTRADA_TEMPLATE->relative()."/css/bootstrap-accordion-filter.css?release=" . APPLICATION_VERSION . "\" rel=\"stylesheet\" media=\"all\"/>\n";

    $filters = array("Status","TranslationStatus");
    
    $default_values = "";
    if ($ENTRADA_USER->getRole() == 'translator') {
        $default_values = "data-defaults={\"objective_translation_status_id\":\"1\"}";
    } 
    
    $filters_json = urlencode(json_encode($filters));
    $html = "<div id=\"sidebarGroupAccordion\" ".
            "data-toggle=\"filter-accordion\" ". 
            "data-url=\"".ENTRADA_URL."/api/api-filter.api.php?filters=$filters_json\" ".
            "data-msgDiv=\"filter-accordion-alerts\" ".
            $default_values .
            "></div>";
    
    $title = $translate->_("Show Tags Matching");
    //new_sidebar_item($title, $html, "sort-results", "open");
?>