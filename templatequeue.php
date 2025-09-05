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

use core\context;
use core\output\html_writer;

require_once('../../config.php');
require_once($CFG->libdir . '/adminlib.php');

admin_externalpage_setup('local_solsits/templatequeue', '', null, '/local/solsits/templatequeue.php');
$context = context\system::instance();
require_capability('local/solsits:managetemplates', $context);

// When paging results with the filter form, the form won't find the params in the url, so
// explicitly set them.
$pagetype = optional_param('pagetype', '', PARAM_ALPHANUMEXT);
$session = optional_param('session', '', PARAM_TEXT);
$selectedcourses = optional_param_array('selectedcourses', [], PARAM_INT);
$params = [
    'pagetype' => $pagetype,
    'session' => $session,
    'selectedcourses' => $selectedcourses,
];

$PAGE->set_context($context);
$PAGE->set_heading(get_string('templatequeue', 'local_solsits'));
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('templatequeue', 'local_solsits'));
$PAGE->set_url($CFG->wwwroot . '/local/solsits/templatequeue.php');

$filterform = new \local_solsits\forms\template_filter_form(null, $params);
if ($filterdata = $filterform->get_data()) {
    $params['pagetype'] = $filterdata->pagetype ?? '';
    $params['session'] = $filterdata->session ?? '';
    $params['selectedcourses'] = $filterdata->selectedcourses ?? [];
}

echo $OUTPUT->header();

echo html_writer::div(get_string('templatequeuehelp', 'local_solsits'));

$filterform->display();

$table = new \local_solsits\tables\templatequeue_table('templatequeue', $params);

$table->out(100, false);

echo $OUTPUT->footer();
