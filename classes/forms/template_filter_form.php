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
 * Template queue filter form
 *
 * @package   local_solsits
 * @author    Mark Sharp <mark.sharp@solent.ac.uk>
 * @copyright 2023 Solent University {@link https://www.solent.ac.uk}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_solsits\forms;

use lang_string;
use local_solsits\helper;
use moodleform;

defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/formslib.php");

/**
 * Filter form to be used on the template queue table.
 */
class template_filter_form extends moodleform {
    /**
     * Form definition
     *
     * @return void
     */
    public function definition() {
        $mform = $this->_form;
        $options = [
            'multiple' => true,
            'noselectionstring' => get_string('noselection', 'local_solsits'),
            'ajax' => 'local_solsits/form-course-selector',
            'valuehtmlcallback' => function($value) {
                global $DB;
                $course = $DB->get_record('course', ['id' => $value]);
                if ($course) {
                    return $course->shortname . ': ' . $course->fullname;
                }
                return false;
            },
        ];
        $mform->addElement('autocomplete', 'selectedcourses',
            new lang_string('selectcourses', 'local_solsits'),
            [],
            $options);
        $mform->setDefault('selectedcourses', []);

        $options = [
            '' => get_string('selectapagetype', 'local_solsits'),
        ] + helper::get_pagetypes_menu();
        $mform->addElement('select', 'pagetype', new lang_string('pagetype', 'local_solsits'), $options);

        $options = [
            '' => new lang_string('selectasession', 'local_solsits'),
        ] + helper::get_session_menu();
        $mform->addElement('select', 'session', new lang_string('session', 'local_solsits'), $options);
        $this->add_action_buttons(false, get_string('filter', 'local_solsits'));
    }

    /**
     * Manually set values for fields so it can work with both get and post
     *
     * @return void
     */
    public function definition_after_data() {
        $mform = $this->_form;
        $customdata = $this->_customdata;
        $selectedcourses = $mform->getElement('selectedcourses');
        $selectedcourses->setValue($customdata['selectedcourses']);
        $pagetype = $mform->getElement('pagetype');
        $pagetype->setValue($customdata['pagetype']);
        $session = $mform->getElement('session');
        $session->setValue($customdata['session']);
    }

    /**
     * Form validation
     *
     * @param array $data
     * @param array $files
     * @return array List of errors
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        if (!empty($data['pagetype'])) {
            $pagetypemenu = helper::get_pagetypes_menu();
            if (!isset($pagetypemenu[$data['pagetype']])) {
                $errors['pagetype'] = get_string('invalidpagetype', 'local_solsits');
            }
        }

        if (!empty($data['session'])) {
            $sessionmenu = helper::get_session_menu();
            if (!isset($sessionmenu[$data['session']])) {
                $errors['session'] = get_string('invalidsession', 'local_solsits');
            }
        }

        if (!empty($data['selectedcourses'])) {
            foreach ($data['selectedcourses'] as $selectedcourse) {
                if (!is_numeric($selectedcourse)) {
                    $errors['selectedcourses'] = get_string('invalidcourseid', 'local_solsits');
                }
            }
        }

        return $errors;
    }
}
