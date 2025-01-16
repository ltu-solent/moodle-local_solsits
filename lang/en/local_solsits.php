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
$string['ais_testconnection'] = 'Test connection';
$string['allgrades'] = 'All grades';
$string['allocatedtemplate'] = 'Allocated template';
$string['applytemplatetask'] = 'Apply template';
$string['assessmentcode'] = 'Assessment code';
$string['assessmentname'] = 'Assessment name';
$string['assignmentmessage_marksuploadinclude'] = 'Marks upload message';
$string['assignmentmessage_marksuploadinclude_desc'] = 'Message appears on grading page to remind marks about the Marks upload process.';
$string['assignmentmessage_reattempt'] = 'Reattempt message';
$string['assignmentmessage_reattempt_desc'] = 'Message appears on the grading page when this is a reattempt, to warn about early release.';
$string['assignmentmessage_studentreattempt'] = 'Reattempt message for students';
$string['assignmentmessage_studentreattempt_desc'] = 'This message will display in the description of any reattempt assignment activities.';
$string['assignmentmessagesettings'] = 'Assignment messages settings';
$string['assignmentmessagesettings_desc'] = 'Messages relating to Summative assignments to help guide processes.';
$string['assignmentsettings'] = 'Assignment settings';
$string['assignmentsettings_desc'] = 'Settings to help manage assignment creation';
$string['assignmentsettingserrorsubject'] = 'Summative assignment settings for {$a->idnumber}';
$string['assignmenttitle'] = 'Assignment title';
$string['assignmentwarning_body'] = 'Assignment warning body';
$string['assignmentwarning_body_desc'] = 'Body of the assignment warning email sent to Module leaders. Any appropriate warnings will be appended.';
$string['assignmentwarning_hidden'] = 'Hidden warning';
$string['assignmentwarning_hidden_desc'] = 'This message will display to all with grading permissions, and will form part of an email when settings are changed';
$string['assignmentwarning_wrongsection'] = 'Wrong section warning';
$string['assignmentwarning_wrongsection_desc'] = 'This message will display to all with grading permissions, and will form part of an email when settings are changed';
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
$string['defaultscale'] = 'Default scale';
$string['defaultscale_desc'] = 'Scale to use when none specified. When empty, assignments will be created with a scale based on grademark exempt flag.';
$string['deletedtemplate'] = '"{$a}" has been deleted.';
$string['deleteduser'] = 'Deleted user';
$string['duedate'] = 'Due date';
$string['duplicatepagetypesession'] = 'A template already exists for this session and page-type combination';

$string['editsoltemplate'] = 'Edit Template';
$string['enabled'] = 'Enabled';
$string['enddate'] = 'End date';
$string['error:courseedited'] = 'Course has been edited. Cannot apply template. {$a}';
$string['error:courseiddoesnotmatch'] = 'Given courseid doesn\'t match the one on record';
$string['error:coursenotexist'] = 'Course specified doesn\'t exist: {$a}';
$string['error:coursevisible'] = 'Course visible. Cannot apply template. {$a}';
$string['error:invalidpagetype'] = 'Invalid pagetype: {$a}';
$string['error:sitsrefinuse'] = 'SITS Reference already in use: {$a}';
$string['error:sitsrefnotexist'] = 'SITS reference doesn\'t exist: {$a}';
$string['error:usersenrolledalready'] = 'Enrolments already exist. Cannot apply template. {$a}';
$string['exportgradestask'] = 'Export grades to SITS';
$string['externaldate'] = 'External date';

$string['failure'] = 'Failure';
$string['filter'] = 'Filter';
$string['filterassignments'] = 'Filter assignments';

$string['gatewaysits'] = 'Gateway (SITS)';
$string['generalsettings'] = 'General settings';
$string['getnewgradestask'] = 'Get newly released grades';
$string['grademark'] = 'Grademark';
$string['grademarkexempt'] = 'Grademark exempt';
$string['grademarkexemptscale'] = 'Grademark exempt scale';
$string['grademarkexemptscale_desc'] = 'Standard 100 point scale';
$string['grademarkscale'] = 'Grademark scale';
$string['grademarkscale_desc'] = 'Solent Grade scale A1, A2, A3, B1 etc';
$string['gradenotqueued'] = 'Not Queued - Course: {$a->course}, Assignment code: {$a->sitsref}, Grader id: {$a->graderid}, Grade: {$a->grade}, Student idnumber: {$a->studentidnumber}';
$string['gradequeued'] = 'Queued - Course: {$a->course}, Assignment code: {$a->sitsref}, Grader id: {$a->graderid}, Grade: {$a->grade}, Student idnumber: {$a->studentidnumber}';
$string['graderidnotexists'] = 'Graderid user does not exist';
$string['gradernotset'] = 'Graderid not set';
$string['gradeslocked'] = '<strong>Grades for this assignment have been released and locked.</strong>';
$string['gradingdueinterval'] = 'Grading due interval';
$string['gradingdueinterval_desc'] = 'Grading due date interval in weeks';
$string['gradingdueintervalsecondplus'] = 'Reattempt Grading due interval';
$string['gradingdueintervalsecondplus_desc'] = 'Grading due date for reattempts interval in weeks';

