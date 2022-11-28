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
 * Template instance form
 *
 * @package   local_solsits
 * @author    Mark Sharp <mark.sharp@solent.ac.uk>
 * @copyright 2022 Solent University {@link https://www.solent.ac.uk}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_solsits\forms;

use core\form\persistent as persistent_form;
use lang_string;
use local_solsits\helper;
use local_solsits\soltemplate;

class soltemplate_form extends persistent_form {

    /**
     * Cross reference for the object this form is working from.
     *
     * @var string
     */
    protected static $persistentclass = 'local_solsits\\soltemplate';

    /**
     * Form definition
     *
     * @return void
     */
    public function definition() {
        global $DB;
        $mform = $this->_form;

        $options = helper::get_pagetypes();
        $mform->addElement('select', 'pagetype', new lang_string('pagetype', 'local_solsits'), $options);
        $mform->addRule('pagetype', get_string('required'), 'required', null, 'client');
        $mform->addHelpButton('pagetype', 'pagetype', 'local_solsits');

        $options = [
            '' => new lang_string('selectasession', 'local_solsits')
        ] + helper::get_session_menu();

        $mform->addElement('select', 'session', new lang_string('session', 'local_solsits'), $options);
        $mform->addRule('session', get_string('required'), 'required', null, 'client');

        $options = [
            '' => new lang_string('selectatemplate', 'local_solsits')
        ] + helper::get_templates_menu();
        $mform->addElement('select', 'courseid', new lang_string('templatecourse', 'local_solsits'), $options);
        $mform->addRule('courseid', get_string('required'), 'required', null, 'client');

        $mform->addElement('advcheckbox', 'enabled', new lang_string('enabled', 'local_solsits'));

        $this->add_action_buttons();

    }
}
