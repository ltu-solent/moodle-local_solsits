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
 * General helper to save repeating bits of code
 *
 * @package   local_solsits
 * @author    Mark Sharp <mark.sharp@solent.ac.uk>
 * @copyright 2022 Solent University {@link https://www.solent.ac.uk}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_solsits;

/**
 * Helper class for common functions.
 */
class helper {

    /**
     * Gets key/value pairs for Pagetypes menu.
     *
     * @return array
     */
    public static function get_pagetypes_menu(): array {
        $options = [
            soltemplate::MODULE => soltemplate::MODULE,
            soltemplate::COURSE => soltemplate::COURSE
        ];
        return $options;
    }
    /**
     * Returns key/value pairs for Session menu. Used also for validation.
     *
     * @return array
     */
    public static function get_session_menu(): array {
        $years = range(2020, date('Y') + 1);
        $options = [];
        foreach ($years as $year) {
            $yearplusone = substr($year, 2, 2) + 1;
            $options[$year . '/' . $yearplusone] = $year . '/' . $yearplusone;
        }
        return array_reverse($options);
    }

    /**
     * Gets list of courses in the designated template category formatted for a menu.
     *
     * @return array
     */
    public static function get_templates_menu(): array {
        global $DB;
        $templatecat = get_config('local_solsits', 'templatecat');
        if (!isset($templatecat)) {
            return [];
        }
        return $DB->get_records_menu('course', ['category' => $templatecat], 'fullname ASC', 'id, fullname');
    }
}

