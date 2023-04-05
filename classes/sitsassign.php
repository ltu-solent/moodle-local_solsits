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

use core\persistent;
use core_date;
use DateTime;
use lang_string;

/**
 * The SITS assignment as it's coming from SITS
 */
class sitsassign extends persistent {
    /**
     * Table name for sits assignment.
     */
    const TABLE = 'local_solsits_assign';

    /**
     * Moodle assignment
     *
     * @var stdClass
     */
    private $assign;

    /**
     * Return the definition of the properties of this model.
     *
     * @return array
     */
    protected static function define_properties() {
        return [
            'sitsref' => [
                'type' => PARAM_TEXT
            ],
            // Cmid can be 0 if the course has not been templated.
            'cmid' => [
                'type' => PARAM_INT,
                'default' => 0
            ],
            'courseid' => [
                'type' => PARAM_INT
            ],
            // Sitting reference number.
            'sitting' => [
                'type' => PARAM_INT,
                'null' => NULL_ALLOWED,
                'default' => 0
            ],
            // Usually FIRST, SECOND, THIRD.
            'sittingdesc' => [
                'type' => PARAM_TEXT,
                'default' => 'FIRST'
            ],
            'externaldate' => [
                'type' => PARAM_INT,
                'default' => 0
            ],
            'status' => [
                'type' => PARAM_TEXT,
                'default' => '',
                'null' => NULL_ALLOWED
            ],
            'title' => [
                'type' => PARAM_TEXT
            ],
            'weighting' => [
                'type' => PARAM_FLOAT,
                'default' => 1
            ],
            'duedate' => [
                'type' => PARAM_INT,
                'default' => 0
            ],
            'grademarkexempt' => [
                'type' => PARAM_BOOL,
                'default' => 0
            ],
            'availablefrom' => [
                'type' => PARAM_INT,
                'default' => 0
            ]
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

        $sql = "SELECT cf.id, cf.shortname
            FROM {customfield_field} cf
            JOIN {customfield_category} cat ON cat.id = cf.categoryid AND cat.name = 'Student Records System'
            WHERE cf.shortname = 'templateapplied'";
        $templateappliedfield = $DB->get_record_sql($sql);
        $params = [
            'fieldid' => $templateappliedfield->id
        ];
        $sql = "SELECT ssa.*
        FROM {local_solsits_assign} ssa
        JOIN {customfield_data} cfd ON cfd.instanceid = ssa.courseid AND cfd.fieldid = :fieldid
        WHERE ssa.cmid = 0 AND cfd.value = '1'";

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

        $mform->addElement('static', 'sits_ref', new lang_string('sitsreference', 'local_solsits'), $solassign->sitsref);
        $mform->addElement('static', 'sits_sitting', new lang_string('sittingreference', 'local_solsits'), $solassign->sitting);
        $mform->addElement('static', 'sits_sittingdesc', new lang_string('sittingdescription', 'local_solsits'),
            $solassign->sittingdesc);

        $weighting = (int)($solassign->weighting * 100);
        $mform->addElement('static', 'sits_weighting', new lang_string('weighting', 'local_solsits'), $weighting . '%');

        $duedate = date('%d %B %Y, %I:%M:%S %p', $solassign->duedate);
        $mform->addElement('static', 'sits_duedate', new lang_string('duedate', 'local_solsits'), $duedate);

        $grademarkexempt = $solassign->grademarkexempt ? get_string('yes') : get_string('no');
        $mform->addElement('static', 'sits_grademarkexempt', new lang_string('grademarkexempt', 'local_solsits'), $grademarkexempt);

        $availablefrom = '';
        if ($solassign->availablefrom > 0) {
            $availablefrom = date('%d %B %Y, %I:%M:%S %p', $solassign->availablefrom);
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
        if ($this->get('availablefrom') == 0) {
            $this->assign->allowsubmissionsfromdate = 0;
        } else {
            $time = new DateTime('now', core_date::get_user_timezone_object());
            $time = DateTime::createFromFormat('U', $this->get('availablefrom'));
            $time->setTime(16, 0, 0);
            $timezone = core_date::get_user_timezone($time);
            $dst = dst_offset_on($this->get('availablefrom'), $timezone);
            $this->assign->allowsubmissionsfromdate = $time->getTimestamp() - $dst;
        }
    }

    /**
     * Create the Moodle assignment from available data
     *
     * @return void
     */
    public function create_assignment() {
        mtrace("Pretending to create: " . $this->get('sitsref'));
        // Store Moodle assignment in $this->assign.
    }

    /**
     * Update the Moodle assignment
     *
     * @return void
     */
    public function updatecm() {
        $this->calculatedates();
    }

    /**
     * Insert the Moodle assignment
     *
     * @return void
     */
    private function insertcm() {
        $this->calculatedates();
    }
}
