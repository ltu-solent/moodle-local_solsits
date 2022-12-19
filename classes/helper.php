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

use context_system;
use core_course\customfield\course_handler;
use core_customfield\category;
use core_customfield\field;

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
                'configdata' => $configdatainvisible
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
}

