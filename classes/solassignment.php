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

defined('MOODLE_INTERNAL') || die();

/**
 * The SITS assignment as it's coming from SITS
 */
class solassignment extends persistent {
    /**
     * Table name for solassignments.
     */
    const TABLE = 'local_solsits_assign';

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
            'cmid' => [
                'type' => PARAM_INT
            ],
            'courseid' => [
                'type' => PARAM_INT
            ],
            'sitting' => [
                'type' => PARAM_INT
            ],
            'sittingdesc' => [
                'type' => PARAM_TEXT
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
            'assessmentcode' => [
                'type' => PARAM_TEXT
            ],
            'duedate' => [
                'type' => PARAM_INT
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
}
