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
$string['addavailabilitytoreattempt'] = 'Add group availability to reattempt';
$string['addavailabilitytoreattempt_desc'] = 'This is linked to the "create reattempt groups" setting, and will explicitly add a
    restriction to the reattempt. Warning: Only add this if the tutors can easily know who should take reattempts.';
$string['additionalinformation'] = 'Additional information';
$string['ais_testconnection'] = 'Test connection';
$string['allgrades'] = 'All grades';
$string['allocatedtemplate'] = 'Allocated template';
$string['applytemplatetask'] = 'Apply template';
$string['assessmentcode'] = 'Assessment code';
$string['assessmentname'] = 'Assessment name';
$string['assignmentbrief'] = 'Assignment brief:';
$string['assignmentbrief_help'] = 'Add this reference to the idnumber of a file resource to automatically link to assignment brief in the assignment';
$string['assignmentconfigwarning_body'] = 'Assignment config warning message body';
$string['assignmentconfigwarning_body_desc'] = 'Message sent to Module leaders and extra recipients at weekly intervals leading up to the due date';
$string['assignmentconfigwarning_mailinglist'] = 'Assignment config warning mailing list';
$string['assignmentconfigwarning_mailinglist_desc'] = 'List of usernames (comma separated) of those who are to receive an assignment config warning in addition to the Module leaders';
$string['assignmentconfigwarning_ranges'] = 'Message date ranges';
$string['assignmentconfigwarning_ranges_desc'] = 'The date ranges for sending config warning messages.';

$string['assignmentduedatechange_body'] = 'Due date change body';
$string['assignmentduedatechange_body_desc'] = 'The message that will be sent to Assessments to change the due date.';
$string['assignmentduedatechange_description'] = '<p>A message will be sent to the Assessments team to update the due date for "<strong>{$a->title}</strong>" with the date you select below.</p>
  <p>The date will not be updated in Gateway automatically.</p>
  <p>A copy will be sent to you when the message has been sent (it may take a few minutes to be sent).</p>';
$string['assignmentduedatechange_mailinglist'] = 'Due date change mailing list';
$string['assignmentduedatechange_mailinglist_desc'] = 'List of usernames (comma separated) of those who are to receive request to update the due dates';
$string['assignmentduedatechange_reasons'] = 'Reasons to change due date';
$string['assignmentduedatechange_reasons_desc'] = 'List of valid reasons to change the due date';

$string['assignmentduedatechange_subject'] = 'Due date change for {$a}';

$string['assignmentmessage_marksuploadinclude'] = 'Marks upload message';
$string['assignmentmessage_marksuploadinclude_desc'] = 'Message appears on grading page to remind marks about the Marks upload process.';
$string['assignmentmessage_reattempt'] = 'Reattempt message';
$string['assignmentmessage_reattempt_desc'] = 'Message appears on the grading page when this is a reattempt, to warn about early release.';
$string['assignmentmessage_studentreattempt'] = 'Reattempt message for students';
$string['assignmentmessage_studentreattempt_desc'] = 'This message will display in the description of any reattempt assignment activities.';
$string['assignmentmessagesettings'] = 'Assignment messages settings';
$string['assignmentmessagesettings_desc'] = 'Messages relating to Summative assignments to help guide processes.';
$string['assignmentnotvisible'] = 'Assignment is not visible to students';
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
$string['availabilityconditions'] = 'Availability conditions have been set. Please make sure those students who need to submit have access. e.g. Group membership.';
$string['availablefrom'] = 'Available from';

$string['checkcoursedeleted'] = 'Check: This template no longer exists';
$string['checkduedate'] = 'Submission due date is <strong>{$a->duedate}</strong>. <a href="#"
 data-action="sol-new-duedate"
 data-sitsref="{$a->sitsref}"
 data-cmid="{$a->cmid}"
 data-title="{$a->title}"
 data-duedate="{$a->duedatetimestamp}">Inform Assessments team of new date</a>, if incorrect.';
$string['checkduedatenomessage'] = 'Submission due date is <strong>{$a->duedate}</strong>. Is this correct?';
$string['cmid'] = 'Course module ID';
$string['confirmdeletetemplate'] = 'Confirm deletion of "{$a}"';
$string['courseidrequired'] = 'Courseid field required';
$string['coursename'] = 'Course name';
$string['createassignmenttask'] = 'Create assignment task';
$string['createreattemptgroups'] = 'Create reattempt groups';
$string['createreattemptgroups_desc'] = 'Create hidden groups when a reattempt assignment is created. This can be used to restrict
    visibility of reattempts to only those students who need to take the reattempt.';
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
$string['duedatethankyou'] = 'Thank you. Your request for a change to the due date has been sent.';
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
$string['existingduedate'] = 'Existing due date';
$string['exportgradestask'] = 'Export grades to SITS';
$string['externaldate'] = 'External date';

