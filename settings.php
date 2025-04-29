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

use local_solsits\helper;

defined('MOODLE_INTERNAL') || die();

$parent = new admin_category('local_solsitscat', new lang_string('pluginname', 'local_solsits'));
if ($hassiteconfig) {
    $ADMIN->add('localplugins', $parent);
    $settings = new admin_settingpage('local_solsits_general', new lang_string('generalsettings', 'local_solsits'));

    $name = 'local_solsits/templates';
    $title = new lang_string('templates', 'local_solsits');
    $description = new lang_string('templates_desc', 'local_solsits');
    $settings->add(new admin_setting_heading($name, $title, $description));

    $name = 'local_solsits/templatecat';
    $title = new lang_string('templatecat', 'local_solsits');
    $description = new lang_string('templatecat_desc', 'local_solsits');
    $options = core_course_category::make_categories_list('moodle/category:manage');
    $settings->add(new admin_setting_configselect($name, $title, $description, '', $options));

    $name = 'local_solsits/maxtemplates';
    $title = new lang_string('maxtemplates', 'local_solsits');
    $description = new lang_string('maxtemplates_desc', 'local_solsits');
    $options = array_combine(range(1, 30), range(1, 30));
    $settings->add(new admin_setting_configselect($name, $title, $description, 1, $options));

    $name = 'local_solsits/assignmentsettings';
    $title = new lang_string('assignmentsettings', 'local_solsits');
    $description = new lang_string('assignmentsettings_desc', 'local_solsits');
    $settings->add(new admin_setting_heading($name, $title, $description));

    $name = 'local_solsits/targetsection';
    $title = new lang_string('targetsection', 'local_solsits');
    $description = new lang_string('targetsection_desc', 'local_solsits');
    $options = array_combine(range(1, 10), range(1, 10));
    $settings->add(new admin_setting_configselect($name, $title, $description, 1, $options));

    $name = 'local_solsits/defaultscale';
    $title = new lang_string('defaultscale', 'local_solsits');
    $description = new lang_string('defaultscale_desc', 'local_solsits');
    $default = '';
    $options = [
        '' => new lang_string('nodefaultscale', 'local_solsits'),
        'points' => new lang_string('points', 'local_solsits'),
        'grademarkscale' => new lang_string('grademarkscale', 'local_solsits'),
        'grademarkexemptscale' => new lang_string('grademarkexemptscale', 'local_solsits'),
        'numericscale' => new lang_string('numericscale', 'local_solsits'),
    ];
    $settings->add(new admin_setting_configselect($name, $title, $description, $default, $options));

    // Get available site scales.
    $scaleoptions = [
        '' => new lang_string('selectascale', 'local_solsits'),
    ] + get_scales_menu();

    // Ensure any new scales that are introduced have a setting name that ends in "scale".
    $name = 'local_solsits/grademarkscale';
    $title = new lang_string('grademarkscale', 'local_solsits');
    $description = new lang_string('grademarkscale_desc', 'local_solsits');
    $default = '';
    $settings->add(new admin_setting_configselect($name, $title, $description, $default, $scaleoptions));

    $name = 'local_solsits/grademarkexemptscale';
    $title = new lang_string('grademarkexemptscale', 'local_solsits');
    $description = new lang_string('grademarkexemptscale_desc', 'local_solsits');
    $default = '';
    $settings->add(new admin_setting_configselect($name, $title, $description, $default, $scaleoptions));

    $name = 'local_solsits/numericscale';
    $title = new lang_string('numericscale', 'local_solsits');
    $description = new lang_string('numericscale_desc', 'local_solsits');
    $default = '';
    $settings->add(new admin_setting_configselect($name, $title, $description, $default, $scaleoptions));

    $name = 'local_solsits/cutoffinterval';
    $title = new lang_string('cutoffinterval', 'local_solsits');
    $description = new lang_string('cutoffinterval_desc', 'local_solsits');
    $default = 1;
    $onetotenoptions = array_combine(range(1, 10), range(1, 10));
    $settings->add(new admin_setting_configselect($name, $title, $description, $default, $onetotenoptions));

    $name = 'local_solsits/cutoffintervalsecondplus';
    $title = new lang_string('cutoffintervalsecondplus', 'local_solsits');
    $description = new lang_string('cutoffintervalsecondplus_desc', 'local_solsits');
    $default = 1;
    $settings->add(new admin_setting_configselect($name, $title, $description, $default, $onetotenoptions));

    $name = 'local_solsits/gradingdueinterval';
    $title = new lang_string('gradingdueinterval', 'local_solsits');
    $description = new lang_string('gradingdueinterval_desc', 'local_solsits');
    $default = 1;
    $settings->add(new admin_setting_configselect($name, $title, $description, $default, $onetotenoptions));

    $name = 'local_solsits/gradingdueintervalsecondplus';
    $title = new lang_string('gradingdueintervalsecondplus', 'local_solsits');
    $description = new lang_string('gradingdueintervalsecondplus_desc', 'local_solsits');
    $default = 1;
    $settings->add(new admin_setting_configselect($name, $title, $description, $default, $onetotenoptions));

    $name = 'local_solsits/defaultfilesubmissions';
    $title = new lang_string('defaultfilesubmissions', 'local_solsits');
    $description = new lang_string('defaultfilesubmissions_desc', 'local_solsits');
    $maxfiles = get_config('assignsubmission_file', 'maxfiles') ?? 20;
    $options = array_combine(range(1, $maxfiles), range(1, $maxfiles));
    $default = 1;
    $settings->add(new admin_setting_configselect($name, $title, $description, $default, $options));

    $name = 'local_solsits/defaultfilesubmissionfilesize';
    $title = new lang_string('defaultfilesubmissionfilesize', 'local_solsits');
    $description = new lang_string('defaultfilesubmissionfilesize_desc', 'local_solsits');
    $options = get_max_upload_sizes(
        $CFG->maxbytes,
        get_config('moodlecourse', 'maxbytes'),
        get_config('assignsubmission_file', 'maxbytes'));
    $default = 104857600;
    $settings->add(new admin_setting_configselect($name, $title, $description, $default, $options));

    $name = 'local_solsits/maxassignments';
    $title = new lang_string('maxassignments', 'local_solsits');
    $description = new lang_string('maxassignments_desc', 'local_solsits');
    $options = array_combine(range(1, 30), range(1, 30));
    $settings->add(new admin_setting_configselect($name, $title, $description, 1, $options));

    $name = 'local_solsits/limittoyears';
    $title = new lang_string('limittoyears', 'local_solsits');
    $description = new lang_string('limittoyears_desc', 'local_solsits');
    $options = \local_solsits\helper::get_session_menu();
    $settings->add(new admin_setting_configmultiselect($name, $title, $description, [], $options));

    $name = 'local_solsits/createreattemptgroups';
    $title = new lang_string('createreattemptgroups', 'local_solsits');
    $description = new lang_string('createreattemptgroups_desc', 'local_solsits');
    $settings->add(new admin_setting_configcheckbox($name, $title, $description, 0));

    $name = 'local_solsits/addavailabilitytoreattempt';
    $title = new lang_string('addavailabilitytoreattempt', 'local_solsits');
    $description = new lang_string('addavailabilitytoreattempt_desc', 'local_solsits');
    $settings->add(new admin_setting_configcheckbox($name, $title, $description, 0));

    // Assignment message settings.
    $name = 'local_solsits/assignmentmessagesettings';
    $title = new lang_string('assignmentmessagesettings', 'local_solsits');
    $description = new lang_string('assignmentmessagesettings_desc', 'local_solsits');
    $settings->add(new admin_setting_heading($name, $title, $description));

    $name = 'local_solsits/assignmentmessage_marksuploadinclude';
    $title = new lang_string('assignmentmessage_marksuploadinclude', 'local_solsits');
    $description = new lang_string('assignmentmessage_marksuploadinclude_desc', 'local_solsits');
    $settings->add(new admin_setting_configtextarea($name, $title, $description, '<p>Grades for this assignment will be sent to
     {SRS} once they have been released to students in SOL.</p>
    <p><strong>Please do not add marks out of 100 in Turnitin</strong> as it can cause issues with grades sent to {SRS}.</p>
    <p>For technical guidance please visit refer to the <strong><a href="https://learn.solent.ac.uk/staff-help" target="_blank"
     rel="noopener noreferrer">assignment help</a></strong>
     or contact ltu@solent.ac.uk (ext. 5100). For amendments to dates please email student.registry@solent.ac.uk.</p>'));

    $name = 'local_solsits/assignmentmessage_reattempt';
    $title = new lang_string('assignmentmessage_reattempt', 'local_solsits');
    $description = new lang_string('assignmentmessage_reattempt_desc', 'local_solsits');
    $settings->add(
        new admin_setting_configtextarea(
            $name, $title, $description,
            'Please do not release these {REATTEMPT} submissions before the first attempt submissions have been ' .
            'marked and released.'
        )
    );

    $name = 'local_solsits/assignmentmessage_studentreattempt';
    $title = new lang_string('assignmentmessage_studentreattempt', 'local_solsits');
    $description = new lang_string('assignmentmessage_studentreattempt_desc', 'local_solsits');
    $settings->add(
        new admin_setting_configtextarea(
            $name, $title, $description,
            'Note: You only need to submit to this assignment if you have been asked to do so. If you are not sure, please ' .
            'speak to your Module leader.'
        )
    );

    $name = 'local_solsits/assignmentwarning_hidden';
    $title = new lang_string('assignmentwarning_hidden', 'local_solsits');
    $description = new lang_string('assignmentwarning_hidden_desc', 'local_solsits');
    $settings->add(
        new admin_setting_configtextarea($name, $title, $description,
        '<p><strong>Warning</strong>This is a Summative assignment and it has been hidden.</p>' .
        '<p>Your students will not be able to see their grades or feedback; please make visible again.</p>'
        )
    );

    $name = 'local_solsits/assignmentwarning_wrongsection';
    $title = new lang_string('assignmentwarning_wrongsection', 'local_solsits');
    $description = new lang_string('assignmentwarning_wrongsection_desc', 'local_solsits');
    $settings->add(
        new admin_setting_configtextarea($name, $title, $description,
        '<p><strong>Warning</strong>This is a Summative assignment and it has been moved out of the Assignments section.</p>' .
        '<p>Moving assignments where students don\'t expect can be confusing. Please contact Guided.Learning to move it back</p>'
        )
    );

    $name = 'local_solsits/assignmentwarning_body';
    $title = new lang_string('assignmentwarning_body', 'local_solsits');
    $description = new lang_string('assignmentwarning_body_desc', 'local_solsits');
    $settings->add(
        new admin_setting_configtextarea($name, $title, $description,
        '<p>Dear {MODULELEADER}</p>' .
        '<p>Changes have recently been made to {ASSIGNMENTLINK} ({IDNUMBER}) on {COURSELINK}.' .
        'Please note these changes can affect access and Marks Uploads. See below for details.</p>' .
        '<p>Kind regards, Guided Learning</p>'
        )
    );

    $name = 'local_solsits/assignmentconfigwarning_ranges';
    $title = new lang_string('assignmentconfigwarning_ranges', 'local_solsits');
    $description = new lang_string('assignmentconfigwarning_ranges_desc', 'local_solsits');
    $options = helper::get_ranges_menu();
    $settings->add(new admin_setting_configmultiselect($name, $title, $description, [], $options));

    $name = 'local_solsits/assignmentconfigwarning_mailinglist';
    $title = new lang_string('assignmentconfigwarning_mailinglist', 'local_solsits');
    $description = new lang_string('assignmentconfigwarning_mailinglist_desc', 'local_solsits');
    $settings->add(new admin_setting_configtext($name, $title, $description, '', PARAM_TEXT));

    $name = 'local_solsits/assignmentconfigwarning_body';
    $title = new lang_string('assignmentconfigwarning_body', 'local_solsits');
    $description = new lang_string('assignmentconfigwarning_body_desc', 'local_solsits');
    $settings->add(
        new admin_setting_configtextarea($name, $title, $description,
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

    $name = 'local_solsits/assignmentduedatechange_mailinglist';
    $title = new lang_string('assignmentduedatechange_mailinglist', 'local_solsits');
    $description = new lang_string('assignmentduedatechange_mailinglist_desc', 'local_solsits');
    $settings->add(new admin_setting_configtext($name, $title, $description, '', PARAM_TEXT));

    $name = 'local_solsits/assignmentduedatechange_body';
    $title = new lang_string('assignmentduedatechange_body', 'local_solsits');
    $description = new lang_string('assignmentduedatechange_body_desc', 'local_solsits');
    $settings->add(
        new admin_setting_configtextarea($name, $title, $description,
        '<p>Dear Assessments</p>
        <p>Please change the due date for "{TITLE}" - "{SITSREF}" to {NEWDUEDATE}<p>
        <p>Kind regards, {TUTOR}</p>')
    );

    $name = 'local_solsits/marksuploadssettings';
    $title = new lang_string('marksuploadssettings', 'local_solsits');
    $description = '';
    $settings->add(new admin_setting_heading($name, $title, $description));

    $name = 'local_solsits/ais_exportgrades_url';
    $title = new lang_string('marksuploads_url', 'local_solsits');
    $description = new lang_string('marksuploads_url_desc', 'local_solsits');
    $settings->add(new admin_setting_configtext($name, $title, $description, '', PARAM_URL));

    $name = 'local_solsits/ais_exportgrades_endpoint';
    $title = new lang_string('marksuploads_endpoint', 'local_solsits');
    $description = new lang_string('marksuploads_endpoint_desc', 'local_solsits');
    $settings->add(new admin_setting_configtext($name, $title, $description, '', PARAM_PATH));

    $name = 'local_solsits/ais_exportgrades_key';
    $title = new lang_string('marksuploads_key', 'local_solsits');
    $description = new lang_string('marksuploads_key_desc', 'local_solsits');
    $settings->add(new admin_setting_configpasswordunmask($name, $title, $description, ''));

    $name = 'local_solsits/ais_testconnection';
    $title = new lang_string('ais_testconnection', 'local_solsits');
    $description = html_writer::tag('p',
        html_writer::tag('button',
            $title,
            [
                'class' => 'btn btn-primary',
                'id' => 'ais_testconnection',
            ]
        ) . html_writer::tag('span', '', ['id' => 'ais_testconnection_response', 'class' => 'pl-1'])

    );
    $settings->add(new admin_setting_description($name, '', $description));
    $PAGE->requires->js_call_amd('local_solsits/ais-testconnection', 'init');

    $name = 'local_solsits/marksuploads_maxassignments';
    $title = new lang_string('marksuploads_maxassignments', 'local_solsits');
    $description = new lang_string('marksuploads_maxassignments_desc', 'local_solsits');
    $settings->add(new admin_setting_configselect($name, $title, $description, 1, $onetotenoptions));

    $name = 'local_solsits/marksuploads_batchgrades';
    $title = new lang_string('marksuploads_batchgrades', 'local_solsits');
    $description = new lang_string('marksuploads_batchgrades_desc', 'local_solsits');
    $options = [
        '-1' => get_string('allgrades', 'local_solsits'),
        '25' => '25',
        '50' => '50',
        '75' => '75',
        '100' => '100',
        '125' => '125',
        '150' => '150',
    ];
    $settings->add(new admin_setting_configselect($name, $title, $description, '-1', $options));

    $ADMIN->add('local_solsitscat', $settings);

    $name = 'local_solsits/managetemplates';
    $title = new lang_string('managetemplates', 'local_solsits');
    $url = new moodle_url('/local/solsits/managetemplates.php');
    $externalpage = new admin_externalpage($name, $title, $url);

    $ADMIN->add('local_solsitscat', $externalpage);

    $name = 'local_solsits/manageassignments';
    $title = new lang_string('manageassignments', 'local_solsits');
    $url = new moodle_url('/local/solsits/manageassignments.php');
    $externalpage = new admin_externalpage($name, $title, $url);

    $ADMIN->add('local_solsitscat', $externalpage);

    $name = 'local_solsits/templatequeue';
    $title = new lang_string('templatequeue', 'local_solsits');
    $url = new moodle_url('/local/solsits/templatequeue.php');
    $externalpage = new admin_externalpage($name, $title, $url);

    $ADMIN->add('local_solsitscat', $externalpage);
}
