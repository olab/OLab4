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

interface Models_Repository_IObjectives extends Models_IRepository {

    public function fetchAllByParentID($parent_id);

    public function fetchAllByParentIDs(array $parent_ids);

    public function fetchTagSetsByOrganisationID($organisation_id);

    public function fetchAllByParentIDAndOrganisationID($parent_id, $organisation_id);

    public function fetchAllByParentIDsAndOrganisationID(array $parent_ids, $organisation_id);

    public function fetchAllByTagSetID($tag_set_id);

    public function fetchAllByTagSetIDAndOrganisationID($tag_set_id, $organisation_id);

    public function fetchLinkedObjectivesByID($direction, $objective_id, $version_id, Entrada_Curriculum_IContext $context, $not = false);

    public function fetchLinkedObjectivesByIDs($direction, array $objective_ids, $version_id, Entrada_Curriculum_IContext $context, $not = false);

    public function fetchLinkedObjectivesByIDsAndEvents($direction, array $objective_ids, $version_id, $event_ids = false);

    public function fetchHasLinks($direction, array $objectives, $version_id, array $exclude_tag_set_ids, Entrada_Curriculum_IContext $context);

    public function populateHasLinks(array $rows, array $objectives_have_links);

    public function fetchTotalMappingsByObjectivesTo($version_id, $to_tag_set_id, array $from_objective_ids, array $event_ids);

    public function updateLinkedObjectives(array $objectives, array $linked_objectives, $version_id, Entrada_Curriculum_Context_ISpecific $context);

    public function insertLinkedObjectiveContexts($objective_id, $target_objective_ids, $version_id, Entrada_Curriculum_Context_ISpecific $context);

    public function insertLinkedObjectives($objective_id, array $target_objective_ids, $version_id);

    public function deleteLinkedObjectiveContexts($objective_id, array $target_objective_ids, $version_id, Entrada_Curriculum_Context_ISpecific $context);

    public function deleteLinkedObjectives($objective_id, array $target_objective_ids, $version_id, Entrada_Curriculum_Context_ISpecific $context);

    public function deleteLinkedObjectivesNotTo($version_id, Entrada_Curriculum_Context_ISpecific $context);

    public function deleteLinkedObjectiveContextsNotToCourseUnit($version_id, $cunit_id);

    public function deleteLinkedObjectivesNotToCourseUnit($version_id, $cunit_id);

    public function deleteLinkedObjectiveContextsNotToCourse($version_id, $course_id);

    public function deleteLinkedObjectivesNotToCourse($version_id, $course_id);

    public function fetchTagSetByObjectives(array $objectives);

    public function groupByTagSet(array $objectives);

    public function groupArraysByTagSet(array $rows);

    public function groupIDsByTagSet(array $objectives);

    public function excludeByTagSetIDs(array $objectives, array $exclude_tag_set_ids);

    public function fetchAllByEventIDs(array $event_ids);

    public function fetchAllByCourseUnitIDs(array $cunit_ids);

    public function fetchAllByCourseIDsAndCperiodID(array $course_ids, $cperiod_id);
}