$string['immediately'] = 'Immediately';
$string['info'] = 'Info';
$string['invalidcourseid'] = 'Invalid courseid';
$string['invalidpagetype'] = 'Invalid pagetype';
$string['invalidsession'] = 'Invalid session';
$string['invalidstudent'] = 'Student IDNumber invalid of {$a->idnumber} for {$a->firstname} {$a->lastname}';
$string['invalidtemplateid'] = 'Invalid template id';

$string['keynotset'] = 'AIS key not set';

$string['lastmodified'] = 'Last modified';
$string['limittoyears'] = 'Limit assignment creation';
$string['limittoyears_desc'] = 'Limit assignment creation to these selected years (multiselect). If none are selected, ALL years will be processed.';

$string['manageassignments'] = 'Manage assignments';
$string['managetemplates'] = 'Manage templates';
$string['marksuploads_batchgrades'] = 'Batch grades';
$string['marksuploads_batchgrades_desc'] = 'Marks uploads is very slow. To prevent timeouts, batch the grades by this amount for each assignment.';
$string['marksuploads_endpoint'] = 'Export grades endpoint';
$string['marksuploads_endpoint_desc'] = 'The path for the export grades function. Start with /';
$string['marksuploads_key'] = 'API key';
$string['marksuploads_key_desc'] = 'API key provided by integration team';
$string['marksuploads_maxassignments'] = 'Max assignments';
$string['marksuploads_maxassignments_desc'] = 'Maximum number of assignments to process in each running of the task';
$string['marksuploads_url'] = 'AIS Base url';
$string['marksuploads_url_desc'] = 'Base url for Moodle -> AIS. Can be used for multiple functions.';
$string['marksuploadssettings'] = 'Marks uploads settings';
$string['maxassignments'] = 'Max assignments';
$string['maxassignments_desc'] = 'Maximum number of assignments to create in a single run.';
$string['maxtemplates'] = 'Max templates';
$string['maxtemplates_desc'] = 'Maximum number of courses to apply templates to in a single run.';
$string['modifiedby'] = 'Modified by';
$string['muptimeoutmessage'] = 'Check with Registry. The results may have been successfully uploaded.';

$string['newsavedtemplate'] = 'New template saved';
$string['newsoltemplate'] = 'New template';
$string['noboard'] = '<p>No board date is available (grades cannot be released). Please contact student.registry@solent.ac.uk.</p>';
$string['nodefaultscale'] = 'No default scale';
$string['nolongerexists'] = 'No longer exists';
$string['noselection'] = 'No selection';
$string['notemplate'] = 'No template';
$string['notenabled'] = 'Not enabled';
$string['notset'] = 'Not set';
$string['notsubmitted'] = 'Not submitted';
$string['notvisible'] = 'Not visible';
$string['numericscale'] = 'Numeric scale';
$string['numericscale_desc'] = 'Numeric (1-100) grade scale to be used for all future assignments';

$string['pagetype'] = 'Page type';
$string['pagetype_help'] = 'Is this template for a Course or a Module';
$string['passfail'] = 'Pass/Fail';
$string['passfailscale'] = 'Pass/Fail scale';
$string['pluginname'] = 'SOL-SITS Integration';
$string['poft'] = '{$a->page} of {$a->total}';
$string['points'] = 'Points';

$string['quercus'] = 'Quercus';
$string['quercusassignmentonsitscourse'] = 'The Quercus assignment ({$a->assignidnumber}) should not be on the Gateway module ({$a->courseidnumber}). Please contact Guided.Learning@solent.ac.uk to delete it.';

$string['reattempt'] = 'Re-attempt';
$string['reattempt0'] = 'First attempt';
$string['reattempt1'] = 'First reattempt';
$string['reattempt2'] = 'Second reattempt';
$string['reattempt3'] = 'Third reattempt';
$string['reattempt4'] = 'Fourth reattempt';
$string['reattempt5'] = 'Fifth reattempt';
$string['reattempt6'] = 'Sixth reattempt';
$string['recreate'] = 'Recreate assignment activity';
$string['releasedate'] = 'Grades cannot be released until {$a->date} ({$a->days} days after the board has passed).';

