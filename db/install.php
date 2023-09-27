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
 * Install functions for solsits
 *
 * @package   local_solsits
 * @author    Mark Sharp <mark.sharp@solent.ac.uk>
 * @copyright 2022 Solent University {@link https://www.solent.ac.uk}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * This function is run when the plugin is installed.
 *
 * @return void
 */
function xmldb_local_solsits_install() {
    global $DB, $USER;
    $fields = [
        'academic_year',
        'level_code',
        'location_code',
        'location_name',
        'module_code',
        'module_occurrence',
        'org_2',
        'org_3',
        'pagetype',
        'period_code',
        'related_courses',
        'subject_area',
        'templateapplied',
    ];
    foreach ($fields as $field) {
        \local_solsits\helper::create_sits_coursecustomfields($field);
    }

    // Copy over settings from Quercus tasks plugin, where needed.
    $quercusconfig = get_config('local_quercus_tasks');
    if (isset($quercusconfig->grademarkscale)) {
        set_config('grademarkscale', $quercusconfig->grademarkscale, 'local_solsits');
    }
    if (isset($quercusconfig->grademarkexemptscale)) {
        set_config('grademarkexemptscale', $quercusconfig->grademarkexemptscale, 'local_solsits');
    }
    if (isset($quercusconfig->cutoffinterval)) {
        set_config('cutoffinterval', $quercusconfig->cutoffinterval, 'local_solsits');
    }
    if (isset($quercusconfig->cutoffintervalsecondplus)) {
        set_config('cutoffintervalsecondplus', $quercusconfig->cutoffintervalsecondplus, 'local_solsits');
    }
    if (isset($quercusconfig->gradingdueinterval)) {
        set_config('gradingdueinterval', $quercusconfig->gradingdueinterval, 'local_solsits');
    }

    $oldcapabilities = $DB->get_records('role_capabilities', ['capability' => 'local/quercus_tasks:releasegrades']);
    $context = context_system::instance();
    foreach ($oldcapabilities as $oldcapability) {
        $cap = new stdClass();
        $cap->contextid    = $context->id;
        $cap->roleid       = $oldcapability->roleid;
        $cap->capability   = 'local/solsits:releasegrades';
        $cap->permission   = $oldcapability->permission;
        $cap->timemodified = time();
        $cap->modifierid   = empty($USER->id) ? 0 : $USER->id;
        // Check it doesn't already exist.
        $existing = $DB->get_record('role_capabilities', [
            'contextid' => $context->id,
            'roleid' => $cap->roleid,
            'capability' => $cap->capability,
        ]);
        if (!$existing) {
            $DB->insert_record('role_capabilities', $cap);
            // Trigger capability_assigned event.
            \core\event\capability_assigned::create([
                'userid' => $cap->modifierid,
                'context' => $context,
                'objectid' => $cap->roleid,
                'other' => [
                    'capability' => $cap->capability,
                    'oldpermission' => CAP_INHERIT,
                    'permission' => $cap->permission,
                ],
            ])->trigger();

            // Reset any cache of this role, including MUC.
            accesslib_clear_role_cache($cap->roleid);
        }
    }
}
