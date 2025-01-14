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

use core\context;

/**
 * Stub for upgrade code
 * @param int $oldversion
 * @return bool
 */
function xmldb_local_solsits_upgrade($oldversion) {
    global $DB, $USER;
    $dbman = $DB->get_manager();
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
            'templateapplied',
        ];
        foreach ($fields as $field) {
            \local_solsits\helper::create_sits_coursecustomfields($field);
        }
        upgrade_plugin_savepoint(true, 2022112119, 'local', 'solsits');
    }

    if ($oldversion < 2022112120) {
        $fields = [
            'module_occurrence',
        ];
        foreach ($fields as $field) {
            \local_solsits\helper::create_sits_coursecustomfields($field);
        }
        upgrade_plugin_savepoint(true, 2022112120, 'local', 'solsits');
    }

    if ($oldversion < 2022112121) {
        $quercusconfig = get_config('local_quercus_tasks');
        if ($quercusconfig->grademarkscale) {
            set_config('grademarkscale', $quercusconfig->grademarkscale, 'local_solsits');
        }
        if ($quercusconfig->grademarkexemptscale) {
            set_config('grademarkexemptscale', $quercusconfig->grademarkexemptscale, 'local_solsits');
        }
        upgrade_plugin_savepoint(true, 2022112121, 'local', 'solsits');
    }

    if ($oldversion < 2022112122) {
        // Copy over all the old quercus assignments to use the new capabilities for the same roles.
        $oldcapabilities = $DB->get_records('role_capabilities', ['capability' => 'local/quercus_tasks:releasegrades']);
        $context = context\system::instance();
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
        upgrade_plugin_savepoint(true, 2022112122, 'local', 'solsits');
    }

    if ($oldversion < 2023040501) {
        $quercusconfig = get_config('local_quercus_tasks');
        if ($quercusconfig->cutoffinterval) {
            set_config('cutoffinterval', $quercusconfig->cutoffinterval, 'local_solsits');
        }
        if ($quercusconfig->cutoffintervalsecondplus) {
            set_config('cutoffintervalsecondplus', $quercusconfig->cutoffintervalsecondplus, 'local_solsits');
        }
        if ($quercusconfig->gradingdueinterval) {
            set_config('gradingdueinterval', $quercusconfig->gradingdueinterval, 'local_solsits');
        }
        upgrade_plugin_savepoint(true, 2023040501, 'local', 'solsits');
    }

    if ($oldversion < 2023040502) {
        $fields = [
            'location_name',
        ];
        foreach ($fields as $field) {
            \local_solsits\helper::create_sits_coursecustomfields($field);
        }
        $table = new xmldb_table('local_solsits_assign');
        $field = new xmldb_field('sitting');
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }
        $field = new xmldb_field('externaldate');
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }
        $field = new xmldb_field('status');
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }
        $field = new xmldb_field('sittingdesc', XMLDB_TYPE_CHAR, 20, null, NULL_ALLOWED, false);
        if ($dbman->field_exists($table, $field)) {
            $dbman->rename_field($table, $field, 'reattempt');
        }

        $table = new xmldb_table('local_solsits_attempts');
        if ($dbman->table_exists($table)) {
            $dbman->drop_table($table);
        }
        $table = new xmldb_table('local_solsits_courses');
        if ($dbman->table_exists($table)) {
            $dbman->drop_table($table);
        }

        $table = new xmldb_table('local_solsits_assign_grades');
        $field = new xmldb_field('attempt');
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        upgrade_plugin_savepoint(true, 2023040502, 'local', 'solsits');
    }

    if ($oldversion < 2023040504) {
        $table = new xmldb_table('local_solsits_assign');
        $field = new xmldb_field('reattempt');
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        $field = new xmldb_field('reattempt', XMLDB_TYPE_INTEGER, 2, XMLDB_UNSIGNED, null, false, 0, 'courseid');
        $dbman->add_field($table, $field);

        $field = new xmldb_field('weighting', XMLDB_TYPE_INTEGER, 3, XMLDB_UNSIGNED, null, false, 100);
        if ($dbman->field_exists($table, $field)) {
            $dbman->change_field_type($table, $field);
        }

        $field = new xmldb_field('assessmentcode', XMLDB_TYPE_CHAR, 255, null, null, false, null, 'availablefrom');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('sequence', XMLDB_TYPE_CHAR, 50, null, null, false, null, 'assessmentcode');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('scale', XMLDB_TYPE_CHAR, 50, null, false, false, '', 'grademarkexempt');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $table = new xmldb_table('local_solsits_assign_grades');
        $key = new xmldb_key('sitsassign', XMLDB_KEY_FOREIGN, ['solassignmentid'], 'local_sits_assign', ['id']);
        $dbman->add_key($table, $key);

        upgrade_plugin_savepoint(true, 2023040504, 'local', 'solsits');
    }

    if ($oldversion < 2023040505) {
        $table = new xmldb_table('local_solsits_assign');
        $field = new xmldb_field('assessmentname', XMLDB_TYPE_CHAR, 100, null, null, false, null, 'assessmentcode');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_plugin_savepoint(true, 2023040505, 'local', 'solsits');
    }

    if ($oldversion < 2023040508) {
        $table = new xmldb_table('local_solsits_assign_grades');
        $field = new xmldb_field('response', XMLDB_TYPE_TEXT, null, null, false, null, null);
        if ($dbman->field_exists($table, $field)) {
            $dbman->rename_field($table, $field, 'message');
        }
        $field = new xmldb_field('response_code', XMLDB_TYPE_CHAR, 255, null, false, false, null);
        if ($dbman->field_exists($table, $field)) {
            $dbman->rename_field($table, $field, 'response');
        }
        $field = new xmldb_field('processed');
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }
        upgrade_plugin_savepoint(true, 2023040508, 'local', 'solsits');
    }

    if ($oldversion < 2024021802) {
        $table = new xmldb_table('local_solsits_assign');
        $field = new xmldb_field('title', XMLDB_TYPE_CHAR, 255, null, true, false, null, null);
        if (!$dbman->field_exists($table, $field)) {
            $dbman->change_field_type($table, $field);
        }
        upgrade_plugin_savepoint(true, 2024021802, 'local', 'solsits');
    }

    return true;
}
