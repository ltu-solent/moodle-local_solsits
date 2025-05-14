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

use assign;
use core\context;
use core_course\customfield\course_handler;
use core_customfield\category;
use core_customfield\field;
use DateTime;
use Exception;
use NumberFormatter;
use stdClass;

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
            soltemplate::COURSE => soltemplate::COURSE,
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
            $yearplusone = (string)((int)substr($year, 2, 2) + 1);
            $options[$year . '/' . $yearplusone] = $year . '/' . $yearplusone;
        }
        return array_reverse($options);
    }

    /**
     * Returns the current academic year, assuming it starts on 1st August.
     *
     * @return string Formatted YYYY/YY
     */
    public static function get_currentacademicyear(): string {
        $cyear = date('Y');
        $cmonth = date('n');
        $yearend = $cyear;
        $yearstart = $cyear;
        if ($cmonth < 8) {
            $yearstart = $cyear - 1;
        } else {
            $yearend = $cyear + 1;
        }
        $yearend = substr($yearend, 2, 2);
        return "$yearstart/$yearend";
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
     * Get ranges menu
     *
     * @return array
     */
    public static function get_ranges_menu(): array {
        $menu = [];
        $nf = new NumberFormatter(current_language(), NumberFormatter::SPELLOUT);
        for ($x = 0; $x < 10; $x++) {
            $endint = $x + 1;
            $start = $nf->format($x);
            $end = $nf->format($endint);
            $value = get_string('rangeweeks', 'local_solsits', [
                'start' => $start,
                'end' => $end,
            ]);
            $menu['r' . $x . '-' . $endint] = $value;
        }
        return $menu;
    }

    /**
     * Gets timestamp for given index e.g. r0-1
     *
     * @param string $index
     * @return array|null
     */
    public static function map_range($index): ?array {
        $result = preg_match('/r(\d+)-(\d+)/', $index, $matches);
        if ($result) {
            $start = $matches[1];
            $end = $matches[2];
            return [
                'start' => strtotime("+{$start} weeks"),
                'end' => strtotime("+{$end} weeks"),
            ];
        }
        return null;
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
            'link' => '',
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
            'link' => '',
        ]);
        $predefined = [
            'academic_year' => [
                'name' => 'Academic year',
            ],
            'level_code' => [
                'name' => 'Academic level',
            ],
            'location_code' => [
                'name' => 'Location code',
            ],
            'location_name' => [
                'name' => 'Location',
            ],
            'module_code' => [
                'name' => 'Module or Course code',
            ],
            'module_occurrence' => [
                'name' => 'Module occurrence',
            ],
            'org_2' => [
                'name' => 'Faculty/School',
            ],
            'org_3' => [
                'name' => 'Department',
            ],
            'pagetype' => [
                'name' => 'Page type',
                'configdata' => $configdatainvisible,
            ],
            'period_code' => [
                'name' => 'Period',
                'configdata' => $configdatainvisible,
            ],
            'related_courses' => [
                'name' => 'Related courses',
            ],
            'subject_area' => [
                'name' => 'Subject area',
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
                    'link' => '',
                ]),
            ],
        ];

        if (!array_key_exists($shortname, $predefined)) {
            // Not defined. Do nothing.
            return;
        }

        $category = category::get_record([
            'name' => 'Student Records System',
            'area' => 'course',
            'component' => 'core_course',
        ]);
        if (!$category) {
            // No category, so create and use it.
            $category = new category(0, (object)[
                'name' => 'Student Records System',
                'description' => 'Fields managed by the university\'s Student records system. Do not change unless asked to.',
                'area' => 'course',
                'component' => 'core_course',
                'contextid' => context\system::instance()->id,
            ]);
            $category->save();
        }
        $field = field::get_record([
            'shortname' => $shortname,
            'categoryid' => $category->get('id'),
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
        $data->description = '';
        $data->descriptionformat = 0;
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
        $value = self::get_customfield($courseid, 'templateapplied');
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
        return self::get_customfield($courseid, 'pagetype');
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
        $config = get_config('local_solsits');
        // Filter out any non-Solent scales, if any are set.
        $solscales = [];
        // If the grademarkscale or grademarkexemptscales are already being used
        // by the cm, then include them in the drop-down, else just the numeric scale?
        if (isset($config->grademarkscale) && $config->grademarkscale != '') {
            $solscales[] = $config->grademarkscale;
        }
        if (isset($config->grademarkexemptscale) && $config->grademarkexemptscale != '') {
            $solscales[] = $config->grademarkexemptscale;
        }
        if (isset($config->numericscale) && $config->numericscale != '') {
            $solscales[] = $config->numericscale;
        }
        // If no solscales are set, return the default set.
        if (count($solscales) == 0) {
            return $scales;
        }
        $scales = array_filter($scales, function($scaleid) use ($solscales) {
            return in_array($scaleid, $solscales);
        }, ARRAY_FILTER_USE_KEY);
        // Format: [id => 'scale name'].
        return $scales;
    }

    /**
     * Given a coursemodule id, returns if this is a summative assignment.
     * Basically a non-empty idnumber.
     *
     * @param int $cmid Course module id
     * @return boolean
     */
    public static function is_summative_assignment($cmid) {
        if (!self::issolsits()) {
            return false;
        }
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
     * @param int $cmid Coursemodule id
     * @return boolean
     */
    public static function can_release_grades($cmid): bool {
        global $DB, $PAGE;
        $issummative = self::is_summative_assignment($cmid);
        if (!$issummative) {
            return true;
        }
        $context = context\module::instance($cmid);
        [$course, $cm] = get_course_and_cm_from_cmid($cmid, 'assign');
        // Update in upgrade.php script transfers capabilities from local/quercus_tasks:releasegrades.
        $hascapability = has_capability('local/solsits:releasegrades', $context);
        $locked = $DB->get_field_select('grade_items', 'locked', 'itemmodule = ? AND iteminstance = ?', ['assign', $cm->instance]);
        if ($hascapability && $locked == 0) {
            return true;
        } else if ($locked != 0) {
            return true;
        }
        return false;
    }

    /**
     * This returns the parameter that was passed in. It's used by component_class_callback as a way
     * to ensure core changes don't affect normal core unit tests. If this function doesn't exist,
     * a default value matching what Moodle expects is returned.
     *
     * @param mixed ...$return One or more arguments.
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

    /**
     * Given a course module id, is this a sits assignment?
     *
     * @param int $cmid
     * @return boolean
     */
    public static function is_sits_assignment($cmid) {
        global $DB;
        return $DB->record_exists('local_solsits_assign', ['cmid' => $cmid]);
    }

    /**
     * Takes a timestamp and modifies it returning a new timestamp
     *
     * @param int $timestamp
     * @param string $timestring A time hh:mm - no seconds.
     * @param string $modifystring A PHP modify string pattern
     * @return int The altered timestamp
     */
    public static function set_time($timestamp, $timestring = '16:00', $modifystring = '') {
        $dt = new DateTime();
        $dt->setTimestamp($timestamp);
        if ($modifystring) {
            $dt->modify($modifystring);
        }
        if ($timestring) {
            // Should only be 2 parts.
            $timeparts = explode(':', $timestring);
            $dt->setTime($timeparts[0], $timeparts[1]);
        }
        return $dt->getTimestamp();
    }

    /**
     * Convert grades into SITS form.
     *
     * @param int $scaleid
     * @param ?float $grade
     * @return int
     */
    public static function convert_grade($scaleid, $grade) {
        $config = get_config('local_solsits');
        // Until we know if the integration and SITS can cope with decimal grades, use rounding half up.
        // This will result in 49.4 -> 49; 49.5 -> 50; 49.9 -> 50.
        if ($grade != null) {
            $grade = (int) round($grade, 0, PHP_ROUND_HALF_UP);
        }
        $converted = $grade;
        if ($scaleid == $config->grademarkscale) { // Solent gradescale.
            $converted = -1;
            switch ($grade) {
                case 18:
                    $converted = 100; // A1.
                    break;
                case 17:
                    $converted = 92; // A2.
                    break;
                case 16:
                    $converted = 83; // A3.
                    break;
                case 15:
                    $converted = 74; // A4.
                    break;
                case 14:
                    $converted = 68; // B1.
                    break;
                case 13:
                    $converted = 65; // B2.
                    break;
                case 12:
                    $converted = 62; // B3.
                    break;
                case 11:
                    $converted = 58; // C1.
                    break;
                case 10:
                    $converted = 55; // C2.
                    break;
                case 9:
                    $converted = 52; // C3.
                    break;
                case 8:
                    $converted = 48; // D1.
                    break;
                case 7:
                    $converted = 45; // D2.
                    break;
                case 6:
                    $converted = 42; // D3.
                    break;
                case 5:
                    $converted = 35; // F1.
                    break;
                case 4:
                    $converted = 20; // F2.
                    break;
                case 3:
                    $converted = 15; // F3.
                    break;
                case 2:
                    $converted = 1; // S.
                    break;
                case 1:
                    $converted = 0; // N.
                    break;
                default:
                    $converted = -1;
                    break;
            }
        } else if ($scaleid == $config->grademarkexemptscale) {
            if ($grade == null || $grade == 0 || $grade == -1) {
                $converted = 0;
            } else {
                // Different languages use different separators (i.e. , .) in float numbers.
                // unformat_float normalises this.
                // Grades are stored as floats.
                // The converted grade is 1 point lower than the index.
                $converted = (int)unformat_float($grade) - 1;
            }
        }
        // The task get_new_grades will strip the decimal and convert -1 to zero for SITS.
        return $converted;
    }

    /**
     * Grading alerts
     *
     * @param \local_solalerts\hook\after_course_content_header $hook
     * @param object $cm
     * @param object $course
     * @param object $context
     * @return void
     */
    public static function gradingalerts(\local_solalerts\hook\after_course_content_header $hook, $cm, $course, $context): void {
        $issummative = self::is_summative_assignment($cm->id);
        if (!$issummative) {
            return;
        }
        $issitsassignment = self::is_sits_assignment($cm->id);
        $srs = get_string('quercus', 'local_solsits');
        if ($issitsassignment) {
            $srs = get_string('gatewaysits', 'local_solsits');
        }
        $assign = new assign($context, $cm, $course);
        $locked = $assign->get_grade_item()->locked;
        $gradingalert = false;
        if (empty($locked) || $locked == 0) {
            $text = get_config('local_solsits', 'assignmentmessage_marksuploadinclude');
            $text = str_replace('{SRS}', $srs, $text);
            $alert = new \core\output\notification(clean_text($text), \core\notification::INFO);
            $hook->add_alert($alert);
            $gradingalert = true;
        } else {
            $alert = new \core\output\notification(get_string('gradeslocked', 'local_solsits'), \core\notification::INFO);
            $hook->add_alert($alert);
            $gradingalert = true;
        }
        $text = get_config('local_solsits', 'assignmentmessage_reattempt');
        if ($issitsassignment) {
            $sitsassign = sitsassign::get_record(['cmid' => $cm->id]);
            $reattempt = $sitsassign->get('reattempt');
            if (($reattempt > 0) && $locked == 0) {
                $reattemptstring = get_string('reattempt' . $reattempt, 'local_solsits');
                $text = str_replace('{REATTEMPT}', $reattemptstring, $text);
                $alert = new \core\output\notification(clean_text($text), \core\notification::ERROR);
                $hook->add_alert($alert);
                $gradingalert = true;
            }
        }

        if (!$gradingalert) {
            $alert = new \core\output\notification("This is a {$srs} assignment", \core\notification::INFO);
            $hook->add_alert($alert);
        }
    }

    /**
     * Does this assignment look as it should? Perhaps it's been copied.
     *
     * @param \local_solalerts\hook\after_course_content_header $hook
     * @param object $cm
     * @param object $course
     * @param object $context
     * @return void
     */
    public static function badassignalerts(\local_solalerts\hook\after_course_content_header $hook, $cm, $course, $context): void {
        $issummative = self::is_summative_assignment($cm->id);
        if (!$issummative) {
            return;
        }
        // Only show errors to people who can grade assignments.
        if (!has_capability('mod/assign:grade', $context)) {
            return;
        }
        // A Quercus assignment idnumber has two parts: TYPE_YEAR
        // A SITS assignment is listed in the local_solsits_assign table.
        $assignidnumberparts = explode('_', $cm->idnumber);
        $assignpartcount = count($assignidnumberparts);
        // A Quercus course idnumber has two parts: MODULECODE_NUMBER.
        // A SITS course has MODULECODE_OCC_PERIOD_YEAR.
        $courseidnumberparts = explode('_', $course->idnumber);
        $coursepartcount = count($courseidnumberparts);

        if ($assignpartcount == 2 && $coursepartcount > 2) {
            // This very much looks like a Quercus Assignment on a SITS course.
            // But we have an exception for SPAN1_2022/23.
            $isspan1exception = (strpos($course->idnumber, 'SPAN1_2022/23') > 0);
            if (!$isspan1exception) {
                $alert = new \core\output\notification(
                    get_string('quercusassignmentonsitscourse', 'local_solsits', [
                        'assignidnumber' => $cm->idnumber,
                        'courseidnumber' => $course->idnumber,
                    ]),
                    \core\notification::ERROR
                );
                $hook->add_alert($alert);
            }
        }
        // The SITS assignment idnumber (sitsref) should match with the courseid in sits assign table.
        $issitsassign = sitsassign::get_record(['sitsref' => $cm->idnumber, 'courseid' => $course->id]);
        if ($assignpartcount > 2 && !$issitsassign) {
            $alert = new \core\output\notification(
                get_string('wrongassignmentonwrongcourse', 'local_solsits', [
                    'assignidnumber' => $cm->idnumber,
                    'courseidnumber' => $course->idnumber,
                ]),
                \core\notification::ERROR
            );
            $hook->add_alert($alert);
        }
    }

    /**
     * Sets the scale depending on a number of conditions
     *
     * @param stdClass $assignment The assignment object from the ws.
     * @return stdClass The assignment object updated with the scale.
     */
    public static function set_scale(stdClass $assignment): stdClass {
        global $DB;
        $config = get_config('local_solsits');
        // For now, the scale isn't being set by AIS, so we're defaulting to defaultscale, if set.
        // If we go live with defaultscale after an assignment has already been created, we don't want to
        // change the scale, but maybe it's ok to set defaultscale.
        // This will ultimately be handled by add_assignment or update_assignment functions.
        $defaultscale = $config->defaultscale ?? '';
        // Use numeric scale by default for all new assignments.
        if (empty($assignment->scale)) {
            $assignment->scale = $defaultscale;
        }
        // If this is not a reattempt, we're just going with whatever the default is.
        if ($assignment->reattempt == 0) {
            return $assignment;
        }

        // Find the original sitsref by replacing the final part of the current sitsref with zero.
        $attemptpos = strrpos($assignment->sitsref, '_');
        if ($attemptpos === false) {
            return $assignment;
        }
        $originalsitsref = substr($assignment->sitsref, 0, $attemptpos) . '_0';
        $originalsitsassign = $DB->get_record('local_solsits_assign', ['sitsref' => $originalsitsref]);
        if (!$originalsitsassign) {
            return $assignment;
        }

        try {
            [$course, $cm] = get_course_and_cm_from_cmid($originalsitsassign->cmid, 'assign');
            $context = context\module::instance($cm->id);
            $originalassign = new \assign($context, $cm, $course);
            if (!$originalassign) {
                return $assignment;
            }
            // If a scale isn't being used on the original return the default.
            if (!$originalassign->get_grade_item()->scaleid) {
                return $assignment;
            }
            // Use the scale being used on the old assignment.
            if ($originalsitsassign->grademarkexempt) {
                $originalscale = 'grademarkexemptscale';
            } else {
                $originalscale = 'grademarkscale';
            }
            $assignment->scale = $originalscale;
        } catch (Exception $ex) {
            return $assignment;
        }

        return $assignment;
    }
}

