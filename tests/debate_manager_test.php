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

namespace mod_videodebate;

/**
 * Tests for debate utilities.
 *
 * @package mod_videodebate
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class debate_manager_test extends \advanced_testcase {
    /**
     * Method test_positions_are_unique_and_stable.
     *
     * @return void Return value.
     */
    public function test_positions_are_unique_and_stable(): void {
        $positions = debate_manager::positions_from_text("Agree\nDisagree\nAgree\nPartially");
        $this->assertCount(3, $positions);
        $this->assertSame(['Agree', 'Disagree', 'Partially'], array_values($positions));
    }

    /**
     * Method test_timecode_supports_hours.
     *
     * @return void Return value.
     */
    public function test_timecode_supports_hours(): void {
        $this->assertSame('05:07', debate_manager::format_timecode(307));
        $this->assertSame('01:02:03', debate_manager::format_timecode(3723));
    }

    /**
     * Method test_evidence_json_is_normalised.
     *
     * @return void Return value.
     */
    public function test_evidence_json_is_normalised(): void {
        $items = debate_manager::parse_evidence_json('[{"start":12.5,"end":10,"label":"Example"}]');
        $this->assertCount(1, $items);
        $this->assertEquals(12.5, $items[0]['start']);
        $this->assertEquals(12.5, $items[0]['end']);
        $this->assertSame('Example', $items[0]['label']);
    }
}
