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

namespace local_solsits\external;

use core\exception\moodle_exception;
use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use local_solsits\ais_client;

/**
 * Class ais_testconnection
 *
 * @package    local_solsits
 * @copyright  2024 Southampton Solent University {@link https://www.solent.ac.uk}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class ais_testconnection extends external_api {
    /**
     * AIS parameters (none required).
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'something' => new external_value(PARAM_TEXT, 'Nothing needed'),
        ]);
    }

    /**
     * Test AIS function. Only available to siteadmins.
     *
     * @return array Response from remote API
     */
    public static function execute() {
        if (!is_siteadmin()) {
            throw new moodle_exception('nopermissions');
        }
        $config = get_config('local_solsits');
        $client = new ais_client([], $config->ais_exportgrades_url, $config->ais_exportgrades_key);
        $result = $client->test_connection();
        return ['result' => $result];
    }

    /**
     * Return result for ais_testconnection
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'result' => new external_value(PARAM_RAW, 'Something interesting'),
        ]);
    }
}
