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

defined('MOODLE_INTERNAL') || die();

$parent = new admin_category('local_solsitscat', new lang_string('pluginname', 'local_solsits'));
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

// Get available site scales.
$scaleoptions = get_scales_menu();
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

$ADMIN->add('local_solsitscat', $settings);

$name = 'local_solsits/managetemplates';
$title = new lang_string('managetemplates', 'local_solsits');
$url = new moodle_url('/local/solsits/managetemplates.php');
$externalpage = new admin_externalpage($name, $title, $url);

$ADMIN->add('local_solsitscat', $externalpage);
