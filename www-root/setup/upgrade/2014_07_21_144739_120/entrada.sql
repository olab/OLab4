CREATE TABLE `global_lu_buildings` (
  `building_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `organisation_id` int(11) unsigned NOT NULL,
  `building_code` varchar(16) NOT NULL DEFAULT '',
  `building_name` varchar(128) NOT NULL DEFAULT '',
  `building_address1` varchar(128) NOT NULL DEFAULT '',
  `building_address2` varchar(128) NOT NULL DEFAULT '',
  `building_city` varchar(64) NOT NULL DEFAULT '',
  `building_province` varchar(64) NOT NULL DEFAULT '',
  `building_country` varchar(64) NOT NULL DEFAULT '',
  `building_postcode` varchar(16) NOT NULL DEFAULT '',
  PRIMARY KEY (`building_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `global_lu_rooms` (
  `room_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `building_id` int(11) unsigned NOT NULL,
  `room_number` varchar(20) NOT NULL DEFAULT '',
  PRIMARY KEY (`room_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `events` ADD COLUMN `room_id` int(11) unsigned DEFAULT NULL AFTER `event_location`;
ALTER TABLE `draft_events` ADD COLUMN `room_id` int(11) unsigned DEFAULT NULL AFTER `event_location`;
