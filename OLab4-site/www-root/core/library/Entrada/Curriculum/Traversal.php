<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 *
 * Entrada is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Entrada is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Entrada.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Organisation: The University of British Columbia
 * @author Unit: MedIT - Faculty of Medicine
 * @author Developer: Carlos Torchia <carlos.torchia@ubc.ca>
 * @copyright Copyright 2016 The University of British Columbia. All Rights Reserved.
 *
 */

class Entrada_Curriculum_Traversal implements Entrada_Curriculum_ITraversal, Entrada_IGettable {

    use Entrada_Gettable;

    public function eventIDsLinkedToObjectiveIDs(array $objective_ids, $ignore_direct_event_objectives, array $filters) {
        $events = $this->eventsLinkedToObjectiveIDs($objective_ids, $ignore_direct_event_objectives, $filters);
        $event_ids = array_map(function (Models_Event $event) { return $event->getID(); }, $events);
        return $event_ids;
    }

    public function eventsLinkedToObjectiveIDs(array $objective_ids, $ignore_direct_event_objectives, array $filters) {
        $event_repository = Models_Repository_Events::getInstance();
        if (!$ignore_direct_event_objectives) {
            $events_by_objective = $event_repository->fetchAllByObjectiveIDsAndFilters($objective_ids, $filters);
            $events = $event_repository->flatten($events_by_objective);
        } else {
            $events = array();
        }

        $event_objectives_by_version = array();
        $objective_repository = Models_Repository_Objectives::getInstance();
        $next_linked_objectives_by_version = $objective_repository->fetchLinkedObjectivesByIDsAndEvents("to", $objective_ids, false, false, $filters);
        while ($next_linked_objectives_by_version) {
            $previous_objective_ids = array();
            foreach ($next_linked_objectives_by_version as $version_id => $linked_objectives_by_event) {
                foreach ($linked_objectives_by_event as $event_id => $linked_objectives_by_to_objective) {
                    foreach ($linked_objectives_by_to_objective as $to_objective_id => $linked_objectives) {
                        foreach ($linked_objectives as $objective_id => $objective) {
                            if ($event_id) {
                                $event_objectives_by_version[$version_id][$event_id][$objective_id] = $objective_id;
                            } else {
                                $previous_objective_ids[$objective_id] = $objective_id;
                            }
                        }
                    }
                }
            }
            $next_linked_objectives_by_version = $objective_repository->fetchLinkedObjectivesByIDsAndEvents("to", $previous_objective_ids, false, false, $filters);
        }
        $version_repository = Models_Repository_CurriculumMapVersions::getInstance();
        foreach ($event_objectives_by_version as $version_id => $event_objectives_by_event) {
            $event_ids = array_keys($event_objectives_by_event);
            $events_for_version = $event_repository->fetchAllByIDs($event_ids);
            if ($events_for_version) {
                $versions_by_events = $version_repository->fetchLatestVersionsByEventIDs(Models_Event::toIDs($events_for_version));
                foreach ($events_for_version as $event_id => $event) {
                    if (isset($versions_by_events[$event_id]) && $versions_by_events[$event_id]->getID() == $version_id) {
                        $events[$event_id] = $event;
                    }
                }
            }
        }
        return $events;
    }
}
