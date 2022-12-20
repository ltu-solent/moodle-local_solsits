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
 * Upgrade functions for solsits
 *
 * @package   local_solsits
 * @author    Mark Sharp <mark.sharp@solent.ac.uk>
 * @copyright 2022 Solent University {@link https://www.solent.ac.uk}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


/**
 * Stub for upgrade code
 * @param int $oldversion
 * @return bool
 */
function xmldb_local_solsits_upgrade($oldversion) {
    if ($oldversion < 2022112119) {
        $fields = [
            'academic_year',
            'level_code',
            'location_code',
            'module_code',
            'org_2',
            'org_3',
            'pagetype',
            'period_code',
            'related_courses',
            'subject_area',
            'templateapplied'
        ];
        foreach ($fields as $field) {
            \local_solsits\helper::create_sits_coursecustomfields($field);
        }
        upgrade_plugin_savepoint(true, 2022112119, 'local', 'solsits');
    }

    // Existing Courses must have a solcourse record with templateapplied=true
    // otherwise enrolments will never happen.
    // Find all courses - add them to solcourses.
    return true;
}
