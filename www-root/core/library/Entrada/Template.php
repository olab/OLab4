<?php
/**
 * Entrada_Template
 *
 * The Entrada Template provides and sets template information for the
 * Entrada interface.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Matt Simpson <simpson@queensu.ca>
 * @copyright Copyright 2013 Queen's University. All Rights Reserved.
 *
*/
class Entrada_Template {
    private $cache_timeout = 30;

    private $default_template;
    private $active_template;

    private $template_url;
    private $template_absolute;
    private $template_relative;

    public function __construct () {
        if (defined("CACHE_TIMEOUT") && CACHE_TIMEOUT) {
            $this->cache_timeout = CACHE_TIMEOUT;
        }

        $this->default_template = DEFAULT_TEMPLATE;
        $this->active_template = DEFAULT_TEMPLATE;

        $this->template_url = ENTRADA_URL . "/templates/" . DEFAULT_TEMPLATE;
        $this->template_absolute = ENTRADA_ABSOLUTE . "/templates/" . DEFAULT_TEMPLATE;
        $this->template_relative = ENTRADA_RELATIVE . "/templates/" . DEFAULT_TEMPLATE;
    }

    public function defaultTemplate() {
        return $this->default_template;
    }

    public function activeTemplate() {
        return $this->active_template;
    }

    public function url() {
        return $this->template_url;
    }

    public function absolute() {
        return $this->template_absolute;
    }

    public function relative() {
        return $this->template_relative;
    }

    public function setActiveTemplate($org_id = 0) {
        $template = $this->_getOrganisationTemplate($org_id);
        if ($template) {
            $this->active_template = $template;

            $this->template_url = ENTRADA_URL . "/templates/" . $template;
            $this->template_absolute = ENTRADA_ABSOLUTE . "/templates/" . $template;
            $this->template_relative = ENTRADA_RELATIVE . "/templates/" . $template;

            return true;
        }

        return false;
    }

    private function _getOrganisationTemplate($org_id = 0) {
        global $db;

        $org_id = (int) $org_id;

        if ($org_id) {
            $query = "SELECT `template` FROM `" . AUTH_DATABASE . "`.`organisations` WHERE `organisation_id` = " . $db->qstr($org_id);
            $template = $db->CacheGetOne($this->cache_timeout, $query);
            if ($template) {
                return $template;
            }
        }

        return false;
    }
}