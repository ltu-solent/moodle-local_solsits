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
 * Sol assignment
 *
 * @package   local_solsits
 * @author    Mark Sharp <mark.sharp@solent.ac.uk>
 * @copyright 2022 Solent University {@link https://www.solent.ac.uk}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_solsits;

use assign;
use context_module;
use core\persistent;
use core_date;
use DateTime;
use grade_item;
use lang_string;
use mod_assign_external;
use plagiarism_plugin_turnitin;
use stdClass;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot . '/course/modlib.php');
require_once($CFG->dirroot . '/mod/assign/locallib.php');
require_once($CFG->dirroot . '/mod/assign/externallib.php');

/**
 * The SITS assignment as it's coming from SITS
 */
class sitsassign extends persistent {
    /**
     * Table name for sits assignment.
     */
    const TABLE = 'local_solsits_assign';

    /**
     * Moodle assignment formdata
     *
     * @var stdClass
     */
    private $formdata;

    /**
     * Default assignment config settings
     *
     * @var object
     */
    private $defaultconfig;

    /**
     * Return the definition of the properties of this model.
     *
     * @return array
     */
    protected static function define_properties() {
        return [
            'sitsref' => [
                'type' => PARAM_TEXT,
            ],
            // Cmid can be 0 if the course has not been templated.
            'cmid' => [
                'type' => PARAM_INT,
                'default' => 0,
            ],
            'courseid' => [
                'type' => PARAM_INT,
            ],
            // Usually 0,1,2,3. Reattempt 0 is the first attempt.
            'reattempt' => [
                'type' => PARAM_INT,
                'default' => 0,
            ],
            'title' => [
                'type' => PARAM_TEXT,
            ],
            'weighting' => [
                'type' => PARAM_INT,
                'default' => 100,
            ],
            // Although we need a duedate to create an assignment, we allow 0 to store the record.
            'duedate' => [
                'type' => PARAM_INT,
                'default' => 0,
            ],
            'grademarkexempt' => [
                'type' => PARAM_BOOL,
                'default' => 0,
            ],
            'scale' => [
                'type' => PARAM_ALPHANUMEXT,
                'default' => '',
            ],
            'availablefrom' => [
                'type' => PARAM_INT,
                'default' => 0,
            ],
            'assessmentcode' => [
                'type' => PARAM_TEXT,
            ],
            'assessmentname' => [
                'type' => PARAM_TEXT,
            ],
            'sequence' => [
                'type' => PARAM_ALPHANUMEXT,
            ],
        ];
    }

    /**
     * A course must exist for this record to be created
     *
     * @param int $courseid
     * @return boolean|lang_string String on error.
     */
    protected function validate_courseid($courseid) {
        if ($courseid == 0) {
            return new lang_string('courseidrequired', 'local_solsits');
        }
        return true;
    }

    /**
     * Gets a list of assignments that haven't been created yet, where the course template has been applied
     * meaning these assignments can now be created.
     *
     * @param int $limit Max number of records to return
     * @return array
     */
    public static function get_create_list($limit = 10) {
        global $DB;
        $config = get_config('local_solsits');
        $sql = "SELECT cf.id, cf.shortname
            FROM {customfield_field} cf
            JOIN {customfield_category} cat ON cat.id = cf.categoryid AND cat.name = 'Student Records System'
            WHERE cf.shortname = 'templateapplied'";
        $templateappliedfield = $DB->get_record_sql($sql);
        $params = [
            'fieldid' => $templateappliedfield->id,
        ];
        // If limittoyears is empty, then there is no restriction.
        $wheres = '';
        $limittoyears = isset($config->limittoyears) ? explode(',', $config->limittoyears) : [];
        if (!empty($limittoyears)) {
            $limitsql = [];
            foreach ($limittoyears as $k => $year) {
                $limitsql[] = $DB->sql_like('sitsref', ':limit' . $k, false, false);
                $params['limit' . $k] = '%' . $year . '%';
            }
            $wheres = ' AND (' . join(' OR ', $limitsql) . ') ';
        }
        $sql = "SELECT ssa.*
        FROM {local_solsits_assign} ssa
        JOIN {customfield_data} cfd ON cfd.instanceid = ssa.courseid AND cfd.fieldid = :fieldid
        WHERE ssa.cmid = 0 AND cfd.value = '1' AND ssa.duedate > 0 {$wheres}";

        $records = $DB->get_records_sql($sql, $params, 0, $limit);
        return $records;
    }

