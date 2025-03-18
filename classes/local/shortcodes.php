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

namespace local_solsits\local;

use assign;
use context_course;
use context_module;
use Exception;
use html_writer;
use local_solsits\sitsassign;
use moodle_url;
use stdClass;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/assign/locallib.php');

/**
 * Class shortcodes
 *
 * @package    local_solsits
 * @copyright  2025 Southampton Solent University {@link https://www.solent.ac.uk}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class shortcodes {
    /**
     * Returns a list of summative SITS assignments.
     *
     * @param string $shortcode The shortcode.
     * @param object $args The arguments of the code.
     * @param string|null $content The content, if the shortcode wraps content.
     * @param object $env The filter environment (contains context, noclean and originalformat).
     * @param Closure $next The function to pass the content through to process sub shortcodes.
     * @return string The new content.
     */
    public static function summativeassignments($shortcode, $args, $content, $env, $next) {
        global $DB, $OUTPUT, $USER;
        $context = $env->context;
        $coursecontext = $context->get_course_context();
        $courseid = $coursecontext->instanceid;
        $sitsassigns = sitsassign::get_records(['courseid' => $courseid]);
        if (count($sitsassigns) == 0) {
            return get_string('noassignmentsyet', 'local_solsits');
        }
        $assdata = new stdClass();
        $assdata->assigns = [];
        $strftimedatetimeaccurate = '%d %B %Y, %I:%M:%S %p';
        foreach ($sitsassigns as $sitsassign) {
            $data = new stdClass();
            $data->link = $sitsassign->get('title');
            if ($sitsassign->get('duedate') == 0) {
                $data->duedate = get_string('noduedate', 'local_solsits');
            }
            if ($sitsassign->get('cmid') > 0) {
                try {
                    // Though there's a cmid, it might still have been deleted.
                    [$course, $cm] = get_course_and_cm_from_cmid($sitsassign->get('cmid'), 'assign');
                    if (!$cm->visible) {
                        continue;
                    }
                    $url = new moodle_url('/mod/assign/view.php', ['id' => $sitsassign->get('cmid')]);
                    $data->link = html_writer::link($url, $cm->name);
                    // SITSassign has the duedate from SITS, but the activity will have the real date.
                    $context = context_course::instance($course->id);
                    $assign = new assign($context, $cm, $course);

                    $data->duedate = userdate($assign->get_instance()->duedate, $strftimedatetimeaccurate);
                    $submission = $DB->get_record('assign_submission', [
                        'userid' => $USER->id,
                        'assignment' => $cm->instance,
                        'latest' => 1,
                    ]);
                    if ($submission) {
                        $data->status = $submission->status;
                    } else {
                        $data->status = get_string('notsubmitted', 'local_solsits');
                    }
                } catch (Exception $ex) {
                    echo $ex->getMessage() . ' ' . $sitsassign->get('cmid');
                    $data->error = get_string('sitsassign:deletedreport', 'local_solsits', $sitsassign->get('title'));
                }
            }
            $assdata->assigns[] = $data;
        }
        $assdata->hasassignments = count($assdata->assigns) > 0;
        return $OUTPUT->render_from_template('local_solsits/importantdates', $assdata);
    }
}
