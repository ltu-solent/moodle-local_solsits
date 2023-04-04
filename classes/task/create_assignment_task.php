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
 * Create assignment task
 *
 * @package   local_solsits
 * @author    Mark Sharp <mark.sharp@solent.ac.uk>
 * @copyright 2023 Solent University {@link https://www.solent.ac.uk}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_solsits\task;
use core\task\scheduled_task;
use local_solsits\sitsassign;

/**
 * Create assignment task
 */
class create_assignment_task extends scheduled_task {
    /**
     * Name of the task
     *
     * @return string
     */
    public function get_name(): string {
        return get_string('createassignmenttask', 'local_solsits');
    }

    /**
     * {@inheritDoc}
     *
     * @return void
     */
    public function execute() {
        $config = get_config('local_solsits');
        // Get list of assignments to be created.
        $assignments = sitsassign::get_create_list($config->maxassignments);
        foreach ($assignments as $assignment) {
            $ass = new sitsassign($assignment->id);
            $ass->create_assignment();
        }
    }
}
