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
 * Filter form for sitsassign management page
 *
 * @package   local_solsits
 * @author    Mark Sharp <mark.sharp@solent.ac.uk>
 * @copyright 2023 Solent University {@link https://www.solent.ac.uk}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_solsits\forms;

use lang_string;
use moodleform;

defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/formslib.php");

/**
 * Filter form for sitsassignment management page
 */
class solassign_filter_form extends moodleform {
    /**
     * Form definition
     *
     * @return void
     */
    public function definition() {
        $mform = $this->_form;

        $mform->addElement('header', 'filterassignmentshdr', new lang_string('filterassignments', 'local_solsits'));
        $mform->setExpanded('filterassignmentshdr', true);
        $options = [
            'multiple' => true,
            'noselectionstring' => get_string('noselection', 'local_solsits'),
            'ajax' => 'local_solsits/form-course-selector',
            'valuehtmlcallback' => function($value) {
                global $DB;
                $course = $DB->get_record('course', ['id' => $value]);
                return $course->shortname . ': ' . $course->fullname;
            },
        ];
        $mform->addElement('autocomplete', 'selectedcourses',
            new lang_string('selectcourses', 'local_solsits'),
            [],
            $options);
        $mform->setDefault('selectedcourses', []);

        $mform->addElement('advcheckbox', 'currentcourses', new lang_string('currentcourses', 'local_solsits'));
        $mform->addHelpButton('currentcourses', 'currentcourses', 'local_solsits');
        $mform->setDefault('currentcourses', true);

        $mform->addElement('advcheckbox', 'showerrorsonly', new lang_string('showerrorsonly', 'local_solsits'));
        $mform->addHelpButton('showerrorsonly', 'showerrorsonly', 'local_solsits');
        $mform->setDefault('showerrorsonly', false);

        $this->add_action_buttons(null, new lang_string('filterassignments', 'local_solsits'));
    }
}
