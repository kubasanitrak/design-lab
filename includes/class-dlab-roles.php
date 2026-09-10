<?php
/**
 * Member role and capabilities.
 */

if (!defined('ABSPATH')) {
    exit;
}

class DLab_Roles {

    const MEMBER_ROLE = 'dlab_member';

    public function __construct() {
        add_action('init', array($this, 'ensure_role'), 1);
    }

    public static function register_role() {
        if (get_role(self::MEMBER_ROLE)) {
            return;
        }

        add_role(
            self::MEMBER_ROLE,
            __('Člen (Design Lab)', 'design-lab'),
            array(
                'read' => true,
            )
        );
    }

    public function ensure_role() {
        self::register_role();
    }

    public static function assign_member_role($user_id) {
        $user = get_userdata($user_id);
        if (!$user) {
            return;
        }
        $user->set_role(self::MEMBER_ROLE);
    }
}
