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
 * Public services
 *
 * @package   local_solsits
 * @author    Mark Sharp <mark.sharp@solent.ac.uk>
 * @copyright 2022 Solent University {@link https://www.solent.ac.uk}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [
    'local_solsits_add_assignments' => array(
        'classname'   => 'local_solsits_external',
        'methodname'  => 'add_assignments',
        'classpath'   => 'local/solsits/externallib.php',
        'description' => 'Add assignments via the AIS-SITS interface',
        'capabilities' => 'local/solsits:manageassignments',
        'type'        => 'write',
    ),
    'local_solsits_update_assignments' => array(
        'classname'   => 'local_solsits_external',
        'methodname'  => 'update_assignments',
        'classpath'   => 'local/solsits/externallib.php',
        'description' => 'Update assignments via the AIS-SITS interface',
        'capabilities' => 'local/solsits:manageassignments',
        'type'        => 'write',
    ),
    'local_solsits_register_sitscourses' => [
        'classname' => 'local_solsits_external',
        'methodname' => 'register_sitscourses',
        'classpath' => 'local/solsits/externallib.php',
        'description' => 'Register that a Moodle course has come from SITS so that templates can be appplied',
        'capabilities' => 'local/solsits:managetemplates',
        'type' => 'write'
    ]
];