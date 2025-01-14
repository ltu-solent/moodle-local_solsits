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
 * External functions for SOL Assignments
 *
 * @package   local_solsits
 * @author    Mark Sharp <mark.sharp@solent.ac.uk>
 * @copyright 2022 Solent University {@link https://www.solent.ac.uk}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_solsits\helper;
use local_solsits\sitsassign;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/lib/externallib.php');

/**
 * External Webservice functions for local_solsits
 */
class local_solsits_external extends external_api {
    /**
     * Validate parameters for register sitscourses.
     *
     * @deprecated Not used, no longer required.
     * @return external_function_parameters
     */
    public static function register_sitscourses_parameters(): external_function_parameters {
        return new external_function_parameters([]);
    }

    /**
     * Register SITS courses and modules in Moodle
     *
     * @deprecated Not used, no longer required.
     * @return null
     */
    public static function register_sitscourses() {
        return;
    }

    /**
     * Returned data format for register sitscourses
     *
     * @deprecated Not used, no longer required.
     * @return null
     */
    public static function register_sitscourses_returns() {
        return null;
    }

    /**
     * Returns the sitscourse record for a given courseid
     *
     * @deprecated Not used, no longer required.
     * @return external_function_parameters
     */
    public static function get_sitscourse_template_parameters(): external_function_parameters {
        return new external_function_parameters([]);
    }

    /**
     * Get the sitscourse record for a given courseid
     *
     * @deprecated Not used, no longer required.
     * @return null
     */
    public static function get_sitscourse_template() {
        return null;
    }

    /**
     * Returned data format for register sitscourses
     *
     * @deprecated Not used, no longer required.
     * @return null
     */
    public static function get_sitscourse_template_returns() {
        return null;
    }
}