    /**
     * Add SITS assignment data to the Moodle assignment settings page, if relevant.
     *
     * @param \moodleform_mod $formwrapper
     * @param \MoodleQuickForm $mform
     * @return void
     */
    public static function coursemodule_form(\moodleform_mod $formwrapper, \MoodleQuickForm $mform) {
        global $DB;
        $cm = $formwrapper->get_coursemodule();
        if (!isset($cm) || $cm->modname != 'assign') {
            return;
        }
        $solassign = $DB->get_record('local_solsits_assign', ['cmid' => $cm->id]);

        if (!$solassign) {
            // Not a SITS assignment, so don't bother.
            return;
        }
        $mform->addElement('header', 'sits_section', new lang_string('sits', 'local_solsits'));

        $mform->addElement('html', new lang_string('sitsdatadesc', 'local_solsits'));

        $mform->addElement('static', 'sits_ref', new lang_string('sitsreference', 'local_solsits'), $solassign->sitsref);
        $mform->addElement('static', 'sits_assessmentname',
            new lang_string('assessmentname', 'local_solsits'), $solassign->assessmentname);
        $mform->addElement('static', 'sits_assessmentcode',
            new lang_string('assessmentcode', 'local_solsits'), $solassign->assessmentcode);
        $mform->addElement('static', 'sits_sequence', new lang_string('sequence', 'local_solsits'), $solassign->sequence);
        $reattempt = get_string('reattempt' . (string)$solassign->reattempt, 'local_solsits');
        $mform->addElement('static', 'sits_reattempt', new lang_string('sitsreattempt', 'local_solsits'),
            $reattempt);

        $mform->addElement('static', 'sits_weighting', new lang_string('weighting', 'local_solsits'), $solassign->weighting . '%');
        $strftimedatetimeaccurate = '%d %B %Y, %I:%M:%S %p';
        $duedate = userdate($solassign->duedate, $strftimedatetimeaccurate);
        $mform->addElement('static', 'sits_duedate', new lang_string('duedate', 'local_solsits'), $duedate);

        $grademarkexempt = $solassign->grademarkexempt ? get_string('yes') : get_string('no');
        $mform->addElement('static', 'sits_grademarkexempt', new lang_string('grademarkexempt', 'local_solsits'), $grademarkexempt);

        $availablefrom = '';
        if ($solassign->availablefrom > 0) {
            $availablefrom = userdate($solassign->availablefrom, $strftimedatetimeaccurate);
        } else {
            $availablefrom = get_string('immediately', 'local_solsits');
        }
        $mform->addElement('static', 'sits_availablefrom', new lang_string('availablefrom', 'local_solsits'), $availablefrom);
    }

    /**
     * Calculate dates from the duedate
     *
     * @return void
     */
    private function calculatedates() {
        $config = get_config('local_solsits');
        // We're getting timestamps, so all this might be wrong.
        // Also, we need to confirm this timestamp has the appropriate Daylight Saving Offset applied.
        // Or we get UTC and apply it ourselves.
        // Or we get UTC and let the Moodle server do all the grunt when displaying the time.
        // What about course start end dates?
        if ($this->get('availablefrom') == 0) {
            $this->formdata->allowsubmissionsfromdate = 0;
        } else {
            // Assuming UTC.
            $this->formdata->allowsubmissionsfromdate = $this->get('availablefrom');
        }

        // Due date.
        $duedate = helper::set_time($this->get('duedate'));
        $this->formdata->duedate = $duedate;

        // Cut off date.
        if (!$this->is_exam()) {
            if ($this->get('reattempt') == 0) {
                $modifystring = '+' . $config->cutoffinterval . ' week';
            } else {
                $modifystring = '+' . $config->cutoffintervalsecondplus . ' week';
            }
            $dt = helper::set_time($this->get('duedate'), '16:00', $modifystring);
            $this->formdata->cutoffdate = $dt;
        } else {
            // Cutoff date for exam must be the same as the duedate.
            $this->formdata->cutoffdate = $duedate;
        }

        // Grading due date.
        $modifystring = '+' . $config->gradingdueinterval . ' week';
        $dt = helper::set_time($this->get('duedate'), '16:00', $modifystring);
        $this->formdata->gradingduedate = $dt;
    }

