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
 * Edit template page
 *
 * @package   local_solsits
 * @author    Mark Sharp <mark.sharp@solent.ac.uk>
 * @copyright 2022 Solent University {@link https://www.solent.ac.uk}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_solsits\forms\soltemplate_form;
use local_solsits\soltemplate;
require_once('../../config.php');
require_once($CFG->libdir . '/adminlib.php');


$id = optional_param('id', 0, PARAM_INT);
$action = optional_param('action', 'new', PARAM_ALPHA);
$confirmdelete = optional_param('confirmdelete', null, PARAM_BOOL);

if (!in_array($action, ['edit', 'delete', 'new'])) {
    $action = 'new';
}
$pageparams = [
    'action' => $action,
    'id' => $id,
];


admin_externalpage_setup('local_solsits/managetemplates', '', $pageparams, '/local/solsits/edittemplate.php');
$context = context_system::instance();
require_capability('local/solsits:managetemplates', $context);


$PAGE->set_context($context);
$PAGE->set_pagelayout('admin');
$PAGE->set_url(new moodle_url('/local/solsits/edittemplate.php', $pageparams));
$PAGE->navbar->add(get_string('localplugins'), new moodle_url('/admin/category.php?category=localplugins'));
$PAGE->navbar->add(get_string('pluginname', 'local_solsits'), new moodle_url('/admin/category.php?category=local_solsitscat'));
$PAGE->navbar->add(get_string('managetemplates', 'local_solsits'), new moodle_url('/local/solsits/managetemplates.php'));

$soltemplate = null;
$form = null;


if ($action == 'edit' || $action == 'delete') {
    if ($id == 0) {
        throw new moodle_exception('invalidtemplateid', 'local_solsits');
    }
} else {
    $action = 'new';
}

$soltemplate = new soltemplate($id);
$customdata = [
    'persistent' => $soltemplate,
    'userid' => $USER->id,
];

if ($confirmdelete && confirm_sesskey()) {
    $templatecourseid = $soltemplate->get('courseid');
    $title = $DB->get_field('course', 'fullname', ['id' => $templatecourseid]);
    $soltemplate->delete();
    redirect(new moodle_url('/local/solsits/managetemplates.php'),
        get_string('deletedtemplate', 'local_solsits', $title),
        null,
        \core\output\notification::NOTIFY_INFO);
}


$form = new soltemplate_form($PAGE->url->out(false), $customdata);
if ($form->is_cancelled()) {
    redirect(new moodle_url('/local/solsits/managetemplates.php'));
}
if ($formdata = $form->get_data()) {
    if (empty($formdata->id)) {
        $soltemplate = new soltemplate(0, $formdata);
        $soltemplate->create();
        redirect(new moodle_url('/local/solsits/managetemplates.php'),
            get_string('newsavedtemplate', 'local_solsits'),
            null,
            \core\output\notification::NOTIFY_SUCCESS);
    } else {
        $soltemplate = new soltemplate($formdata->id);
        if ($action == 'edit') {
            $soltemplate->from_record($formdata);
            $soltemplate->update();
            redirect(new moodle_url('/local/solsits/managetemplates.php'),
                get_string('updatedtemplate', 'local_solsits', $formdata->courseid),
                null,
                \core\output\notification::NOTIFY_SUCCESS);
        }
    }
}

$PAGE->set_title(get_string('editsoltemplate', 'local_solsits'));
$PAGE->set_heading(get_string('editsoltemplate', 'local_solsits'));

echo $OUTPUT->header();

if ($action == 'delete') {
    $templatecourseid = $soltemplate->get('courseid');
    $title = $DB->get_field('course', 'fullname', ['id' => $templatecourseid]);
    $heading = new lang_string('confirmdeletetemplate', 'local_solsits', $title);
    echo html_writer::tag('h3', $heading);
    $deleteurl = new moodle_url('/local/solsits/edittemplate.php', [
        'action' => 'delete',
        'confirmdelete' => true,
        'id' => $id,
        'sesskey' => sesskey(),
    ]);
    $deletebutton = new single_button($deleteurl, get_string('delete'));
    echo $OUTPUT->confirm(
        $heading,
        $deletebutton,
        new moodle_url('/local/solsits/managetemplates.php')
    );
} else {
    $heading = new lang_string('newsoltemplate', 'local_solsits');
    if ($id > 0) {
        $heading = new lang_string('editsoltemplate', 'local_solsits');
    }
    echo html_writer::tag('h3', $heading);

    $form->display();
}

echo $OUTPUT->footer();
