<?php
class Controllers_Schedule extends Controllers_Base {

    protected $validation_rules = array(
        "schedule_id"               => array("sanitization_params" => "int"),
        "schedule_parent_id"        => array("sanitization_params" => "int"),
        "schedule_type"             => array(
                "required"              => true,
                "sanitization_params"   => array("trim", "striptags"),
                "allowed_values"        => array("organisation", "academic_year", "stream", "block", "rotation", "rotation_academic_year", "rotation_stream", "rotation_block", "rotation_event")
            ),
        "generate_blocks"           => array("sanitization_params" => "int"),
        "block_end_day"             => array(
                "required"              => false,
                "sanitization_params"   => array("trim", "striptags"),
                "allowed_values"        => array("sunday", "monday", "tuesday", "wednesday", "thursday", "friday", "saturday")
            ),
        "title"                     => array("required" => true,  "sanitization_params" => array("trim", "striptags")),
        "description"               => array("sanitization_params" => array("trim", "striptags")),
        "block_type_id"             => array("sanitization_params" => "int"),
        "start_date"                => array("sanitization_params" => array("strtotime", "int")),
        "end_date"                  => array("sanitization_params" => array("strtotime", "end_of_day", "int")),
        "first_block_end_date"      => array(),
        "organisation_id"           => array("required" => true, "sanitization_params" => "int"),
        "cperiod_id"                => array("required" => false, "sanitization_params" => "int"),
        "schedule_order"            => array("required" => false, "sanitization_params" => "int")
    );

    public function __construct($request_data) {
        parent::__construct($request_data);
    }

    public function save() {
        if ($this->validated_data["end_date"] < $this->validated_data["start_date"]) {
            $this->errors["conditions"]["start_date"] = "Start date is after end date.";
        }

        if ($this->validated_data["schedule_type"] == "block" && ($this->validated_data["end_date"] < $this->validated_data["start_date"]) ) {
            if (empty($this->validated_data["end_date"])) {
                $this->errors["conditions"]["end_date"] = "Block schedule elements require an end date.";
            } else {
                $this->errors["conditions"]["start_date"] = "Start date is after end date.";
            }
        }

        $first_block_end_date = null;

        if (isset($this->validated_data["generate_blocks"]) && $this->validated_data["generate_blocks"]) {
            if (isset($this->validated_data["first_block_end_date"]) && $this->validated_data["first_block_end_date"]) {

                $first_block_end_date = false;
                if ($tmp_input = clean_input($this->validated_data["first_block_end_date"], array("strtotime", "end_of_day", "int"))) {
                    $first_block_end_date = $tmp_input;
                }

                if (!$first_block_end_date || $first_block_end_date < $this->validated_data["start_date"]) {
                    $this->errors["conditions"]["first_block_end_date"] = array("First block end date is before the start date.");
                }
            }
        }

        if (!$this->getErrors()) {
            $method = "insert";
            $schedule = new Models_Schedule();
            if (isset($this->validated_data["schedule_id"]) && !empty($this->validated_data["schedule_id"])) {
                $method = "update";
                $schedule::fetchRowByID($this->validated_data["schedule_id"]);
            } else {
                $this->validated_data["created_date"] = time();
                $this->validated_data["created_by"] = time();
            }
            
            if ($schedule->fromArray($this->validated_data)->$method()) {
                if ($this->validated_data["schedule_type"] == "stream" && isset($this->validated_data["generate_blocks"]) && $this->validated_data["generate_blocks"]) {
                    $this->generateBlocks($first_block_end_date);
                }
                return $schedule;
            }

            return false;
        }

        return $this->getErrors();
    }

    public function generateBlocks($first_block_end_date = null) {
        if (!empty($this->validated_data) && isset($this->validated_data["generate_blocks"])) {

            if (!isset($this->validated_data["block_end_day"]) && !empty($this->validated_data["block_end_day"])) {
                $this->validated_data["block_end_day"] = "monday";
            }

            if ($this->validated_data["block_end_day"] == "sunday") {
                $this->validated_data["block_end_day"] .= " last week";
            } else {
                $this->validated_data["block_end_day"] .= " this week";
            }

            $block_type = Models_BlockType::fetchRowByID($this->validated_data["block_type_id"]);
            $total_blocks = (int) ($block_type->getNumberOfBlocks());
            $start = $this->validated_data["start_date"];
            // Users can override the first block's end date to deal with odd starting days.
            if ($first_block_end_date) {
                $end = $first_block_end_date;
            } else {
                $end = strtotime($this->validated_data["block_end_day"], $start + ((52 / $block_type->getNumberOfBlocks()) * 604800));
            }

            $i = 1;
            while ($end <= $this->validated_data["end_date"]) {
                if ($i > 99) {
                    application_log("error", "Something went seriously wrong when attempting to generate block schedules.");
                    break;
                }
                $block_data = array(
                    "title"                 => "Block " . $i,
                    "schedule_type"         => "block",
                    "organisation_id"       => $this->validated_data["organisation_id"],
                    "schedule_parent_id"    => $this->validated_data["schedule_id"],
                    "start_date"            => strtotime(date("l, F jS, Y", $start) . " 00:00:00"),
                    "end_date"              => strtotime(date("l, F jS, Y", $end) . " 23:59:59"),
                    "cperiod_id"            => $this->validated_data["cperiod_id"],
                    "block_type_id"         => $block_type->getID(),
                    "created_date"          => time(),
                    "created_by"            => 1
                );

                $schedule = new Models_Schedule($block_data);
                $schedule->insert();

                $start = strtotime(date("l, F jS, Y", $end) . " 23:59:59") + 1;
                $end = strtotime($this->validated_data["block_end_day"], $start + ((52 / $block_type->getNumberOfBlocks()) * 604800));
                $i++;
                if ($i == $total_blocks) {
                    $end = $this->validated_data["end_date"];
                }
            }
        }
    }

}