    /**
     * Create the Moodle assignment from available data
     *
     * @return void
     */
    public function create_assignment() {
        global $DB;
        $config = get_config('local_solsits');
        if ($this->get('duedate') == 0) {
            mtrace("Due date has not been set (0), so no assignment has been created. {$this->get('sitsref')}");
            return false;
        }
        if (!$DB->record_exists('course', ['id' => $this->get('courseid')])) {
            mtrace("The courseid {$this->get('courseid')} no longer exists. {$this->get('sitsref')}");
            return false;
        }

        $this->prepare_formdata();
        $course = get_course($this->get('courseid'));
        $modinfo = \prepare_new_moduleinfo_data($course, 'assign', $config->targetsection);
        $newassign = new \assign($modinfo, null, $course);
        $newmod = $newassign->add_instance($this->formdata, true);
        $cm = $this->insert_cm($course, $newassign, $newmod);
        if ($cm) {
            $this->set('cmid', $cm->id);
            $this->save();
            $this->save_tii();
            return true;
        } else {
            mtrace('Failed to create Course module for ' . $course->shortname . ". {$this->get('sitsref')}");
            return false;
        }
    }

    /**
     * Update an already existing assignment with new data
     *
     * @return bool false in an error occurs
     */
    public function update_assignment() {
        global $DB;
        $config = get_config('local_solsits');

        if ($this->get('duedate') == 0) {
            // It should only get here is the cmid has been set, which means that previously the assignment
            // was created.
            mtrace("Due date has not been set (0), so we can't update the assignment. {$this->get('sitsref')}");
            return false;
        }
        if (!$DB->record_exists('course', ['id' => $this->get('courseid')])) {
            // This should be checked before getting here.
            mtrace("The courseid {$this->get('courseid')} no longer exists. {$this->get('sitsref')}");
            return false;
        }
        if (!$DB->record_exists('course_modules', ['id' => $this->get('cmid')])) {
            if ($this->get('cmid') == 0) {
                mtrace("The specified Course module ({$this->get('cmid')}) hasn't yet been created. "
                . "{$this->get('sitsref')}");
            } else {
                mtrace("The specified Course module ({$this->get('cmid')}) no longer exists, it needs to be recreated. "
                . "{$this->get('sitsref')}");
            }
            return false;
        }

        // Do manual changes rather than update_instance as this requires more formdata.
        [$course, $cm] = get_course_and_cm_from_cmid($this->get('cmid'), 'assign');
        $this->formdata = $DB->get_record('assign', ['id' => $cm->instance]);
        $this->formdata->name = $this->get('title');
        if ($this->get('reattempt') > 0 && $this->formdata->intro == '') {
            $this->formdata->intro = $config->assignmentmessage_studentreattempt ?? '';
        }
        $this->calculatedates();
        $gradeitem = grade_item::fetch([
            'itemtype' => 'mod',
            'itemmodule' => $cm->modname,
            'iteminstance' => $cm->instance,
            'itemnumber' => 0,
            'courseid' => $course->id,
        ]);
        // We only want to update the grade scale if there have been no submissions.
        if (!$gradeitem->has_grades()) {
            if ($this->get('grademarkexempt')) {
                $this->formdata->grade = $config->grademarkexemptscale * -1;
            } else {
                $this->formdata->grade = $config->grademarkscale * -1;
            }
        }
        $DB->update_record('assign', $this->formdata);
        $completion = new stdClass();
        $completion->id = $this->get('cmid');
        $completion->completionexpected = 0;
        $DB->update_record('course_modules', $completion);

        $assign = $DB->get_record('assign', ['id' => $cm->instance]);
        // Get fresh, rather than cached.
        $cm = $DB->get_record('course_modules', ['id' => $this->get('cmid')]);
        $cm->modname = 'assign';
        course_module_calendar_event_update_process($assign, $cm);
        rebuild_course_cache($course->id);
        return true;
    }

