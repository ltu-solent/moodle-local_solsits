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
 * TODO describe file contextualhelp
 *
 * @package    local_solsits
 * @copyright  2026 Southampton Solent University {@link https://www.solent.ac.uk}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core\lang_string;

defined('MOODLE_INTERNAL') || die();

$page = new admin_settingpage('local_solsits_contextualhelp', new lang_string('contextualhelp', 'local_solsits'));

// Assignment message settings.
$page->add(
    new admin_setting_heading(
        'local_solsits/assignmentmessagesettings',
        new lang_string('assignmentmessagesettings', 'local_solsits'),
        new lang_string('assignmentmessagesettings_desc', 'local_solsits')
    )
);

$page->add(
    new admin_setting_configtextarea(
        'local_solsits/assignmentmessage_marksuploadinclude',
        new lang_string('assignmentmessage_marksuploadinclude', 'local_solsits'),
        new lang_string('assignmentmessage_marksuploadinclude_desc', 'local_solsits'),
        '<p>Grades for this assignment will be sent to {SRS} once they have been released to students in SOL.</p>
<p><strong>Please do not add marks out of 100 in Turnitin</strong> as it can cause issues with grades sent to {SRS}.</p>
<p>For technical guidance please visit refer to the <strong><a href="https://learn.solent.ac.uk/staff-help" target="_blank"
    rel="noopener noreferrer">assignment help</a></strong>
    or contact ltu@solent.ac.uk (ext. 5100). For amendments to dates please email student.registry@solent.ac.uk.</p>'
    )
);

$page->add(
    new admin_setting_configtextarea(
        'local_solsits/assignmentmessage_reattempt',
        new lang_string('assignmentmessage_reattempt', 'local_solsits'),
        new lang_string('assignmentmessage_reattempt_desc', 'local_solsits'),
        'Please do not release these {REATTEMPT} submissions before the first attempt submissions have been ' .
        'marked and released.'
    )
);

$page->add(
    new admin_setting_configtextarea(
        'local_solsits/assignmentmessage_studentreattempt',
        new lang_string('assignmentmessage_studentreattempt', 'local_solsits'),
        new lang_string('assignmentmessage_studentreattempt_desc', 'local_solsits'),
        'Note: You only need to submit to this assignment if you have been asked to do so. If you are not sure, please ' .
        'speak to your Module leader.'
    )
);

$settings->add($page);
