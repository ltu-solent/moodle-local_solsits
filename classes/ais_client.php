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
 * AIS API connection
 *
 * @package   local_solsits
 * @author    Mark Sharp <mark.sharp@solent.ac.uk>
 * @copyright 2023 Solent University {@link https://www.solent.ac.uk}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_solsits;

use curl;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/filelib.php');

/**
 * AIS client to be used for communicating with AIS
 */
class ais_client extends curl {
    /**
     * Endpoint for this client.
     *
     * @var string
     */
    private $url;

    /**
     * Constructor setting url for this client.
     *
     * @param array $settings
     * @param string $url
     */
    public function __construct($settings = [], $url = '') {
        parent::__construct($settings);
        $this->url = $url;
    }
    /**
     * Export grades
     *
     * @param array $grades
     * @return string json
     */
    public function export_grades($grades) {
        $export = json_encode($grades);
        $this->setopt([
            'CURLOPT_RETURNTRANSFER' => 1, // Post will return false on error, or the response on success.
            'CURLOPT_FAILONERROR' => 1,
            // @codingStandardsIgnoreStart
            // 'CURLOPT_SSL_VERIFYHOST' => false,
            // 'CURLOPT_SSL_VERIFYPEER' => false
            // @codingStandardsIgnoreEnd
        ]);
        return $this->post($this->url, $export);
    }
}
