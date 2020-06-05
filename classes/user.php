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
 * Timeline Social course format.
 *
 * @package    format_timeline
 * @copyright  2020 onwards Willian Mano {@link http://conecti.me}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_timeline;

use user_picture;

class user {
    public static function get_userinfo($user, $page) {
        $userimg = new user_picture($user);

        $userimg->size = true;

        return [
            'img' => $userimg->get_url($page),
            'fullname' => $user->firstname . ' ' . $user->lastname
        ];
    }

    public static function get_userpic($user, $page) {
        $userimg = new user_picture($user);

        $userimg->size = true;

        return $userimg->get_url($page);
    }

    public static function can_add_post($context, $user = null) {
        return has_capability('moodle/course:update', $context, $user);
    }

    public static function can_delete_post($post, $user = null) {
        global $USER;

        if (!$user) {
            $user = $USER;
        }

        if ($post->userid == $user->id) {
            return true;
        }

        if (is_siteadmin($user)) {
            return true;
        }

        return false;
    }

    public static function can_comment_on_post($context, $user = null) {
        return is_enrolled($context, $user) || is_siteadmin($user);
    }
}