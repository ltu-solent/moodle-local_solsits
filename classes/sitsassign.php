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
 * Helper functions
 *
 * @package   local_solsits
 * @author    Mark Sharp <mark.sharp@solent.ac.uk>
 * @copyright 2022 Solent University {@link https://www.solent.ac.uk}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_solsits;

use assign;
use context_module;
use core_date;
use DateTime;
use lang_string;
use moodle_exception;
use stdClass;

/**
 * Sits assignment class
 */
class sitsassign {
    /**
     * Moodle assignment instance
     *
     * @var assign
     */
    private $assign;

    /**
     * SolAssignment persistent record
     *
     * @var solassignment
     */
    private $solassignment;

    /**
     * Data coming from SITS
     *
     * @var stdClass
     */
    private $sitsdata;

    /**
     * The formdata being submitted to create the Moodle assignment
     *
     * @var array
     */
    private $formdata;

    /**
     * SolSits config settings
     *
     * @var stdClass
     */
    private $config;

    /**
     * Class that manages SITS assignments
     *
     * @param string $sitsref
     * @param stdClass $sitsdata
     */
    public function __construct($sitsref = '', stdClass $sitsdata = null) {
        $this->config = get_config('local_solsits');
        $this->sitsdata = $sitsdata;
        if ($sitsref != '') {
            $this->solassignment = solassignment::get_record(['sitsref' => $sitsref]);
            if (!$this->solassignment) {
                throw new moodle_exception('invalidrecord');
            }
            // If this fails there's a mismatch between solassignments and assign tables.
            $context = context_module::instance($this->solassignment->cmid);
            $this->assign = new assign($context, null, null);
        }
    }

    /**
     * Add a new SITS assignment to Moodle.
     *
     * @return void
     */
    public function add() {
        // Merge the default data and the sitsdata
        $this->formdata = clone $this->defaultsettings();
        $moduleinfo = new stdClass();
        // What happens if the course hasn't had its template applied yet?
        // Section 1 won't exist, and the assignment will be deleted!
        // When a template is applied, all the content is deleted, including any
        // previously created assignment. This will throw an event. We could capture the delete event
        // and do an action based on this. e.g. recreate the assignment (what happens if the template 
        // hasn't yet finished being applied?); send an email?
        // We should watch for deleted course modules anyway, as we don't want lecturers deleting them,
        // as they won't automatically reappear.
        $moduleinfo->section = $this->config->targetsection;
        // $assignid = $this->assign->add_instance($moduleinfo, true);
        $this->calculatedates($moduleinfo);
        $moduleinfo = add_moduleinfo($moduleinfo, $this->sitsdata->courseid, $this->formdata);
        return $moduleinfo->cm->id;
    }

    /**
     * Update an existing Moodle assignment
     *
     * @return void
     */
    public function update() {
        $this->assign->update_instance($this->formdata);
        // update_moduleinfo();
    }

    /**
     * Calculate the relative dates to the duedate
     *
     * @param stdClass $moduleinfo
     * @return void
     */
    private function calculatedates(&$moduleinfo) {
        if ($this->sitsdata->availablefrom == 0) {
            $moduleinfo->allowsubmissionsfromdate = 0;
        } else {
            $time = new DateTime('now', core_date::get_user_timezone_object());
            $time = DateTime::createFromFormat('U', $this->sitsdata->availablefrom);
            $time->setTime(16, 0, 0);
            $timezone = core_date::get_user_timezone($time);
            $dst = dst_offset_on($this->sitsdata->availablefrom, $timezone);
            $moduleinfo->allowsubmissionsfromdate = $time->getTimestamp() - $dst;
        }
    }

    /**
     * Default settings for a new assignment
     *
     * @return stdClass
     */
    private function defaultsettings() {

        $assigncfg = get_config('assign');
        // These settings need to be set. When creating an assign object
        // the class assumes these "formdata" elements are present.
        // These are fixed settings. Other settings will be variable settings.
        $assignsettings = [
            'alwaysshowdescription',
            'submissiondrafts',
            'requiresubmissionstatement',
            'sendnotifications',
            'sendlatenotifications',
            'sendstudentnotifications',
            'allowsubmissionsfromdate',
            'teamsubmission',
            'requireallteammemberssubmit',
            'teamsubmissiongroupingid',
            'blindmarking',
            'hidegrader',
            'attemptreopenmethod',
            'maxattempts',
            'preventsubmissionnotingroup',
            'markingworkflow',
            'markingallocation',
        ];
        $settings = new stdClass();
        foreach ($assignsettings as $asetting) {
            $settings->{$asetting} = $assigncfg->{$asetting};
        }
        $comments = get_config('assignfeedback_comments');
        $doublemark = get_config('assignfeedback_doublemark');
        $file = get_config('assignfeedback_file');
        $misconduct = get_config('assignfeedback_misconduct');
        $sample = get_config('assignfeedback_sample');
        return $settings;
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

        $mform->addElement('static', 'sits_ref', new lang_string('sitsreference', 'local_solsits'), $solassign->sitsref);
        $mform->addElement('static', 'sits_sitting', new lang_string('sittingreference', 'local_solsits'), $solassign->sitting);
        $mform->addElement('static', 'sits_sittingdesc', new lang_string('sittingdescription', 'local_solsits'), $solassign->sittingdesc);

        $externaldate = '';
        if ($solassign->externaldate > 0) {
            $externaldate = date('Y-m-d', $solassign->externaldate);
        } else {
            $externaldate = get_string('notset', 'local_solsits');
        }
        $mform->addElement('static', 'sits_externaldate', new lang_string('externaldate', 'local_solsits'), $externaldate);
        $mform->addElement('static', 'sits_status', new lang_string('status', 'local_solsits'), $solassign->status);

        $weighting = (int)($solassign->weighting * 100);
        $mform->addElement('static', 'sits_weighting', new lang_string('weighting', 'local_solsits'), $weighting . '%');

        $mform->addElement('static', 'sits_assessmentcode', new lang_string('assessmentcode', 'local_solsits'), $solassign->assessmentcode);

        $duedate = date('y-m-d', $solassign->duedate);
        $mform->addElement('static', 'sits_duedate', new lang_string('duedate', 'local_solsits'), $duedate);

        $grademarkexempt = $solassign->grademarkexempt ? get_string('Yes') : get_string('No');
        $mform->addElement('static', 'sits_grademarkexempt', new lang_string('grademarkexempt', 'local_solsits'), $grademarkexempt);

        $availablefrom = '';
        if ($solassign->availablefrom > 0) {
            $availablefrom = get_string('immediately', 'local_solsits');
        } else {
            $availablefrom = date('Y-m-d', $solassign->availablefrom);
        }
        $mform->addElement('static', 'sits_availablefrom', new lang_string('availablefrom', 'local_solsits'), $availablefrom);
    }
}
