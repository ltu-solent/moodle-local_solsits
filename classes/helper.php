<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * General helper to save repeating bits of code
 *
 * @package   local_solsits
 * @author    Mark Sharp <mark.sharp@solent.ac.uk>
 * @copyright 2022 Solent University {@link https://www.solent.ac.uk}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_solsits;

use context_module;
use context_system;
use core_course\customfield\course_handler;
use core_customfield\category;
use core_customfield\field;
use Exception;

/**
 * Helper class for common functions.
 */
class helper {

    /**
     * Gets key/value pairs for Pagetypes menu.
     *
     * @return array
     */
    public static function get_pagetypes_menu(): array {
        $options = [
            soltemplate::MODULE => soltemplate::MODULE,
            soltemplate::COURSE => soltemplate::COURSE
        ];
        return $options;
    }
    /**
     * Returns key/value pairs for Session menu. Used also for validation.
     *
     * @return array
     */
    public static function get_session_menu(): array {
        $years = range(2020, date('Y') + 1);
        $options = [];
        foreach ($years as $year) {
            $yearplusone = substr($year, 2, 2) + 1;
            $options[$year . '/' . $yearplusone] = $year . '/' . $yearplusone;
        }
        return array_reverse($options);
    }

    /**
     * Gets list of courses in the designated template category formatted for a menu.
     *
     * @return array
     */
    public static function get_templates_menu(): array {
        global $DB;
        $templatecat = get_config('local_solsits', 'templatecat');
        if (!isset($templatecat)) {
            return [];
        }
        return $DB->get_records_menu('course', ['category' => $templatecat], 'fullname ASC', 'id, fullname');
    }

    /**
     * Create customfields for SITS data
     *
     * @param string $shortname
     * @return void
     */
    public static function create_sits_coursecustomfields($shortname) {
        // This is the required configdata fields for "text" data type.
        // If you require different fields, enter manually below.
        $configdatavisible = json_encode([
            'locked' => 1,
            'visibility' => course_handler::VISIBLETOALL,
            'ispassword' => 0,
            'required' => 0,
            'uniquevalues' => 0,
            'defaultvalue' => '',
            'displaysize' => 50,
            'maxlength' => 1333,
            'link' => ''
        ]);
        $configdatainvisible = json_encode([
            'locked' => 1,
            'visibility' => course_handler::NOTVISIBLE,
            'ispassword' => 0,
            'required' => 0,
            'uniquevalues' => 0,
            'defaultvalue' => '',
            'displaysize' => 50,
            'maxlength' => 1333,
            'link' => ''
        ]);
        $predefined = [
            'academic_year' => [
                'name' => 'Academic year'
            ],
            'level_code' => [
                'name' => 'Academic level'
            ],
            'location_code' => [
                'name' => 'Location'
            ],
            'module_code' => [
                'name' => 'Module or Course code'
            ],
            'module_occurrence' => [
                'name' => 'Module occurrence'
            ],
            'org_2' => [
                'name' => 'Faculty/School'
            ],
            'org_3' => [
                'name' => 'Department'
            ],
            'pagetype' => [
                'name' => 'Page type',
                'configdata' => $configdatainvisible
            ],
            'period_code' => [
                'name' => 'Period',
                'configdata' => $configdatainvisible
            ],
            'related_courses' => [
                'name' => 'Related courses'
            ],
            'subject_area' => [
                'name' => 'Subject area'
            ],
            'templateapplied' => [
                'name' => 'Template applied',
                'configdata' => json_encode([
                    'locked' => 1,
                    'visibility' => course_handler::NOTVISIBLE,
                    'ispassword' => 0,
                    'required' => 0,
                    'uniquevalues' => 0,
                    'defaultvalue' => '0',
                    'displaysize' => 50,
                    'maxlength' => 1333,
                    'link' => ''
                ])
            ]
        ];

        if (!array_key_exists($shortname, $predefined)) {
            // Not defined. Do nothing.
            return;
        }

        $category = category::get_record([
            'name' => 'Student Records System',
            'area' => 'course',
            'component' => 'core_course'
        ]);
        if (!$category) {
            // No category, so create and use it.
            $category = new category(0, (object)[
                'name' => 'Student Records System',
                'description' => 'Fields managed by the university\'s Student records system. Do not change unless asked to.',
                'area' => 'course',
                'component' => 'core_course',
                'contextid' => context_system::instance()->id
            ]);
            $category->save();
        }
        $field = field::get_record([
            'shortname' => $shortname,
            'categoryid' => $category->get('id')
        ]);
        if ($field) {
            // Already exists. Nothing to do here.
            return;
        }
        $data = (object)$predefined[$shortname];
        $data->categoryid = $category->get('id');
        $data->shortname = $shortname;
        $data->type = $data->type ?? 'text';
        $data->configdata = $data->configdata ?? $configdatavisible;
        $field = new field(0, $data);
        $field->save();
    }

    /**
     * Check to see if a template has been applied to this course.
     *
     * @param int $courseid
     * @return boolean
     */
    public static function istemplated($courseid): bool {
        $value = static::get_customfield($courseid, 'templateapplied');
        if ($value == null) {
            return false;
        }
        return (bool)$value;
    }

    /**
     * Get pagetype custom field for given course
     *
     * @param int $courseid
     * @return string
     */
    public static function get_pagetype($courseid): string {
        return static::get_customfield($courseid, 'pagetype');
    }

    /**
     * Get field value for given course and field name
     *
     * @param int $courseid
     * @param string $shortname
     * @return mixed
     */
    public static function get_customfield($courseid, $shortname) {
        $handler = course_handler::create();
        $datas = $handler->get_instance_data($courseid, true);
        foreach ($datas as $data) {
            $fieldname = $data->get_field()->get('shortname');
            if ($fieldname != $shortname) {
                continue;
            }
            $value = $data->get_value();
            if (empty($value)) {
                continue;
            }
            return $value;
        }
        return null;
    }

    /**
     * If this is a summative assignment, only return sanctioned gradescales
     *
     * @param integer $courseid
     * @return array
     */
    public static function get_scales_menu($courseid = 0) {
        global $PAGE;
        $cm = $PAGE->cm;
        // Get the default scales menu.
        $scales = \get_scales_menu($courseid);
        if (!isset($cm)) {
            return $scales;
        }
        if (!self::is_summative_assignment($cm->id)) {
            return $scales;
        }
        // Filter out any non-Solent scales.
        $solsitsconfig = get_config('local_solsits');
        $solscales = [$solsitsconfig->grademarkscale, $solsitsconfig->grademarkexemptscale];
        $scales = array_filter($scales, function($scaleid) use ($solscales) {
            return in_array($scaleid, $solscales);
        }, ARRAY_FILTER_USE_KEY);
        // Format: [id => 'scale name'].
        return $scales;
    }

    /**
     * Given a coursemodule id, returns if this is a summative assignment.
     *
     * @param int $cmid Course module id
     * @return boolean
     */
    public static function is_summative_assignment($cmid) {
        try {
            [$course, $cm] = get_course_and_cm_from_cmid($cmid, 'assign');
            return ($cm->idnumber != '');
        } catch (Exception $ex) {
            return false;
        }
    }

    /**
     * Can the logged in user release grades in this context?
     *
     * @return boolean
     */
    public static function can_release_grades($cmid): bool {
        global $DB, $PAGE;
        $issummative = static::is_summative_assignment($cmid);
        if (!$issummative) {
            return true;
        }
        $context = context_module::instance($cmid);
        [$course, $cm] = get_course_and_cm_from_cmid($cmid, 'assign');
        // Update in upgrade.php script transfers capabilities from local/quercus_tasks:releasegrades.
        $hascapability = has_capability('local/solsits:releasegrades', $context);
        $locked = $DB->get_field_select('grade_items', 'locked', 'itemmodule = ? AND iteminstance = ?', array('assign', $cm->instance));
        $gradingpanel = ($PAGE->pagetype == 'mod-assign-gradingpanel');
        if ($hascapability && $locked == 0 && !$gradingpanel) {
            return true;
        }
        if ($locked != 0) {
            return true;
        }
        return false;
    }

    /**
     * This returns the parameter that was passed in. It's used by component_class_callback as a way
     * to ensure core changes don't affect normal core unit tests. If this function doesn't exist,
     * a default value matching what Moodle expects is returned.
     *
     * @param Mixed $params One or more arguments.
     * @return mixed
     */
    public static function returnresult(...$return) {
        if (count($return) > 1) {
            return $return;
        }
        return $return[0];
    }

    /**
     * Is used as a switch in core code edits. If running core unit tests locally set this to false.
     * Used in conjunction with component_class_callback.
     *
     * @return bool
     */
    public static function issolsits() {
        return true;
    }
}

