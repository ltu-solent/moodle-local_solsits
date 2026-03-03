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
 * TODO describe file marksuploads
 *
 * @package    local_solsits
 * @copyright  2026 Southampton Solent University {@link https://www.solent.ac.uk}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core\lang_string;
use core\output\html_writer;

defined('MOODLE_INTERNAL') || die();

$page = new admin_settingpage('local_solsits_marksuploads', new lang_string('marksuploadssettings', 'local_solsits'));

$page->add(
    new admin_setting_heading(
        'local_solsits/marksuploadssettings',
        new lang_string('marksuploadssettings', 'local_solsits'),
        new lang_string('marksuploadssettings_desc', 'local_solsits')
    )
);

$page->add(
    new admin_setting_configtext(
        'local_solsits/ais_exportgrades_url',
        new lang_string('marksuploads_url', 'local_solsits'),
        new lang_string('marksuploads_url_desc', 'local_solsits'),
        '',
        PARAM_URL
    )
);

$page->add(
    new admin_setting_configtext(
        'local_solsits/ais_exportgrades_endpoint',
        new lang_string('marksuploads_endpoint', 'local_solsits'),
        new lang_string('marksuploads_endpoint_desc', 'local_solsits'),
        '',
        PARAM_PATH
    )
);

$page->add(
    new admin_setting_configpasswordunmask(
        'local_solsits/ais_exportgrades_key',
        new lang_string('marksuploads_key', 'local_solsits'),
        new lang_string('marksuploads_key_desc', 'local_solsits'),
        ''
    )
);

$page->add(
    new admin_setting_description(
        'local_solsits/ais_testconnection',
        '',
        html_writer::tag(
            'p',
            html_writer::tag(
                'button',
                new lang_string('ais_testconnection', 'local_solsits'),
                [
                    'class' => 'btn btn-primary',
                    'id' => 'ais_testconnection',
                ]
            ) . html_writer::tag('span', '', ['id' => 'ais_testconnection_response', 'class' => 'pl-1'])
        )
    )
);
$PAGE->requires->js_call_amd('local_solsits/ais-testconnection', 'init');

$page->add(
    new admin_setting_configselect(
        'local_solsits/marksuploads_maxassignments',
        new lang_string('marksuploads_maxassignments', 'local_solsits'),
        new lang_string('marksuploads_maxassignments_desc', 'local_solsits'),
        1,
        $onetotenoptions
    )
);

$options = [
    '-1' => get_string('allgrades', 'local_solsits'),
    '25' => '25',
    '50' => '50',
    '75' => '75',
    '100' => '100',
    '125' => '125',
    '150' => '150',
];
$page->add(
    new admin_setting_configselect(
        'local_solsits/marksuploads_batchgrades',
        new lang_string('marksuploads_batchgrades', 'local_solsits'),
        new lang_string('marksuploads_batchgrades_desc', 'local_solsits'),
        '-1',
        $options
    )
);

$settings->add($page);
