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
 * Generator class for local_solsits
 *
 * @package   local_solsits
 * @author    Mark Sharp <mark.sharp@solent.ac.uk>
 * @copyright 2023 Solent University {@link https://www.solent.ac.uk}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_solsits\sitsassign;

 /**
  * Generator class
  */
class local_solsits_generator extends component_generator_base {
    /**
     * How many assignment have been created.
     *
     * @var integer
     */
    public $assigncount = 0;

    /**
     * Reset the counters
     *
     * @return void
     */
    public function reset() {
        $this->assigncount = 0;
    }

    /**
     * Creates a record in the local_solsits_assign table
     *
     * @param array $record
     * @return object
     */
    public function create_sits_assign(array $record) {
        global $USER;
        $this->assigncount++;
        $i = $this->assigncount;

        $record = (object)array_merge([
            'sitsref' => "SITS{$i}",
            'cmid' => 0,
            'courseid' => 0,
            'reattempt' => '', // Usually FIRST, SECOND...
            'title' => "ASSIGN{$i}",
            'weighting' => 1,
            'duedate' => strtotime('+1 week'),
            'grademarkexempt' => false,
            'availablefrom' => 0,
            'usermodified' => $USER->id,
            'timecreated' => time(),
            'timemodified' => time()
        ], (array)$record);

        $assignment = new sitsassign(0, $record);
        $assignment->create();
        return $assignment;
    }
}
