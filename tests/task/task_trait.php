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
 * Helper functions for tasks
 *
 * @package   local_solsits
 * @author    Mark Sharp <mark.sharp@solent.ac.uk>
 * @copyright 2022 Solent University {@link https://www.solent.ac.uk}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_solsits\task;

use local_solsits\soltemplate;
use stdClass;

/**
 * Helper trait for task processing.
 */
trait task_trait {

    /**
     * Helper to execute a particular task.
     *
     * @param string $task The task.
     */
    private function execute_task($task) {
        // Run the scheduled task - we want to check the output, so don't use ob_start.
        $task = \core\task\manager::get_scheduled_task($task);
        $task->execute();
    }

    /**
     * Creates a template course and registers it as a soltemplate
     *
     * @param string $session
     * @param string $pagetype
     * @param bool $enabled
     * @return soltemplate
     */
    private function create_template_course($session, $pagetype = 'module', $enabled = 1) {
        $idnumber = 'template_' . $session . '_' . $pagetype;
        $template = $this->getDataGenerator()->create_course([
            'fullname' => "Template " . $idnumber,
            'idnumber' => $idnumber,
            'shortname' => $idnumber
        ]);
        $this->getDataGenerator()->create_module('label', [
            'course' => $template->id,
            'intro' => "Label from Template {$idnumber}."
        ]);
        $record = new stdClass();
        $record->courseid = $template->id;
        $record->pagetype = $pagetype;
        $record->session = $session;
        $record->enabled = $enabled;
        $soltemplate = new soltemplate(0, $record);
        $soltemplate->save();
        return $soltemplate;
    }
}