    /**
     * Insert the Moodle assignment
     *
     * @param stdClass $course Course object
     * @param assign $newassign Assignment instance
     * @param int $newmod Instance id
     * @return stdClass|false Returns false on failure.
     */
    private function insert_cm($course, $newassign, $newmod) {
        global $DB;
        // Get module.
        $modassign = $DB->get_record('modules', ['name' => 'assign'], '*', MUST_EXIST);

        // Insert to course_modules table.
        $module = new stdClass();
        $module->id = null;
        $module->course = $course->id;
        $module->module = $modassign->id;
        $module->modulename = $modassign->name;
        $module->instance = $newmod;
        $module->section = 1;
        $module->idnumber = $this->get('sitsref');
        $module->added = 0;
        $module->score = 0;
        $module->indent = 0;
        if ($this->get('reattempt') == 0) {
            $module->visible = 1;
            $module->completion = COMPLETION_CRITERIA_TYPE_DATE;
        } else {
            $module->visible = 0;
            $module->completion = 0;
        }
        $module->visibleold = 0;
        $module->groupmode = 0;
        $module->groupingid = 0;
        $module->completiongradeitemnumber = null;
        $module->completionview = 0;
        $module->completionexpected = 0;
        $module->showdescription = 1;
        $module->availability = null;
        $module->deletioninprogress = 0;
        $module->coursemodule = "";
        $module->add = 'assign';

        $newcmid = add_course_module($module);

        // Get course module here.
        $newcm = get_coursemodule_from_id('assign', $newcmid, $course->id, false, MUST_EXIST);

        if (!$newcm) {
            return false;
        }

        course_add_cm_to_section($course, $newcmid, 1);
        $modcontext = $newassign->set_context(context_module::instance($newcm->id));

        $eventdata = clone $newcm;
        $eventdata->modname = $eventdata->modname;
        $eventdata->id = $eventdata->id;
        $event = \core\event\course_module_created::create_from_cm($eventdata, $modcontext);
        $event->trigger();

        rebuild_course_cache($course->id);

        return $newcm;
    }

    /**
     * Set assignment default settings used for form submission.
     *
     * @return void
     */
    private function set_defaultconfig() {
        if ($this->defaultconfig) {
            // Already set.
            return;
        }
        // This might be too much, but it does cover cases where new settings have been created.
        $this->defaultconfig = get_config('assign');
        $this->defaultconfig->assignfeedback_comments_enabled = get_config('assignfeedback_comments', 'default');
        $this->defaultconfig->assignfeedback_comments_commentinline = get_config('assignfeedback_comments', 'inline');
        $this->defaultconfig->assignfeedback_doublemark_enabled = get_config('assignfeedback_doublemark', 'default');
        $this->defaultconfig->assignfeedback_file_enabled = get_config('assignfeedback_file', 'default');
        $this->defaultconfig->assignfeedback_misconduct_enabled = get_config('assignfeedback_misconduct', 'default');
        $this->defaultconfig->assignfeedback_sample_enabled = get_config('assignfeedback_sample', 'default');
    }

    /**
     * Prepare formdata from default assign settings and SOL requirements.
     *
     * @return void
     */
    private function prepare_formdata() {
        // Not all settings have a default setting override, so these need to be filled in as we're spoofing a
        // form submission.
        $this->set_defaultconfig();
        $config = get_config('local_solsits');

        $this->formdata = $this->defaultconfig;
        if ($this->get('cmid') > 0) {
            // Might need to get the assign id rather than the cmid.
            $this->formdata->id = $this->get('cmid');
        } else {
            $this->formdata->id = null;
        }
        $this->formdata->course = $this->get('courseid');
        $this->formdata->name = $this->get('title');
        if ($this->get('reattempt') > 0) {
            $desc = $config->assignmentmessage_studentreattempt ?? '';
            $this->formdata->intro = $desc;
        } else {
            $this->formdata->intro = '';
        }
        $this->formdata->introformat = FORMAT_HTML;
        $this->formdata->alwaysshowdescription = 1;
        // Any submission plugins enabled? Default 0.
        $this->formdata->nosubmissions = 0;
        if ($this->get('grademarkexempt')) {
            $this->formdata->grade = $config->grademarkexemptscale * -1;
        } else {
            $this->formdata->grade = $config->grademarkscale * -1;
        }
        $this->formdata->completionsubmit = 1;
        $this->formdata->revealidenties = 0;
        $this->formdata->coursemodule = '';
        $this->calculatedates();

    }

    /**
     * If Turnitin is enabled, save the configuration for it
     *
     * @return void
     */
    private function save_tii() {
        // TII isn't installed. Nothing to do.
        if (!class_exists('plagiarism_plugin_turnitin')) {
            return;
        }
        $tiiform = new stdClass();
        $tiiform->modulename = 'assign';
        $tiiform->coursemodule = $this->get('cmid');
        $tiienabled = \plagiarism_plugin_turnitin::get_config_settings('mod_assign');
        if (empty($tiienabled)) {
            return;
        }
        $tii = new \plagiarism_plugin_turnitin();
        $defaults = $tii->get_settings();
        foreach ($defaults as $name => $value) {
            $tiiform->$name = $value;
        }
        $course = get_course($this->get('courseid'));
        \plagiarism_turnitin_coursemodule_edit_post_actions($tiiform, $course);
    }

