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
        $config = get_config('local_solsits');
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
            if (!$sitsassign) {
                continue;
            }
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
                    $data->style = 'solent-sea-blue';
                    // SITSassign has the duedate from SITS, but the activity will have the real date.
                    $assign = new assign($context, $cm, $course);
                    $duedate = $assign->get_instance()->duedate;

                    $data->duedate = userdate($duedate, $strftimedatetimeaccurate);
                    $submission = $DB->get_record('assign_submission', [
                        'userid' => $USER->id,
                        'assignment' => $cm->instance,
                        'latest' => 1,
                    ]);
                    if (!$submission) {
                        $data->status = get_string('notsubmitted', 'local_solsits');
                    } else {
                        $data->status = get_string('submissionstatus_' . $submission->status, 'assign');
                    }

                    $submittedstatuses = ['submitted', 'marked'];
                    if ($submission && !in_array($submission->status, $submittedstatuses)) {
                        $data->style = 'solent-burgundy-light';
                        $data->submissiondue = get_string('submissiondue', 'local_solsits', $data->duedate);
                    }
                    if ($submission && in_array($submission->status, $submittedstatuses)) {
                        $data->submissiondate = get_string('submissiondueandsubmitted', 'local_solsits', (object)[
                            'duedate' => userdate($duedate, $strftimedatetimeaccurate),
                            'submissiondate' => userdate($submission->timemodified, $strftimedatetimeaccurate),
                        ]);
                    }
                } catch (Exception $ex) {
                    $data->error = get_string('sitsassign:deletedreport', 'local_solsits', $sitsassign->get('title'));
                }
            }
            $assdata->assigns[] = $data;
        }
        $assdata->hasassignments = count($assdata->assigns) > 0;
        return $OUTPUT->render_from_template('local_solsits/importantdates', $assdata);
    }

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
    public static function assignmentintro($shortcode, $args, $content, $env, $next) {
        global $OUTPUT;
        $config = get_config('local_solsits');
        $context = $env->context;
        $data = new stdClass();
        $sitsassign = sitsassign::get_record(['cmid' => $context->instanceid]);
        if (!$sitsassign) {
            return '';
        }
        // Note: Side admins will have both.
        $isstudent = has_capability('mod/assign:submit', $context);
        if ($isstudent) {
            $data = self::studentintro($sitsassign, $context, $config);
            $data->hasstudentinfo = true;
        }

        $istutor = has_capability('mod/assign:grade', $context);
        if ($istutor) {
            $data->tutorinfo = self::tutorintro($sitsassign, $context, $config);
            $data->hastutorinfo = count($data->tutorinfo) > 0;
        }

        return $OUTPUT->render_from_template('local_solsits/assignmentintro', $data);
    }

    /**
     * Adds assignment info for students about their submission
     *
     * @param sitsassign $sitsassign
     * @param context $context
     * @param stdClass $config
     * @return stdClass
     */
    private static function studentintro($sitsassign, $context, $config): stdClass {
        global $DB, $USER;
        $data = new stdClass();
        $data->style = 'solent-sea-blue';
        $coursecontext = $context->get_course_context();
        [$course, $cm] = get_course_and_cm_from_cmid($sitsassign->get('cmid'), 'assign');
        $strftimedatetimeaccurate = '%d %B %Y, %I:%M:%S %p';
        $assign = new assign($context, $cm, $course);
        // Apply overrides.
        $assign->update_effective_access($USER->id);
        $duedate = $assign->get_instance()->duedate;

        $data->duedate = userdate($duedate, $strftimedatetimeaccurate);
        $userdata = $DB->get_record('assign_user_flags', [
            'userid' => $USER->id,
            'assignment' => $cm->instance,
        ]);
        if ($userdata && $userdata->extensionduedate > 0) {
            $data->extensionduedate = get_string('userextensiondate', 'mod_assign',
                userdate($userdata->extensionduedate, $strftimedatetimeaccurate));
        }
        $submission = $DB->get_record('assign_submission', [
            'userid' => $USER->id,
            'assignment' => $cm->instance,
            'latest' => 1,
        ]);
        $submissionplugins = $assign->is_any_submission_plugin_enabled();
        if ($submission && $submission->status != ASSIGN_SUBMISSION_STATUS_NEW) {
            $data->status = get_string('submissionstatus_' . $submission->status, 'assign');
        } else {
            if (!$submissionplugins) {
                $data->status = get_string('noonlinesubmissions', 'assign');
            } else {
                get_string('noattempt', 'assign');
            }
        }

        $submittedstatuses = ['submitted', 'marked'];
        if ($submission && !in_array($submission->status, $submittedstatuses)) {
            $data->style = 'solent-burgundy-light';
            $data->submissiondue = get_string('submissiondue', 'local_solsits', userdate($duedate, $strftimedatetimeaccurate));
        }
        if ($submission && in_array($submission->status, $submittedstatuses)) {
            $data->submissiondate = get_string('submissiondueandsubmitted', 'local_solsits', (object)[
                'duedate' => userdate($duedate, $strftimedatetimeaccurate),
                'submissiondate' => userdate($submission->timemodified, $strftimedatetimeaccurate),
            ]);
        }
        if ($sitsassign->get('reattempt') > 0) {
            $data->reattempt = $config->assignmentmessage_studentreattempt;
        }

        if ($brief = $DB->get_record('course_modules', ['idnumber' => 'brief:' . $sitsassign->get('sitsref')])) {
            $briefcm = get_fast_modinfo($course)->cms[$brief->id];
            $data->assignmentbrief = (object)[
                'id' => $briefcm->id,
                'title' => $briefcm->name,
                'url' => $briefcm->get_url(),
            ];
        }
        return $data;
    }

    /**
     * Adds assignment set-up info for tutors
     *
     * @param sitsassign $sitsassign
     * @param context $context
     * @param stdClass $config
     * @return string[]
     */
    private static function tutorintro($sitsassign, $context, $config): array {
        $now = time();
        $tutorinfo = [];
        [$course, $cm] = get_course_and_cm_from_cmid($sitsassign->get('cmid'), 'assign');
        $strftimedatetimeaccurate = '%d %B %Y, %I:%M:%S %p';
        $assign = new assign($context, $cm, $course);
        $duedate = $assign->get_instance()->duedate;
        if ($duedate > $now) {
            $mailinglist = get_config('local_solsits', 'assignmentduedatechange_mailinglist') ?? '';
            if (empty($mailinglist)) {
                $tutorinfo[] = get_string('checkduedatenomessage', 'local_solsits', [
                    'duedate' => userdate($duedate, $strftimedatetimeaccurate),
                ]);
            } else {
                $tutorinfo[] = get_string('checkduedate', 'local_solsits', [
                    'duedate' => userdate($duedate, $strftimedatetimeaccurate),
                    'sitsref' => $sitsassign->get('sitsref'),
                    'title' => $sitsassign->get('title'),
                    'cmid' => $sitsassign->get('cmid'),
                ]);
            }
        }
        if (!$cm->visible) {
            $tutorinfo[] = get_string('assignmentnotvisible', 'local_solsits');
        }
        $submissionplugins = $assign->is_any_submission_plugin_enabled();
        if (!$submissionplugins) {
            $tutorinfo[] = get_string('nosubmissionplugins', 'local_solsits');
        }
        $gradingdue = $assign->get_instance()->gradingduedate;
        if ($now > $duedate && $now < $gradingdue) {
            $tutorinfo[] = get_string('gradingdueby', 'local_solsits', userdate($gradingdue, $strftimedatetimeaccurate));
        }
        if ($cm->availability != null) {
            if ($sitsassign->get('reattempt') > 0) {
                $groupslink = html_writer::link(
                    new moodle_url('/group/index.php', ['id' => $sitsassign->get('courseid')]),
                    $sitsassign->get('title')
                );
                $tutorinfo[] = get_string('reattemptavailability', 'local_solsits', $groupslink);
            } else {
                $tutorinfo[] = get_string('availabilityconditions', 'local_solsits');
            }
        }

        return $tutorinfo;
    }
}
