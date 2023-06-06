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
 * Template queue
 *
 * @package   local_solsits
 * @author    Mark Sharp <mark.sharp@solent.ac.uk>
 * @copyright 2023 Solent University {@link https://www.solent.ac.uk}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->libdir . '/adminlib.php');

admin_externalpage_setup('local_solsits/templatequeue', '', null, '/local/solsits/templatequeue.php');
$context = context_system::instance();
require_capability('local/solsits:managetemplates', $context);

$PAGE->set_context($context);
$PAGE->set_heading(get_string('templatequeue', 'local_solsits'));
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('templatequeue', 'local_solsits'));
$PAGE->set_url($CFG->wwwroot.'/local/solsits/templatequeue.php');

echo $OUTPUT->header();
$params = [
    'pagetype' => '',
    'session' => '',
    'selectedcourses' => []
];

echo html_writer::div(get_string('templatequeuehelp', 'local_solsits'));

$filterform = new \local_solsits\forms\template_filter_form(null);
if ($filterdata = $filterform->get_data()) {
    $params['pagetype'] = $filterdata->pagetype;
    $params['session'] = $filterdata->session;
    $params['selectedcourses'] = $filterdata->selectedcourses;
}

$filterform->display();

$table = new \local_solsits\tables\templatequeue_table('templatequeue', $params);

$table->out(100, false);

echo $OUTPUT->footer();
