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
 * Manage templates page
 *
 * @package   local_solsits
 * @author    Mark Sharp <mark.sharp@solent.ac.uk>
 * @copyright 2022 Solent University {@link https://www.solent.ac.uk}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->libdir . '/adminlib.php');

// admin_externalpage_setup('local_solsitscat', '', null, '/local/solsits/managetemplates.php');
$context = context_system::instance();
require_capability('local/solsits:managetemplates', $context);

$PAGE->set_context($context);
$PAGE->set_heading(get_string('pluginname', 'local_solsits'));
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('pluginname', 'local_solsits'));
$PAGE->set_url($CFG->wwwroot.'/local/solalerts/index.php');

echo $OUTPUT->header();

$new = new action_link(new moodle_url('/local/solsits/edittemplate.php', ['action' => 'new']),
    get_string('newsoltemplate', 'local_solsits'), null,
    ['class' => 'btn btn-primary']);
echo $OUTPUT->render($new);

$table = new \local_solsits\tables\soltemplates_table('soltemplates');

$table->out(100, false);

echo $OUTPUT->footer();
