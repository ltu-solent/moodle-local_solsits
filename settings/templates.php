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
 * TODO describe file templates
 *
 * @package    local_solsits
 * @copyright  2026 Southampton Solent University {@link https://www.solent.ac.uk}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core\lang_string;

defined('MOODLE_INTERNAL') || die();

$page = new admin_settingpage('local_solsits_templates', new lang_string('templates', 'local_solsits'));

$page->add(
    new admin_setting_heading(
        'local_solsits/templates',
        new lang_string('templates', 'local_solsits'),
        new lang_string('templates_desc', 'local_solsits')
    )
);

$options = core_course_category::make_categories_list('moodle/category:manage');
$page->add(
    new admin_setting_configselect(
        'local_solsits/templatecat',
        new lang_string('templatecat', 'local_solsits'),
        new lang_string('templatecat_desc', 'local_solsits'),
        '',
        $options
    )
);

$options = array_combine(range(1, 30), range(1, 30));
$page->add(
    new admin_setting_configselect(
        'local_solsits/maxtemplates',
        new lang_string('maxtemplates', 'local_solsits'),
        new lang_string('maxtemplates_desc', 'local_solsits'),
        5,
        $options
    )
);

$settings->add($page);
