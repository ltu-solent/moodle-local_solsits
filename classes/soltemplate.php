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
 * Templates content typwe
 *
 * @package   local_solsits
 * @author    Mark Sharp <mark.sharp@solent.ac.uk>
 * @copyright 2022 Solent University {@link https://www.solent.ac.uk}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_solsits;

use core\persistent;
use lang_string;

/**
 * Soltemplate persistent record.
 */
class soltemplate extends persistent {
    /**
     * Table name for templates.
     */
    const TABLE = 'local_solsits_templates';
    /**
     * Module pagetype
     */
    public const MODULE = 'module';
    /**
     * Course pagetype
     */
    public const COURSE = 'course';


    /**
     * Return the definition of the properties of this model.
     *
     * @return array
     */
    protected static function define_properties() {
        return [
            'pagetype' => [
                'type' => PARAM_ALPHA,
                'default' => self::MODULE,
                'options' => [
                    self::MODULE,
                    self::COURSE
                ]
            ],
            'courseid' => [
                'type' => PARAM_INT,
            ],
            'session' => [
                'type' => PARAM_TEXT
            ],
            'enabled' => [
                'type' => PARAM_BOOL,
                'default' => false
            ]
        ];
    }

    /**
     * Validate pagetypes
     *
     * @param string $value Expected pagetype
     * @return bool|lang_string True success; string on failure.
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
     * @return bool|lang_string True success; string on failure.
     */
    protected static function validate_session($value) {
        $options = helper::get_session_menu();
        if (!isset($options[$value])) {
            return new lang_string('invalidsession', 'local_solsits');
        }
        return true;
    }

    /**
     * Validate the courseid
     *
     * @param string $value The courseid
     * @return bool|lang_string True success; string on failure.
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
