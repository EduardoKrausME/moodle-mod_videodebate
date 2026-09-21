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
 * Tests for watched segment merging.
 *
 * @package mod_videodebate
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class tracking_manager_test extends \advanced_testcase {
    /**
     * Method test_merge_segments_merges_overlaps_and_clamps_duration.
     *
     * @return void Return value.
     */
    public function test_merge_segments_merges_overlaps_and_clamps_duration(): void {
        $manager = new tracking_manager();
        $segments = $manager->merge_segments([[0, 5], [4.8, 10], [20, 25], [99, 105]], 100);
        $this->assertEquals([[0.0, 10.0], [20.0, 25.0], [99.0, 100.0]], $segments);
    }

    /**
     * Method test_merge_segments_discards_invalid_ranges.
     *
     * @return void Return value.
     */
    public function test_merge_segments_discards_invalid_ranges(): void {
        $manager = new tracking_manager();
        $this->assertEquals([[3.0, 8.0]], $manager->merge_segments([[5, 5], ['x'], [3, 8]], 0));
    }
}
