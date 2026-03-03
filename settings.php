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
 * Settings for Sol SITs integrations
 *
 * @package   local_solsits
 * @author    Mark Sharp <mark.sharp@solent.ac.uk>
 * @copyright 2022 Solent University {@link https://www.solent.ac.uk}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core\lang_string;
use core\url;

defined('MOODLE_INTERNAL') || die();

$parent = new admin_category('local_solsitscat', new lang_string('pluginname', 'local_solsits'));
if ($hassiteconfig) {
    $ADMIN->add('localplugins', $parent);
    $externalpage = new admin_externalpage(
        'local_solsits/managetemplates',
        new lang_string('managetemplates', 'local_solsits'),
        new url('/local/solsits/managetemplates.php')
    );
    $ADMIN->add('local_solsitscat', $externalpage);

    $externalpage = new admin_externalpage(
        'local_solsits/templatequeue',
        new lang_string('templatequeue', 'local_solsits'),
        new url('/local/solsits/templatequeue.php')
    );
    $ADMIN->add('local_solsitscat', $externalpage);

    $externalpage = new admin_externalpage(
        'local_solsits/manageassignments',
        new lang_string('manageassignments', 'local_solsits'),
        new url('/local/solsits/manageassignments.php')
    );
    $ADMIN->add('local_solsitscat', $externalpage);

    $settings = new theme_boost_admin_settingspage_tabs(
        'local_solsits_general',
        new lang_string('generalsettings', 'local_solsits')
    );
    $onetotenoptions = array_combine(range(1, 10), range(1, 10));

    include($CFG->dirroot . '/local/solsits/settings/assignments.php');
    include($CFG->dirroot . '/local/solsits/settings/templates.php');
    include($CFG->dirroot . '/local/solsits/settings/contextualhelp.php');
    include($CFG->dirroot . '/local/solsits/settings/digestemails.php');
    include($CFG->dirroot . '/local/solsits/settings/marksuploads.php');


    $ADMIN->add('local_solsitscat', $settings);
}
