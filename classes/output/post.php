<?php

namespace format_timeline\output;

use format_timeline\local\posts;
use format_timeline\local\user;
use templatable;
use renderable;
use renderer_base;

class post implements templatable, renderable {
    protected $post;
    protected $page;

    public function __construct($post, $page) {
        $this->post = $post;
        $this->page = $page;
    }

    /**
     * Export renderer
     *
     * @param renderer_base $output
     *
     * @return array|\stdClass
     */
    public function export_for_template(renderer_base $output) {
        global $USER, $DB;

        $user = $DB->get_record('user', ['id' => $this->post->userid]);

        // User image who created the post.
        $this->post->userpic = user::get_userpic($user, $this->page);

        // Current loggedin user image.
        $loggedinuserinfo = $USER;
        $userinfos = user::get_userinfo($loggedinuserinfo, $this->page);
        $loggedinuserinfo->img = $userinfos['img'];
        $loggedinuserinfo->fullname = $userinfos['fullname'];

        $this->post->loggedinuserinfo = $loggedinuserinfo;

        // Human readable time created.
        $this->post->humantimecreated = userdate($this->post->timecreated);

        // Get the user fullname.
        $this->post->fullname = fullname($user);

        if ($this->post->groupid) {
            $group = $DB->get_record('groups', ['id' => $this->post->groupid]);

            $this->post->groupname = $group->name;
        }

        $children = posts::get_post_children($this->post->id, false, $this->page);

        if ($children) {
            $this->post->children = $children;
        }

        return $this->post;
    }
}