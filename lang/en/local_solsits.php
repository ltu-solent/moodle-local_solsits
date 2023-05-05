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
 * SolAssignments Language file
 *
 * @package   local_solsits
 * @author    Mark Sharp <mark.sharp@solent.ac.uk>
 * @copyright 2022 Solent University {@link https://www.solent.ac.uk}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['actions'] = 'Actions';
$string['applytemplatetask'] = 'Apply template';
$string['assessmentcode'] = 'Assessment code';
$string['assignmentsettings'] = 'Assignment settings';
$string['assignmentsettings_desc'] = 'Settings to help manage assignment creation';
$string['assignmenttitle'] = 'Assignment title';
$string['availablefrom'] = 'Available from';

$string['checkcoursedeleted'] = 'Check: This template no longer exists';
$string['cmid'] = 'Course module ID';
$string['confirmdeletetemplate'] = 'Confirm deletion of "{$a}"';
$string['courseidrequired'] = 'Courseid field required';
$string['coursename'] = 'Course name';
$string['createassignmenttask'] = 'Create assignment task';
$string['currentcourses'] = 'Display only current courses';
$string['currentcourses_help'] = 'Current courses are courses or modules that are currently running or have no end date';
$string['cutoffinterval'] = 'Cut off interval';
$string['cutoffinterval_desc'] = 'Cut off date interval in weeks';
$string['cutoffintervalsecondplus'] = 'Cut off second/third+ sittings';
$string['cutoffintervalsecondplus_desc'] = 'Cut off date interval in weeks for second/third+ sittings';

$string['defaultfilesubmissionfilesize'] = 'Default submission filesize';
$string['defaultfilesubmissionfilesize_desc'] = 'Default filesize for file submissions';
$string['defaultfilesubmissions'] = 'Default file submissions';
$string['defaultfilesubmissions_desc'] = 'Default number of files used by the file submission plugin.';
$string['deletedtemplate'] = '"{$a}" has been deleted.';
$string['deleteduser'] = 'Deleted user';
$string['duedate'] = 'Due date';
$string['duplicatepagetypesession'] = 'A template already exists for this session and page-type combination';

$string['editsoltemplate'] = 'Edit Template';
$string['enabled'] = 'Enabled';
$string['error:courseedited'] = 'Course has been edited. Cannot apply template. {$a}';
$string['error:courseiddoesnotmatch'] = 'Given courseid doesn\'t match the one on record';
$string['error:coursenotexist'] = 'Course specified doesn\'t exist: {$a}';
$string['error:coursevisible'] = 'Course visible. Cannot apply template. {$a}';
$string['error:invalidpagetype'] = 'Invalid pagetype: {$a}';
$string['error:sitsrefinuse'] = 'SITS Reference already in use: {$a}';
$string['error:sitsrefnotexist'] = 'SITS reference doesn\'t exist: {$a}';
$string['error:usersenrolledalready'] = 'Enrolments already exist. Cannot apply template. {$a}';
$string['externaldate'] = 'External date';

$string['generalsettings'] = 'General settings';
$string['grademark'] = 'Grademark';
$string['grademarkexempt'] = 'Grademark exempt';
$string['grademarkexemptscale'] = 'Grademark exempt scale';
$string['grademarkexemptscale_desc'] = 'Standard 100 point scale';
$string['grademarkscale'] = 'Grademark scale';
$string['grademarkscale_desc'] = 'Solent Grade scale A1, A2, A3, B1 etc';
$string['gradingdueinterval'] = 'Grading due interval';
$string['gradingdueinterval_desc'] = 'Grading due date interval in weeks';

$string['immediately'] = 'Immediately';
$string['invalidpagetype'] = 'Invalid pagetype';
$string['invalidsession'] = 'Invalid session';

$string['lastmodified'] = 'Last modified';

$string['manageassignments'] = 'Manage assignments';
$string['managetemplates'] = 'Manage templates';
$string['maxassignments'] = 'Max assignments';
$string['maxassignments_desc'] = 'Maximum number of assignments to create in a single run.';
$string['maxtemplates'] = 'Max templates';
$string['maxtemplates_desc'] = 'Maximum number of courses to apply templates to in a single run.';
$string['modifiedby'] = 'Modified by';

$string['newsavedtemplate'] = 'New template saved';
$string['newsoltemplate'] = 'New template';
$string['nolongerexists'] = 'No longer exists';
$string['notenabled'] = 'Not enabled';
$string['notset'] = 'Not set';

$string['pagetype'] = 'Page type';
$string['pagetype_help'] = 'Is this template for a Course or a Module';
$string['passfail'] = 'Pass/Fail';
$string['passfailscale'] = 'Pass/Fail scale';
$string['pluginname'] = 'SOL-SITS Integration';

$string['reattempt'] = 'Re-attempt';
$string['reattempt0'] = '';
$string['reattempt1'] = 'First reattempt';
$string['reattempt2'] = 'Second reattempt';
$string['reattempt3'] = 'Third reattempt';
$string['reattempt4'] = 'Fourth reattempt';
$string['reattempt5'] = 'Fifth reattempt';
$string['reattempt6'] = 'Sixth reattempt';
$string['recreate'] = 'Recreate Assignment activity';

$string['scale'] = 'Scale';
$string['selectasession'] = 'Select a session';
$string['selectatemplate'] = 'Select a template';
$string['selectcourses'] = 'Select courses';
$string['sequence'] = 'Sequence';
$string['session'] = 'Academic session';
$string['showerrorsonly'] = 'Show errors only';
$string['showerrorsonly_help'] = 'The following errors are identified:<ol><li>Broken or no Assignment link</li>
    <li>No duedate</li>
    <li>Invisible course or activity</li>
    </ol>';
$string['sits'] = 'SITS';
$string['sitsreattempt'] = 'Re-attempt';
$string['sitsreference'] = 'SITS reference';
$string['sittingdescription'] = 'Sitting description';
$string['sittingreference'] = 'Sitting reference';
$string['solassignments:manage'] = 'Manage SITS Assignments';
$string['solsits:manageassignments'] = 'Manage SITS assignments';
$string['solsits:managetemplates'] = 'Manage Templates';
$string['solsits:registersitscourse'] = 'Register SITS course';
$string['solsits:releasegrades'] = 'Release grades';
$string['status'] = 'Status';

$string['targetsection'] = 'Assignment section';
$string['targetsection_desc'] = 'Which section No. should assignments be created in?';
$string['templateapplied'] = 'Template {$a->templatekey} has been applied to {$a->courseidnumber}';
$string['templatecat'] = 'Template category';
$string['templatecat_desc'] = 'The source of all template files';
$string['templatecourse'] = 'Template course';
$string['templatename'] = 'Template name';
$string['templates'] = 'Templates';
$string['templates_desc'] = 'Module and Course templates assigned to sessions';
$string['timecreated'] = 'Time created';
$string['timemodified'] = 'Time modified';

$string['updatedtemplate'] = '"{$a}" has been updated.';

$string['weighting'] = 'Weighting';