$string['failedtoqueue'] = 'We\'ve been unable to send your request - this may be because there\'s a pending request.';
$string['failure'] = 'Failure';
$string['filter'] = 'Filter';
$string['filterassignments'] = 'Filter assignments';
$string['forinformation'] = '[For information] ';

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
$string['gradingdueby'] = 'Grading due by {$a}';
$string['gradingdueinterval'] = 'Grading due interval';
$string['gradingdueinterval_desc'] = 'Grading due date interval in weeks';
$string['gradingdueintervalsecondplus'] = 'Reattempt Grading due interval';
$string['gradingdueintervalsecondplus_desc'] = 'Grading due date for reattempts interval in weeks';

$string['immediately'] = 'Immediately';
$string['importantdates'] = 'Important dates for your calendar';
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
$string['module'] = 'Module';
$string['muptimeoutmessage'] = 'Check with Registry. The results may have been successfully uploaded.';

$string['newduedate'] = 'New due date';
$string['newsavedtemplate'] = 'New template saved';
$string['newsoltemplate'] = 'New template';
$string['noassignmentsyet'] = 'No assignments yet';
$string['noboard'] = '<p>No board date is available (grades cannot be released). Please contact student.registry@solent.ac.uk.</p>';
$string['nodefaultscale'] = 'No default scale';
$string['noduedate'] = 'No due date';
$string['nolongerexists'] = 'No longer exists';
$string['noselection'] = 'No selection';
$string['nosubmissionplugins'] = 'No Submission types have been selected. Students will not be able to submit work.';
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
$string['pointgrademarkmapping'] = 'Grade';
$string['pointgrademarkmapping_help'] = 'Enter the grade for the student\'s submission here.

For summative assignments, the following points map to the Solent Grademark scale:<br />
100 -> A1<br />
 92 -> A2<br />
 83 -> A3<br />
 74 -> A4<br />
 68 -> B1<br />
 65 -> B2<br />
 62 -> B3<br />
 58 -> C1<br />
 55 -> C2<br />
 52 -> C3<br />
 48 -> D1<br />
 45 -> D2<br />
 42 -> D3<br />
 35 -> F1<br />
 20 -> F2<br />
 15 -> F3<br />
  2 -> S<br />
  0 -> N<br />

For Pass/Fail assessments, use:<br />
42 -> Pass<br />
35 -> Fail<br />
';
$string['points'] = 'Points';

$string['quercus'] = 'Quercus';
$string['quercusassignmentonsitscourse'] = 'The Quercus assignment ({$a->assignidnumber}) should not be on the Gateway module ({$a->courseidnumber}). Please contact Guided.Learning@solent.ac.uk to delete it.';

$string['r0-1weeks'] = 'The following assignments are due within 1 week';
$string['rangedates'] = 'Unconfigured assignments due between {$a->start} and {$a->end}';
$string['rangeweeks'] = '{$a->start} to {$a->end} weeks';
$string['reason'] = 'Reason';
$string['reattempt'] = 'Re-attempt';
$string['reattempt0'] = 'First attempt';
$string['reattempt1'] = 'First reattempt';
$string['reattempt2'] = 'Second reattempt';
$string['reattempt3'] = 'Third reattempt';
$string['reattempt4'] = 'Fourth reattempt';
$string['reattempt5'] = 'Fifth reattempt';
$string['reattempt6'] = 'Sixth reattempt';
$string['reattemptavailability'] = 'Ensure required students are a member of the "{$a}" group';
$string['recreate'] = 'Recreate assignment activity';
$string['releasedate'] = 'Grades cannot be released until {$a->date} ({$a->days} days after the board has passed).';

$string['samedates'] = 'Your requested has not been sent because they\'re for the same dates';
$string['scale'] = 'Scale';
$string['selectapagetype'] = 'Select a pagetype';
$string['selectareason'] = 'Select a reason';
$string['selectascale'] = 'Select a scale';
$string['selectasession'] = 'Select a session';
$string['selectatemplate'] = 'Select a template';
$string['selectcourses'] = 'Select courses';
$string['send_assign_config_errors_messsage_task'] = 'Send assignment configuration error message digest';
$string['sendmessage'] = 'Send message';
$string['sequence'] = 'Sequence';
$string['session'] = 'Academic session';
$string['shortcode:assignmentintro'] = 'Displays dynamic information about an assignment that is displayed to students.';
$string['shortcode:summativeassignments'] = 'Summative assignments for course context';
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
$string['sitsassign:deletedreport'] = 'SITS assignment {$a} has been deleted. Please report this to the Module Leader or to Registry.';
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
$string['submissiondue'] = '<strong>Submission due on or before:</strong> {$a}';
$string['submissiondueandsubmitted'] = 'Submission due on {$a->duedate} and submitted on {$a->submissiondate}';
$string['submissionsenabled'] = 'Submissions enabled';
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
$string['tutorinfo'] = 'Tutor information';

$string['updatedtemplate'] = '"{$a}" has been updated.';
$string['updateduedate'] = 'Update due date for "{$a->title}"';
$string['urlnotset'] = 'AIS client url not set';

$string['visibility'] = 'Visibility';
$string['visible'] = 'Visible';

$string['weighting'] = 'Weighting';
$string['wrongassignmentonwrongcourse'] = 'This assignment ({$a->assignidnumber}) should not be on {$a->courseidnumber}, please contact Guided.Learning@solent.ac.uk to remove it.';
