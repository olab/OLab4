<?php
/**
 * Entrada [ https://entrada.org ]
 *
 * @author Thaisa Almeida <trda@queensu.ca>
 * @copyright Copyright 2017 Queen's University. All Rights Reserved.
 */
if (!defined("PARENT_INCLUDED")) {
    exit;
} elseif (!isset($_SESSION["isAuthorized"]) || !(bool)$_SESSION["isAuthorized"]) {
    header("Location: " . ENTRADA_URL);
    exit;
} else {
    $PAGE_META["title"] = "Clinical Experience";
    $PAGE_META["description"] = "";
    $PAGE_META["keywords"] = "";

    ?>
    <div id="app-root" data-route="clinical.mylearners" data-layout="NoComponentsLayout"></div>
    <?php
}