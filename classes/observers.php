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
 * Event observers
 *
 * @package   local_solsits
 * @author    Mark Sharp <mark.sharp@solent.ac.uk>
 * @copyright 2022 Solent University {@link https://www.solent.ac.uk}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_solsits;

use core_user;
use html_writer;
use moodle_url;

/**
 * Observers class for local_solsits.
 */
class observers {

    /**
     * Actions on deletion of sits assignment.
     *
     * @param \core\event\course_module_deleted $event
     * @return void
     */
    public static function course_module_deleted(\core\event\course_module_deleted $event) {
        // If cm is assignment.
        // If is formative assignment.
        // Send email to module leader and LTU.
        // Include the number of submissions.
        // Try to recover from the recycle bin.

        // Alternatively use the hook _pre_course_module_delete (e.g. in Recycle bin).
        return;
    }

    /**
     * Process any course_module_updated events
     *
     * @param \core\event\course_module_updated $event
     * @return void
     */
    public static function course_module_updated(\core\event\course_module_updated $event): void {
        global $DB;
        $modname = $event->other['modulename'];
        if ($modname != 'assign') {
            return;
        }
        $cmid = $event->objectid;
        if (!helper::is_summative_assignment($cmid)) {
            return;
        }
        $config = get_config('local_solsits');
        $problems = [];
        $coursesection = 0;
        try {
            [$course, $cm] = get_course_and_cm_from_cmid($cmid, 'assign');
            $problems = [];
            if ($cm->visible == 0) {
                $problems[] = 'hidden';
            }
            $coursesection = $DB->get_record('course_sections', ['id' => $cm->section]);
            if ($coursesection->section != 1) {
                $problems[] = 'wrongsection';
            }
        } catch (\Exception $ex) {
            return;
        }
        if (count($problems) == 0) {
            return;
        }
        // Don't send if the user is site admin or AIS WS user.
        $triggerer = core_user::get_user($event->userid);
        if (is_siteadmin($triggerer) || $triggerer->username == 'aisws') {
            // No notification required.
            return;
        }
        $moduleleader = fullname($triggerer);
        $assignmenturl = new moodle_url('/course/mod.php', ['update' => $cmid]);
        $assignmentlink = html_writer::link($assignmenturl, $cm->name);
        $courseurl = new moodle_url('/course/view.php', ['id' => $course->id]);
        $courselink = html_writer::link($courseurl, $course->fullname);
        $subject = html_to_text(get_string('assignmentsettingserrorsubject', 'local_solsits', ['idnumber' => $cm->idnumber]));
        $body = $config->assignmentwarning_body;
        $body = str_replace([
                '{IDNUMBER}',
                '{COURSELINK}',
                '{MODULELEADER}',
                '{ASSIGNMENTLINK}',
            ],
            [
                $cm->idnumber,
                $courselink,
                $moduleleader,
                $assignmentlink,
            ], $body);
        foreach ($problems as $problem) {
            $problemtext = clean_text($config->{'assignmentwarning_' . $problem});
            $body .= $problemtext;
        }
        $recipients = [
            core_user::get_user_by_username('admin'),
            core_user::get_user_by_username('guidedlearning'),
            $triggerer,
        ];
        $noreply = core_user::get_noreply_user();
        foreach ($recipients as $recipient) {
            email_to_user($recipient, $noreply, $subject, html_to_text($body), $body);
        }
    }
}
