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
 * Apply appropriate templates to course pages
 *
 * @package   local_solsits
 * @author    Mark Sharp <mark.sharp@solent.ac.uk>
 * @copyright 2022 Solent University {@link https://www.solent.ac.uk}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_solsits\task;

use core\task\scheduled_task;
use core_course_external;
use local_solsits\soltemplate;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/course/externallib.php');
require_once($CFG->dirroot . '/lib/enrollib.php');

/**
 * Task to apply templates
 */
class applytemplate_task extends scheduled_task {
    /**
     * Name of the task
     *
     * @return string
     */
    public function get_name(): string {
        return get_string('applytemplatetask', 'local_solsits');
    }

    /**
     * {@inheritDoc}
     *
     * @return void
     */
    public function execute() {
        global $DB;
        $max = get_config('local_solsits', 'maxtemplates');
        $count = 0;
        // Might want to order by most recently created template to save some looping.
        $activetemplates = soltemplate::get_records(['enabled' => 1]);
        if (count($activetemplates) == 0) {
            // There are no templates, so there's nothing to do.
            return;
        }

        foreach ($activetemplates as $activetemplate) {
            if ($count >= $max) {
                // Stop here don't do any more processing.
                break;
            }
            $templatekey = $activetemplate->get('pagetype') . '_' . $activetemplate->get('session');
            $untemplateds = soltemplate::get_templateapplied_records(
                $activetemplate->get('pagetype'),
                $activetemplate->get('session'),
                0
            );
            $countuntemplateds = count($untemplateds);
            if ($countuntemplateds == 0) {
                // No courses to process for this template.
                continue;
            }
            foreach ($untemplateds as $untemplated) {
                if ($count >= $max) {
                    // Stop here don't do any more processing.
                    break;
                }
                $course = $DB->get_record('course', ['id' => $untemplated->id]);
                if (!$course) {
                    // This shouldn't happen, but it's possible if a course has been deleted.
                    mtrace(get_string('error:coursenotexist', 'local_solsits', $untemplated->id));
                    // Skip this and do the next course.
                    continue;
                }
                // Check the target course is not visible and has not been edited.
                if ($course->visible == 1) {
                    mtrace(get_string('error:coursevisible', 'local_solsits', $course->idnumber));
                    continue;
                }
                $activities = $DB->get_records('course_modules', ['course' => $course->id]);
                if (count($activities) > 1) {
                    // Something has happened here. Do not apply template.
                    // Course is always created with a forum.
                    mtrace(get_string('error:courseedited', 'local_solsits', $course->idnumber));
                    continue;
                }
                $enrolledusers = enrol_get_course_users($course->id);
                if (count($enrolledusers) > 0) {
                    mtrace(get_string('error:usersenrolledalready', 'local_solsits', $course->idnumber));
                    continue;
                }
                $count++;

                // Apply the template.
                // This will delete existing content.
                // This will remove all existing enrolments.
                $courseexternal = new core_course_external();
                $courseexternal->import_course($activetemplate->get('courseid'), $course->id, 1);
                $course->visible = 1;
                $course->customfield_templateapplied = 1;
                $course->format_coursedisplay = 0;
                update_course($course);
                rebuild_course_cache($course->id);

                // Readd manual enrolment method.
                $plugin = enrol_get_plugin('manual');
                $instance = $DB->get_record('enrol', array('courseid' => $course->id, 'enrol' => 'manual'), '*');

                if (!$instance) {
                    $fields = array(
                        'status'          => '0',
                        'roleid'          => '5',
                        'enrolperiod'     => '0',
                        'expirynotify'    => '0',
                        'notifyall'       => '0',
                        'expirythreshold' => '86400'
                    );
                    $instance = $plugin->add_instance($course, $fields);
                }

                // Mark the solcourse record as templated.
                mtrace(get_string('templateapplied', 'local_solsits', [
                    'templatekey' => $templatekey,
                    'courseidnumber' => $course->idnumber
                ]));
            }
        }
    }
}