$string['scale'] = 'Scale';
$string['selectapagetype'] = 'Select a pagetype';
$string['selectascale'] = 'Select a scale';
$string['selectasession'] = 'Select a session';
$string['selectatemplate'] = 'Select a template';
$string['selectcourses'] = 'Select courses';
$string['sequence'] = 'Sequence';
$string['session'] = 'Academic session';
$string['shortname'] = 'Shortname';
$string['showerrorsonly'] = 'Show errors only';
$string['showerrorsonly_help'] = 'The following errors are identified:<ol><li>Broken or no Assignment link</li>
    <li>No duedate</li>
    <li>Invisible course or activity</li>
    </ol>';
$string['sits'] = 'SITS';
$string['sitsassign:cannotdelete'] = 'Cannot delete SITS assignment {$a}';
$string['sitsassign:cannotrecreate'] = 'Cannot recreate SITS assignment {$a} without first deleting the activity.';
$string['sitsassign:confirmdelete'] = 'Confirm deletion of {$a}';
$string['sitsassign:confirmdeletebody'] = 'Confirm deletion of {$a}.<br />Note: This only deletes the sitsassign record, the activity will remain, and will need to be manually deleted, if it hasn\'t already.';
$string['sitsassign:confirmrecreate'] = 'Confirm recreation of {$a}';
$string['sitsassign:confirmrecreatebody'] = 'Confirm recreation of {$a}.<br />Note: This resets the course_module id to zero so that the assignment creation task can reprocess it. It will take a few minutes.';
$string['sitsassign:deleted'] = 'SITS assignment {$a} has been deleted.';
$string['sitsassign:recreate'] = 'Recreate';
$string['sitsassign:recreated'] = 'SITS assignment {$a} has been queued to be recreated. It will take a few minutes to process.';
$string['sitsdatadesc'] = '<div class="alert alert-info">These are the data received from SITS and are used for diagnostic purposes only.</div>';
$string['sitsreattempt'] = 'Re-attempt';
$string['sitsreference'] = 'SITS reference';
$string['sittingdescription'] = 'Sitting description';
$string['sittingreference'] = 'Sitting reference';
$string['solassignmentidnotexists'] = 'SOL assignment ID does not exist';
$string['solassignmentidnotset'] = 'SOL assignment ID not set';
$string['solassignments:manage'] = 'Manage SITS Assignments';
$string['solsits:manageassignments'] = 'Manage SITS assignments';
$string['solsits:managetemplates'] = 'Manage Templates';
$string['solsits:registersitscourse'] = 'Register SITS course';
$string['solsits:releasegrades'] = 'Release grades';
$string['solsits:submissionsselectusers'] = 'Allow user to bulk select individual submissions';
$string['startdate'] = 'Start date';
$string['status'] = 'Status';
$string['studentidnotexists'] = 'Studentid user does not exist';
$string['studentidnotset'] = 'Studentid not set';
$string['success'] = 'Success';

$string['targetsection'] = 'Assignment section';
$string['targetsection_desc'] = 'Which section No. should assignments be created in?';
$string['templateapplied'] = 'Template {$a->templatekey} has been applied to {$a->courseidnumber}';
$string['templatecat'] = 'Template category';
$string['templatecat_desc'] = 'The source of all template files';
$string['templatecourse'] = 'Template course';
$string['templatename'] = 'Template name';
$string['templatequeue'] = 'Template queue';
$string['templatequeuehelp'] = '<p>This will display all the modules or courses that have not yet had a template applied.
    The info column will provide a reason why the template has not been applied. If there is no reason, then it is just
    waiting its turn.</p>
    <p>The following reasons will <em>prevent</em> the template being applied:</p>
    <ul>
        <li>The template has not been created yet (Matches Pagetype and Session)</li>
        <li>The Template has not been enabled</li>
        <li>The course or module is already visible (it is hidden when created)</li>
        <li>The course or module already has content (Other than a forum)</li>
        <li>The course or module already has users enrolled</li>
    </ul>
    <p>The "Allocated template" column will tell you which template will be used, when it can be applied.
    If this says "No template", then one doesn\'t exist.</p>';
$string['templates'] = 'Templates';
$string['templates_desc'] = 'Module and Course templates assigned to sessions';
$string['timecreated'] = 'Time created';
$string['timemodified'] = 'Time modified';

$string['updatedtemplate'] = '"{$a}" has been updated.';
$string['urlnotset'] = 'AIS client url not set';

$string['visibility'] = 'Visibility';
$string['visible'] = 'Visible';

$string['weighting'] = 'Weighting';
$string['wrongassignmentonwrongcourse'] = 'This assignment ({$a->assignidnumber}) should not be on {$a->courseidnumber}, please contact Guided.Learning@solent.ac.uk to remove it.';
