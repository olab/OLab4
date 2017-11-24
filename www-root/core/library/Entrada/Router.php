<?php
/**
 * Entrada_Router
 *
 * The Entrada Router routes all requests to the correct module or sub-module and appends the
 * correct section as provided by the $_GET["section"] a.k.a. $SECTION variable.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Matt Simpson <matt.simpson@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 *
*/
class Entrada_Router {
	private $request = "";
	private $modules = array();
	private $section = "";
	private $base_path = "";
	private $load_path = "";
	private $initialized = false;

    public function __construct () {
		$path_info = ((isset($_SERVER["PATH_INFO"])) ? clean_input($_SERVER["PATH_INFO"], array("url", "lowercase")) : "dashboard");
		$parsed_path = parse_url($path_info, PHP_URL_PATH);

		if ($parsed_path) {
			$this->request = preg_replace(array("%^/%", "%/$%"), "", $parsed_path);
			$this->modules = explode("/", $this->request);
		}

		if ($this->request && count($this->modules)) {
			return true;
		}

		return false;
    }

    public function setBasePath($path = "") {
		$path = clean_input($path, "dir");

		if ($path) {
			$this->base_path = $path;
		}
	}

	public function setSection($section = "") {
		$section = clean_input($section, "module");

		return ($section &&($this->section = $section));
	}

	public function getModules() {
		return $this->modules;
	}

	public function getLoadPath() {
		return $this->load_path;
	}

	/**
	 * Adds a module (directory) to the requests
	 * @param string $module
	 */
	public function addModule($module = "") {
		$module = clean_input($module, "module");
		//the check for the end is to ensure we don't end up loading the same module twice
		return ($module && end($this->modules) && (current($this->modules) == $module || ($this->modules[] = $module)));
	}

	public function initRoute($insert_module = "") {
		$insert_module = clean_input($insert_module, "module");

		$load_path = array();

		$current_depth = substr_count($this->load_path, "/");

		if ($insert_module) {
			if (!is_array($this->modules)){
				$this->modules = array();
			}

			//check modules array to see if it is next in line anyways
			//if not, splice/push it in

			if (isset($this->modules[$current_depth])) {
				if ($this->modules[$current_depth] != $insert_module) {
					array_splice($this->modules, $current_depth, 0, $insert_module);
				}
			} else {
				array_push($this->modules, $insert_module);
			}
		}

		$request_depth = ((is_array($this->modules)) ? count($this->modules) : 1);

		if ($current_depth <= $request_depth) {
			for ($level = 0; $level <= $current_depth; $level++) {

				if (isset($this->modules[$level]) && $this->modules[$level]) {
					$load_path[] = clean_input($this->modules[$level], "alpha");
				}
			}

			if (($current_depth == $request_depth) && ($this->section)) {
				$load_path[] = $this->section;
			}
		}

		$this->load_path = "/".((count($load_path)) ? implode(DIRECTORY_SEPARATOR, $load_path).".inc.php" : false);
		$this->initialized = true;
		return $this->load_path;
	}

	public function getRoute() {
		if ($this->base_path && $this->load_path) {
			$load_route = $this->base_path . $this->load_path;

			if ((@file_exists($load_route)) && (@is_readable($load_route))) {
				return $load_route;
			} else {
				return ENTRADA_ABSOLUTE.DIRECTORY_SEPARATOR."default-pages".DIRECTORY_SEPARATOR."404.inc.php";

				application_log("error", $load_route." does not exist or is not readable by PHP.");
			}
		} else {
			application_log("error", "The Entrada_Router base_path [".$this->base_path."] or load_path [".$this->load_path."] was not set.");

			return false;
		}
	}
}
