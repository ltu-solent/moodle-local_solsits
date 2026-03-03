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
 * TODO describe file digestemails
 *
 * @package    local_solsits
 * @copyright  2026 Southampton Solent University {@link https://www.solent.ac.uk}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core\lang_string;
use local_solsits\helper;

defined('MOODLE_INTERNAL') || die();

$page = new admin_settingpage('local_solsits_digestemails', new lang_string('emailsettings', 'local_solsits'));

$page->add(
    new admin_setting_heading(
        'local_solsits/configchangeemails',
        new lang_string('configchangeemails', 'local_solsits'),
        new lang_string('configchangeemails_desc', 'local_solsits')
    )
);

$page->add(
    new admin_setting_configtextarea(
        'local_solsits/assignmentwarning_hidden',
        new lang_string('assignmentwarning_hidden', 'local_solsits'),
        new lang_string('assignmentwarning_hidden_desc', 'local_solsits'),
        '<p><strong>Warning</strong>This is a Summative assignment and it has been hidden.</p>' .
        '<p>Your students will not be able to see their grades or feedback; please make visible again.</p>'
    )
);

$page->add(
    new admin_setting_configtextarea(
        'local_solsits/assignmentwarning_wrongsection',
        new lang_string('assignmentwarning_wrongsection', 'local_solsits'),
        new lang_string('assignmentwarning_wrongsection_desc', 'local_solsits'),
        '<p><strong>Warning</strong>This is a Summative assignment and it has been moved out of the Assignments section.</p>' .
        '<p>Moving assignments where students don\'t expect can be confusing. ' .
        'Please contact Guided.Learning to move it back</p>'
    )
);

$page->add(
    new admin_setting_configtextarea(
        'local_solsits/assignmentwarning_body',
        new lang_string('assignmentwarning_body', 'local_solsits'),
        new lang_string('assignmentwarning_body_desc', 'local_solsits'),
        '<p>Dear {MODULELEADER}</p>' .
        '<p>Changes have recently been made to {ASSIGNMENTLINK} ({IDNUMBER}) on {COURSELINK}.' .
        'Please note these changes can affect access and Marks Uploads. See below for details.</p>' .
        '<p>Kind regards, Guided Learning</p>'
    )
);

$page->add(
    new admin_setting_heading(
        'local_solsits/assignmentconfigwarning',
        new lang_string('assignmentconfigwarning', 'local_solsits'),
        new lang_string('assignmentconfigwarning_desc', 'local_solsits')
    )
);

$rangesmenu = helper::get_ranges_menu();
$page->add(
    new admin_setting_configmultiselect(
        'local_solsits/assignmentconfigwarning_ranges',
        new lang_string('assignmentconfigwarning_ranges', 'local_solsits'),
        new lang_string('assignmentconfigwarning_ranges_desc', 'local_solsits'),
        [],
        $rangesmenu
    )
);

$page->add(
    new admin_setting_configtext(
        'local_solsits/assignmentconfigwarning_mailinglist',
        new lang_string('assignmentconfigwarning_mailinglist', 'local_solsits'),
        new lang_string('assignmentconfigwarning_mailinglist_desc', 'local_solsits'),
        '',
        PARAM_TEXT
    )
);

$page->add(
    new admin_setting_configtextarea(
        'local_solsits/assignmentconfigwarning_body',
        new lang_string('assignmentconfigwarning_body', 'local_solsits'),
        new lang_string('assignmentconfigwarning_body_desc', 'local_solsits'),
        '<p>Dear {MODULELEADER}</p>
        <p>The following assignments may not have been set up correctly.</p>
        <p>If you require your students to submit to any of these assignments, please set a <a href="#">submission type</a> as
        appropriate.</p>
        <p>If an assignment does not require your students to uploaded a submission (e.g. exams, in-class presentations,
        physical submission), then no further action is required.</p>
        <p>Thank you</p>
        <p>Kind regards, Guided Learning</p>'
    )
);

$page->add(
    new admin_setting_configtext(
        'local_solsits/assignmentduedatechange_mailinglist',
        new lang_string('assignmentduedatechange_mailinglist', 'local_solsits'),
        new lang_string('assignmentduedatechange_mailinglist_desc', 'local_solsits'),
        '',
        PARAM_TEXT
    )
);

$page->add(
    new admin_setting_configtextarea(
        'local_solsits/assignmentduedatechange_reasons',
        new lang_string('assignmentduedatechange_reasons', 'local_solsits'),
        new lang_string('assignmentduedatechange_reasons_desc', 'local_solsits'),
        '',
        PARAM_TEXT
    )
);

$page->add(
    new admin_setting_configtextarea(
        'local_solsits/assignmentduedatechange_body',
        new lang_string('assignmentduedatechange_body', 'local_solsits'),
        new lang_string('assignmentduedatechange_body_desc', 'local_solsits'),
        '<p>Dear Assessments</p>
        <p>Please change the due date for "{TITLE}" - "{SITSREF}" to {NEWDUEDATE}<p>
        <p>Kind regards, {TUTOR}</p>'
    )
);

$settings->add($page);
