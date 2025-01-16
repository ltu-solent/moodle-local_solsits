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

namespace local_solsits\local;

use local_solsits\helper;

/**
 * Class after_course_content_header_callback
 *
 * @package    local_solsits
 * @copyright  2025 Southampton Solent University {@link https://www.solent.ac.uk}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class after_course_content_header_callback {
    /**
     * Hook callback for after_course_content_header
     *
     * @param \local_solalerts\hook\after_course_content_header $hook
     * @return void
     */
    public static function assignalerts(\local_solalerts\hook\after_course_content_header $hook): void {
        global $COURSE, $PAGE;
        if ($PAGE->pagetype == 'mod-assign-grading') {
            helper::gradingalerts($hook, $PAGE->cm, $COURSE, $PAGE->context);
        }
        if ($PAGE->pagetype == 'mod-assign-view') {
            helper::badassignalerts($hook, $PAGE->cm, $COURSE, $PAGE->context);
        }
    }
}
