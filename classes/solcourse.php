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
 * SOL Course record
 *
 * @deprecated
 * @package   local_solsits
 * @author    Mark Sharp <mark.sharp@solent.ac.uk>
 * @copyright 2022 Solent University {@link https://www.solent.ac.uk}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_solsits;

use core\persistent;
use lang_string;

/**
 * SolCourse class.
 *
 * Used to register which courses have come from SITS
 * and allows use to apply an appropriate template.
 */
class solcourse extends persistent {
    /**
     * Table name for solcourses.
     */
    const TABLE = 'local_solsits_courses';

    /**
     * Return the definition of the properties of this model.
     *
     * @return array
     */
    protected static function define_properties() {
        global $USER;
        return [
            'courseid' => [
                'type' => PARAM_INT
            ],
            'pagetype' => [
                'type' => PARAM_ALPHA,
                'default' => soltemplate::MODULE,
                'options' => helper::get_pagetypes_menu()
            ],
            'session' => [
                'type' => PARAM_RAW
            ],
            'templateapplied' => [
                'type' => PARAM_BOOL,
                'default' => false
            ],
        ];
    }

    /**
     * Ensure pagetype is valid.
     *
     * @param string $value
     * @return bool|lang_string True success, lang_string on failure.
     */
    protected function validate_pagetype($value) {
        $valid = helper::get_pagetypes_menu();
        if (!in_array($value, $valid)) {
            return new lang_string('invalidpagetype', 'local_solsits');
        }
        return true;
    }

    /**
     * Validate the session
     *
     * @param string $value The expected format is 2023/24 - but check.
     * @return bool|lang_string
     */
    protected function validate_session($value) {
        $options = helper::get_session_menu();
        if (!isset($options[$value])) {
            return new lang_string('invalidsession', 'local_solsits');
        }
        return true;
    }

    /**
     * Validate the courseid exists
     *
     * @param int $value courseid
     * @return bool|lang_string
     */
    protected function validate_courseid($value) {
        global $DB;
        if (!is_numeric($value)) {
            return new lang_string('invalidcourseid', 'local_solsits');
        }
        if (!$DB->record_exists('course', ['id' => $value])) {
            return new lang_string('invalidcourseid', 'local_solsits');
        }
        return true;
    }
}