    /**
     * This is an exam if EXAM appears anywhere in the sitsref
     *
     * @return boolean
     */
    public function is_exam() {
        return (strpos($this->get('sitsref'), 'EXAM') !== false);
    }

    /**
     * Add new grade to solsits_assign_grades table for later processing and records.
     *
     * @param stdClass $grade
     * @return bool Success/Failure
     */
    public function enqueue_grade($grade) {
        global $DB;
        // There should only be one grade for this user on this assignment.
        $exists = $DB->record_exists('local_solsits_assign_grades', [
            'solassignmentid' => $grade->solassignmentid,
            'studentid' => $grade->studentid,
        ]);
        if (!$exists) {
            $DB->insert_record('local_solsits_assign_grades', $grade);
            return true;
        }
        return false;
    }

    /**
     * Update grade in local_solsits_assign_grades for individual student
     *
     * @param object $grade appropriate data object for table.
     * @return bool Success or not.
     */
    public function update_grade($grade) {
        global $DB;
        $exists = $DB->record_exists('local_solsits_assign_grades', [
            'solassignmentid' => $grade->solassignmentid,
            'studentid' => $grade->studentid,
        ]);
        if (!$exists) {
            return false;
        }
        $grade->timemodified = time();
        return $DB->update_record('local_solsits_assign_grades', $grade);
    }

    /**
     * Get grade from local_solsits_assign_grades for individual student.
     *
     * @param string $studentid
     * @return object|false
     */
    public function get_grade($studentid) {
        global $DB;
        return $DB->get_record('local_solsits_assign_grades', [
            'solassignmentid' => $this->get('id'),
            'studentid' => $studentid,
        ]);
    }

