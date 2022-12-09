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
use local_solsits\solcourse;
use local_solsits\soltemplate;

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
        // Get all courses/modules that haven't had their template applied.
        $untemplateds = solcourse::get_records_select('templateapplied = 0');
        $countuntemplateds = count($untemplateds);
        if ($max > $countuntemplateds) {
            $max = $countuntemplateds;
        }
        $availabletemplates = [];
        foreach ($untemplateds as $untemplated) {
            if ($count >= $max) {
                // Stop here don't do any more processing.
                break;
            }
            $templatekey = $untemplated->get('pagetype') . '_' . $untemplated->get('session');
            // Does the template exist for this pagetype and session?
            if (!isset($availabletemplates[$templatekey])) {
                $template = soltemplate::get_records_select(
                    "pagetype = :pagetype AND session = :session AND enabled = 1",
                    [
                        'pagetype' => $untemplated->get('pagetype'),
                        'session' => $untemplated->get('session')
                    ]
                );
                if (count($template) == 0) {
                    // There is no template here, so skip to the next untemplated.
                    continue;
                }
                $availabletemplates[$templatekey] = reset($template);
            }

            $course = $DB->get_record('course', ['id' => $untemplated->get('courseid')]);
            if (!$course) {
                // This shouldn't happen, but it's possible if a course has been deleted.
                mtrace(get_string('error:coursenotexist', 'local_solsits', $untemplated->get('courseid')));
                // Skip this and do the next course.
                continue;
            }
            $count++;
            // Check that the target is actually empty.
                // What do you do if it's not?

            // Apply the template (this will delete existing content).
            $courseexternal = new core_course_external();
            $courseexternal->import_course($availabletemplates[$templatekey]->get('courseid'), $course->id, 1);
            $course->visible = 1;
            update_course($course);
            rebuild_course_cache($course->id);

            // Mark the solcourse record as templated.
            $untemplated->set('templateapplied', 1);
            $untemplated->save();
            mtrace(get_string('templateapplied', 'local_solsits', [
                'templatekey' => $templatekey,
                'courseidnumber' => $course->idnumber
            ]));
        }
    }
}
