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
 * TODO describe file assignments
 *
 * @package    local_solsits
 * @copyright  2026 Southampton Solent University {@link https://www.solent.ac.uk}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core\lang_string;

defined('MOODLE_INTERNAL') || die();

$page = new admin_settingpage('local_solsits_assignments', new lang_string('assignmentsettings', 'local_solsits'));

$page->add(
    new admin_setting_heading(
        'local_solsits/assignmentsettings',
        new lang_string('assignmentsettings', 'local_solsits'),
        new lang_string('assignmentsettings_desc', 'local_solsits')
    )
);

$page->add(
    new admin_setting_configselect(
        'local_solsits/targetsection',
        new lang_string('targetsection', 'local_solsits'),
        new lang_string('targetsection_desc', 'local_solsits'),
        1,
        array_combine(range(1, 10), range(1, 10))
    )
);

$page->add(
    new admin_setting_configselect(
        'local_solsits/defaultscale',
        new lang_string('defaultscale', 'local_solsits'),
        new lang_string('defaultscale_desc', 'local_solsits'),
        '',
        [
            '' => new lang_string('nodefaultscale', 'local_solsits'),
            'points' => new lang_string('points', 'local_solsits'),
            'grademarkscale' => new lang_string('grademarkscale', 'local_solsits'),
            'grademarkexemptscale' => new lang_string('grademarkexemptscale', 'local_solsits'),
            'numericscale' => new lang_string('numericscale', 'local_solsits'),
        ]
    )
);

// Get available site scales.
$scaleoptions = [
    '' => new lang_string('selectascale', 'local_solsits'),
] + get_scales_menu();

// Ensure any new scales that are introduced have a setting name that ends in "scale".
$page->add(
    new admin_setting_configselect(
        'local_solsits/grademarkscale',
        new lang_string('grademarkscale', 'local_solsits'),
        new lang_string('grademarkscale_desc', 'local_solsits'),
        '',
        $scaleoptions
    )
);

$page->add(
    new admin_setting_configselect(
        'local_solsits/grademarkexemptscale',
        new lang_string('grademarkexemptscale', 'local_solsits'),
        new lang_string('grademarkexemptscale_desc', 'local_solsits'),
        '',
        $scaleoptions
    )
);

$page->add(
    new admin_setting_configselect(
        'local_solsits/numericscale',
        new lang_string('numericscale', 'local_solsits'),
        new lang_string('numericscale_desc', 'local_solsits'),
        '',
        $scaleoptions
    )
);

$page->add(
    new admin_setting_configselect(
        'local_solsits/cutoffinterval',
        new lang_string('cutoffinterval', 'local_solsits'),
        new lang_string('cutoffinterval_desc', 'local_solsits'),
        1,
        $onetotenoptions
    )
);

$page->add(
    new admin_setting_configselect(
        'local_solsits/cutoffintervalsecondplus',
        new lang_string('cutoffintervalsecondplus', 'local_solsits'),
        new lang_string('cutoffintervalsecondplus_desc', 'local_solsits'),
        1,
        $onetotenoptions
    )
);

$page->add(
    new admin_setting_configselect(
        'local_solsits/gradingdueinterval',
        new lang_string('gradingdueinterval', 'local_solsits'),
        new lang_string('gradingdueinterval_desc', 'local_solsits'),
        1,
        $onetotenoptions
    )
);

$page->add(
    new admin_setting_configselect(
        'local_solsits/gradingdueintervalsecondplus',
        new lang_string('gradingdueintervalsecondplus', 'local_solsits'),
        new lang_string('gradingdueintervalsecondplus_desc', 'local_solsits'),
        1,
        $onetotenoptions
    )
);

$maxfiles = get_config('assignsubmission_file', 'maxfiles') ?? 20;
$options = array_combine(range(1, $maxfiles), range(1, $maxfiles));
$page->add(
    new admin_setting_configselect(
        'local_solsits/defaultfilesubmissions',
        new lang_string('defaultfilesubmissions', 'local_solsits'),
        new lang_string('defaultfilesubmissions_desc', 'local_solsits'),
        1,
        $options
    )
);

$options = get_max_upload_sizes(
    $CFG->maxbytes,
    get_config('moodlecourse', 'maxbytes'),
    get_config('assignsubmission_file', 'maxbytes')
);
$page->add(
    new admin_setting_configselect(
        'local_solsits/defaultfilesubmissionfilesize',
        new lang_string('defaultfilesubmissionfilesize', 'local_solsits'),
        new lang_string('defaultfilesubmissionfilesize_desc', 'local_solsits'),
        104857600,
        $options
    )
);

$page->add(
    new admin_setting_configselect(
        'local_solsits/maxassignments',
        new lang_string('maxassignments', 'local_solsits'),
        new lang_string('maxassignments_desc', 'local_solsits'),
        1,
        array_combine(range(1, 30), range(1, 30))
    )
);

$page->add(
    new admin_setting_configmultiselect(
        'local_solsits/limittoyears',
        new lang_string('limittoyears', 'local_solsits'),
        new lang_string('limittoyears_desc', 'local_solsits'),
        [],
        \local_solsits\helper::get_session_menu()
    )
);

$page->add(
    new admin_setting_configcheckbox(
        'local_solsits/createreattemptgroups',
        new lang_string('createreattemptgroups', 'local_solsits'),
        new lang_string('createreattemptgroups_desc', 'local_solsits'),
        0
    )
);

$page->add(
    new admin_setting_configcheckbox(
        'local_solsits/addavailabilitytoreattempt',
        new lang_string('addavailabilitytoreattempt', 'local_solsits'),
        new lang_string('addavailabilitytoreattempt_desc', 'local_solsits'),
        0
    )
);

$settings->add($page);