    /**
     * Get list of solassignmentids for exporting
     *
     * @param integer $limit
     * @return array
     */
    public static function get_retry_list($limit = 0): array {
        global $DB;
        // The challenge here is that the local_sits_assign_grades table lists one entry for each person
        // on the assignment.
        // Return the solassignment ids only.
        $assignids = $DB->get_records_sql(
            "SELECT DISTINCT(ag.solassignmentid) solassignmentid
            FROM {local_solsits_assign_grades} ag
            WHERE ag.response IS NULL", [], 0, $limit);
        $assignids = array_keys($assignids);
        return $assignids;
    }

    /**
     * Get queued grades for export for this assignment.
     * Reads the local_solsits_assign_grades table for assignments that have not yet been exported.
     *
     * @return array ['module', 'assignment', 'unitleader', 'grades']
     */
    public function get_queued_grades_for_export(): array {
        global $DB;
        [$course, $cm] = get_course_and_cm_from_cmid($this->get('cmid'));
        [$insql, $inparams] = $DB->get_in_or_equal(['module_code', 'academic_year']);
        $sql = "SELECT cf.id, cf.shortname
            FROM {customfield_field} cf
            JOIN {customfield_category} cat ON cat.id = cf.categoryid AND cat.name = 'Student Records System'
            WHERE cf.shortname {$insql}";
        $fields = $DB->get_records_sql($sql, $inparams);

        $params = [];
        foreach ($fields as $field) {
            $params[$field->shortname . 'id'] = $field->id;
        }

        $sql = "SELECT sag.id, sa.sitsref, sa.reattempt, sa.sequence, sag.response,
            stu.id studentid, stu.idnumber studentidnumber, stu.firstname studentfirstname, stu.lastname studentlastname,
            leader.firstname leaderfirstname, leader.lastname leaderlastname, leader.email leaderemail,
            cfmod.value modulecode, c.fullname moduletitle, cfay.value academic_year, c.shortname moduleinstanceid,
            a.name assessment_name, a.duedate, sag.converted_grade, a.id assignid
            FROM {local_solsits_assign_grades} sag
            JOIN {user} stu ON stu.id = sag.studentid
            JOIN {user} leader ON leader.id = sag.graderid
            JOIN {local_solsits_assign} sa ON sa.id = sag.solassignmentid
            JOIN {course_modules} cm ON cm.id = sa.cmid
            JOIN {assign} a ON a.id = cm.instance
            JOIN {course} c ON c.id = sa.courseid
            JOIN {customfield_data} cfmod ON cfmod.instanceid = sa.courseid AND cfmod.fieldid = :module_codeid
            JOIN {customfield_data} cfay ON cfay.instanceid = sa.courseid AND cfay.fieldid = :academic_yearid
            WHERE sag.solassignmentid = :solassignmentid";
        $params['solassignmentid'] = $this->get('id');
        $markedassignments = $DB->get_records_sql($sql, $params);
        if (!$markedassignments) {
            mtrace("No grades for {$this->get('sitsref')} found for export");
            return [];
        }
        $firstrecord = reset($markedassignments);

        $toexport = [
            'module' => [
                'modulecode' => $firstrecord->modulecode,
                'moduleinstanceid' => $firstrecord->moduleinstanceid,
                'moduletitle' => $firstrecord->moduletitle,
                'modulestartdate' => date('d/m/Y H:i:s', $course->startdate),
                'moduleenddate' => date('d/m/Y H:i:s', $course->enddate),
                'academic_year' => $firstrecord->academic_year,
            ],
            'assignment' => [
                'sitsref' => $firstrecord->sitsref,
                'assignmenttitle' => $firstrecord->assessment_name,
                'duedate' => date('d/m/Y H:i:s', $firstrecord->duedate),
                'assignid' => $firstrecord->assignid,
                'reattempt' => $firstrecord->reattempt,
                'sequence' => $firstrecord->sequence,
                'page' => get_string('poft', 'local_solsits', ['page' => '1', 'total' => '1']),
            ],
            'unitleader' => [
                'firstname' => $firstrecord->leaderfirstname,
                'lastname' => $firstrecord->leaderlastname,
                'email' => $firstrecord->leaderemail,
            ],
            'grades' => [],
        ];

        $studentgrades = $this->get_grades();
        foreach ($markedassignments as $markedassignment) {
            if ($markedassignment->response) {
                // Already exported, don't do again.
                continue;
            }
            $misconductstring = get_string('no');
            if (isset($studentgrades[$markedassignment->studentid])) {
                $misconduct = $DB->get_record('assignfeedback_misconduct', [
                    'assignment' => $cm->instance,
                    'grade' => $studentgrades[$markedassignment->studentid]->id,
                ]);
            }
            $misconduct = $misconduct->misconduct ?? 0;
            if ($misconduct == 1) {
                $misconductstring = get_string('yes');
            }

            $submissiontime = get_string('notsubmitted', 'local_solsits');
            if (isset($studentgrades[$markedassignment->studentid])) {
                $submissiontime = date('d/m/Y H:i:s', $studentgrades[$markedassignment->studentid]->submissiontime);
                if ($studentgrades[$markedassignment->studentid]->submissionstatus != ASSIGN_SUBMISSION_STATUS_SUBMITTED) {
                    $submissiontime = get_string('notsubmitted', 'local_solsits');
                }
            }
            $gradeitem = [
                'firstname' => $markedassignment->studentfirstname,
                'lastname' => $markedassignment->studentlastname,
                'studentidnumber' => $markedassignment->studentidnumber,
                'moodlestudentid' => $markedassignment->studentid,
                'result' => $markedassignment->converted_grade,
                'submissiontime' => $submissiontime,
                'misconduct' => $misconductstring,
            ];
            $toexport['grades'][] = $gradeitem;
        }

        return $toexport;
    }

    /**
     * Get Moodle grades for students on this assignment
     *
     * @return array
     */
    public function get_grades() {
        global $DB;
        [$course, $cm] = get_course_and_cm_from_cmid($this->get('cmid'));
        $params = [
            'assignid' => $cm->instance,
        ];
        $sql = "SELECT ag.userid,
                        ag.id,
                           ag.assignment,
                           ag.timecreated,
                           ag.timemodified,
                           ag.grader,
                           ag.grade,
                           ag.attemptnumber,
                           s.timemodified submissiontime,
                           s.status submissionstatus
                      FROM {assign_grades} ag, {assign_submission} s
                     WHERE s.assignment = :assignid
                       AND s.userid = ag.userid
                       AND s.latest = 1
                       AND s.attemptnumber = ag.attemptnumber
                       AND ag.timemodified  >= 0
                       AND ag.assignment = s.assignment
                  ORDER BY ag.assignment, ag.id";
        return $DB->get_records_sql($sql, $params);
    }
}
