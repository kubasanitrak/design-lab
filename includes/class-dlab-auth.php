<?php
/**
 * Member registration (checkout), e-mail verification, set password, login redirects.
 */

if (!defined('ABSPATH')) {
    exit;
}

class DLab_Auth {

    const META_FIRST_NAME           = 'dlab_first_name';
    const META_LAST_NAME            = 'dlab_last_name';
    const META_PHONE                = 'dlab_phone';
    const META_GDPR_AT              = 'dlab_gdpr_at';
    const META_PENDING_VERIFICATION = 'dlab_pending_verification';
    const META_EMAIL_VERIFIED       = 'dlab_email_verified';
    const META_VERIFY_TOKEN_HASH    = 'dlab_verify_token_hash';
    const META_PASSWORD_KEY         = 'dlab_password_key';
    const META_PASSWORD_KEY_EXPIRES = 'dlab_password_key_expires';

    const ACTION_SET_PASSWORD = 'dlab_set_password';

    const QUERY_VERIFY = 'dlab_verify';
    const QUERY_UID    = 'dlab_uid';
    const QUERY_TOKEN  = 'dlab_token';

    const QUERY_SET_PASSWORD = 'dlab_set_password';
    const QUERY_SET_UID      = 'dlab_set_uid';
    const QUERY_SET_KEY      = 'dlab_set_key';

    public function __construct() {
        add_action('init', array($this, 'handle_verification_link'), 5);
        add_action('init', array($this, 'handle_form_posts'), 6);
        add_action('login_init', array($this, 'redirect_wp_register'));
        add_action('login_init', array($this, 'handle_logged_in_login_page'), 20);
        add_filter('login_redirect', array($this, 'filter_login_redirect'), 10, 3);
        add_filter('login_message', array($this, 'filter_login_message'));
        add_filter('authenticate', array($this, 'block_unverified_login'), 30, 3);
        add_shortcode('dlab_set_password', array($this, 'shortcode_set_password'));
        add_filter('dlab_enqueue_public_assets', array($this, 'enqueue_assets_flag'));
    }

    public function redirect_wp_register() {
        if (isset($_GET['action']) && 'register' === $_GET['action']) {
            $url = DLab_Settings::checkout_page_url();
            if ($url) {
                wp_safe_redirect($url);
                exit;
            }
        }
    }

    public function handle_logged_in_login_page() {
        if (!is_user_logged_in() || $this->is_wp_login_action_request()) {
            return;
        }

        $redirect = isset($_REQUEST['redirect_to']) ? esc_url_raw(wp_unslash($_REQUEST['redirect_to'])) : '';

        login_header(esc_html__('Přihlášení', 'design-lab'));

        if ($redirect && wp_validate_redirect($redirect, false)) {
            echo '<p class="dlab-auth-notice"><a href="' . esc_url($redirect) . '">' . esc_html__('Pokračovat', 'design-lab') . '</a></p>';
        } else {
            echo '<p class="dlab-auth-notice">' . esc_html__('Jste již přihlášeni.', 'design-lab') . '</p>';
        }

        login_footer();
        exit;
    }

    private function is_wp_login_action_request() {
        $action = isset($_REQUEST['action']) ? sanitize_text_field(wp_unslash($_REQUEST['action'])) : '';
        if ($action === '') {
            return false;
        }

        $passthrough = array('logout', 'lostpassword', 'retrievepassword', 'rp', 'resetpass', 'postpass', 'confirmaction');
        return in_array($action, $passthrough, true);
    }

    public function filter_login_redirect($redirect_to, $requested_redirect_to, $user) {
        if (!$user instanceof WP_User) {
            return $redirect_to;
        }

        $dashboard = self::get_page_url('dashboard') ?: home_url('/muj-ucet-design-lab/');

        if ($this->should_honor_login_redirect($requested_redirect_to)) {
            return $requested_redirect_to;
        }

        if (user_can($user, 'edit_posts')) {
            return $redirect_to;
        }

        return $dashboard;
    }

    private function should_honor_login_redirect($url) {
        if (!$url || !wp_validate_redirect($url, false)) {
            return false;
        }

        $normalized = untrailingslashit($url);
        $ignored    = array(
            untrailingslashit(home_url('/')),
            untrailingslashit(admin_url()),
            untrailingslashit(admin_url('profile.php')),
        );

        return !in_array($normalized, $ignored, true);
    }

    public function filter_login_message($message) {
        $codes = self::auth_message_codes();
        $extra = '';

        if (!empty($_GET['dlab_auth_success'])) {
            $code = sanitize_key(wp_unslash($_GET['dlab_auth_success']));
            if (isset($codes[$code])) {
                $extra .= '<p class="message">' . esc_html($codes[$code]) . '</p>';
            }
        }

        if (!empty($_GET['dlab_auth_error'])) {
            $code = sanitize_key(rawurldecode(wp_unslash($_GET['dlab_auth_error'])));
            $msg  = !empty($_GET['dlab_auth_message'])
                ? sanitize_text_field(rawurldecode(wp_unslash($_GET['dlab_auth_message'])))
                : (isset($codes[$code]) ? $codes[$code] : __('Došlo k chybě.', 'design-lab'));
            $extra .= '<div id="login_error">' . esc_html($msg) . '</div>';
        }

        return $message . $extra;
    }

    private static function auth_message_codes() {
        return array(
            'verification_sent'   => __('Účet byl vytvořen. Na e-mail jsme odeslali ověřovací odkaz — po kliknutí si nastavíte heslo.', 'design-lab'),
            'verified'            => __('E-mail byl ověřen. Nastavte si heslo níže.', 'design-lab'),
            'password_set'        => __('Heslo bylo uloženo. Nyní se můžete přihlásit.', 'design-lab'),
            'invalid_nonce'       => __('Platnost formuláře vypršela. Odešlete ho znovu.', 'design-lab'),
            'email'               => __('Zadejte platný e-mail.', 'design-lab'),
            'email_exists'        => __('Účet s tímto e-mailem již existuje. Přihlaste se.', 'design-lab'),
            'phone'               => __('Vyplňte prosím telefon.', 'design-lab'),
            'registration_failed' => __('Registraci se nepodařilo dokončit. Zkuste to prosím znovu.', 'design-lab'),
            'not_verified'        => __('Účet není ověřen. Zkontrolujte e-mail.', 'design-lab'),
            'invalid_token'       => __('Ověřovací odkaz je neplatný nebo vypršel.', 'design-lab'),
            'password_mismatch'   => __('Hesla se neshodují.', 'design-lab'),
            'weak_password'       => __('Heslo musí mít alespoň 8 znaků.', 'design-lab'),
        );
    }

    public function enqueue_assets_flag($load) {
        return $load || $this->current_page_has_auth_shortcode();
    }

    private function current_page_has_auth_shortcode() {
        $post = get_post();
        if (!$post || empty($post->post_content)) {
            return false;
        }
        return has_shortcode($post->post_content, 'dlab_set_password');
    }

    public static function get_page_url($key) {
        $ids = get_option('dlab_page_ids', array());
        if (!empty($ids[$key])) {
            $url = get_permalink((int) $ids[$key]);
            if ($url) {
                return $url;
            }
        }
        return '';
    }

    /**
     * @param string $redirect_to Optional validated redirect target.
     * @param array  $extra_args  Query args.
     */
    public static function build_wp_login_url($redirect_to = '', $extra_args = array()) {
        $validated = ($redirect_to && wp_validate_redirect($redirect_to, false)) ? $redirect_to : '';
        $url       = wp_login_url($validated);

        if (!empty($extra_args)) {
            $url = add_query_arg($extra_args, $url);
        }

        return $url;
    }

    public static function generate_username($email) {
        $base = sanitize_user(current(explode('@', $email)), true);

        if ($base === '') {
            $base = 'user';
        }

        $username = $base;
        $suffix   = 1;

        while (username_exists($username)) {
            $username = $base . $suffix;
            ++$suffix;
        }

        return $username;
    }

    public static function is_email_verified($user_id) {
        return (bool) get_user_meta($user_id, self::META_EMAIL_VERIFIED, true);
    }

    public static function is_pending_verification($user_id) {
        return (bool) get_user_meta($user_id, self::META_PENDING_VERIFICATION, true);
    }

    public function handle_verification_link() {
        if (empty($_GET[self::QUERY_VERIFY])) {
            return;
        }

        $uid   = isset($_GET[self::QUERY_UID]) ? (int) $_GET[self::QUERY_UID] : 0;
        $token = isset($_GET[self::QUERY_TOKEN]) ? sanitize_text_field(wp_unslash($_GET[self::QUERY_TOKEN])) : '';

        if (!$uid || $token === '') {
            return;
        }

        $result   = self::verify_email($uid, $token);
        $redirect = self::get_page_url('set_password');

        if (!$redirect) {
            $redirect = home_url('/');
        }

        if (is_wp_error($result)) {
            $redirect = self::build_wp_login_url('', array(
                'dlab_auth_error' => $result->get_error_code(),
            ));
        } else {
            $redirect = add_query_arg(array(
                self::QUERY_SET_PASSWORD => '1',
                self::QUERY_SET_UID      => $uid,
                self::QUERY_SET_KEY      => rawurlencode($result['key']),
                'dlab_auth_success'      => 'verified',
            ), $redirect);
        }

        wp_safe_redirect($redirect);
        exit;
    }

    public function handle_form_posts() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['dlab_auth_action'])) {
            return;
        }

        $action = sanitize_key(wp_unslash($_POST['dlab_auth_action']));
        if ($action === self::ACTION_SET_PASSWORD) {
            $this->process_set_password_post();
        }
    }

    private function process_set_password_post() {
        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_wpnonce'])), self::ACTION_SET_PASSWORD)) {
            $this->redirect_set_password_error('invalid_nonce');
        }

        $uid   = isset($_POST['dlab_user_id']) ? (int) $_POST['dlab_user_id'] : 0;
        $key   = isset($_POST['dlab_password_key']) ? sanitize_text_field(wp_unslash($_POST['dlab_password_key'])) : '';
        $pass  = isset($_POST['dlab_password']) ? (string) $_POST['dlab_password'] : '';
        $pass2 = isset($_POST['dlab_password_confirm']) ? (string) $_POST['dlab_password_confirm'] : '';

        $result = self::set_member_password($uid, $key, $pass, $pass2);

        if (is_wp_error($result)) {
            $this->redirect_set_password_error($result->get_error_code(), $result->get_error_message(), $uid, $key);
        }

        wp_set_auth_cookie($uid, true);
        wp_set_current_user($uid);

        $redirect = self::get_page_url('dashboard');
        if (!$redirect) {
            $redirect = home_url('/');
        }
        wp_safe_redirect($redirect);
        exit;
    }

    private function redirect_set_password_error($code, $message = '', $uid = 0, $key = '') {
        $url  = self::get_page_url('set_password') ?: home_url('/');
        $args = array(
            'dlab_auth_error' => rawurlencode($code),
        );
        if ($message) {
            $args['dlab_auth_message'] = rawurlencode($message);
        }
        if ($uid && $key !== '') {
            $args[self::QUERY_SET_PASSWORD] = '1';
            $args[self::QUERY_SET_UID]      = $uid;
            $args[self::QUERY_SET_KEY]      = rawurlencode($key);
        }
        wp_safe_redirect(add_query_arg($args, $url));
        exit;
    }

    /**
     * Create member from checkout contact data (no password yet).
     *
     * @param array $contact {name, email, phone}
     * @param bool  $gdpr_accepted
     * @return int|WP_Error user ID
     */
    public static function register_member_from_contact(array $contact, $gdpr_accepted = true) {
        $name  = isset($contact['name']) ? sanitize_text_field($contact['name']) : '';
        $email = isset($contact['email']) ? sanitize_email($contact['email']) : '';
        $phone = isset($contact['phone']) ? sanitize_text_field($contact['phone']) : '';

        if ($email === '' || !is_email($email)) {
            return new WP_Error('email', __('Zadejte platný e-mail.', 'design-lab'));
        }
        if (email_exists($email)) {
            return new WP_Error('email_exists', __('Účet s tímto e-mailem již existuje. Přihlaste se.', 'design-lab'));
        }
        if ($phone === '') {
            return new WP_Error('phone', __('Vyplňte prosím telefon.', 'design-lab'));
        }
        if ($name === '') {
            return new WP_Error('name', __('Vyplňte jméno a příjmení.', 'design-lab'));
        }
        if (!$gdpr_accepted) {
            return new WP_Error('agreement', __('Musíte souhlasit se zpracováním osobních údajů.', 'design-lab'));
        }

        $parts = preg_split('/\s+/', trim($name), 2);
        $first = $parts[0];
        $last  = isset($parts[1]) ? $parts[1] : '';

        $login         = self::generate_username($email);
        $temp_password = wp_generate_password(24, true, true);
        $user_id       = wp_insert_user(array(
            'user_login'   => $login,
            'user_email'   => $email,
            'user_pass'    => $temp_password,
            'first_name'   => $first,
            'last_name'    => $last,
            'display_name' => $name,
            'role'         => DLab_Roles::MEMBER_ROLE,
        ));

        if (is_wp_error($user_id)) {
            return new WP_Error('registration_failed', __('Registraci se nepodařilo dokončit. Zkuste to prosím znovu.', 'design-lab'));
        }

        update_user_meta($user_id, self::META_FIRST_NAME, $first);
        update_user_meta($user_id, self::META_LAST_NAME, $last);
        update_user_meta($user_id, self::META_PHONE, $phone);
        update_user_meta($user_id, self::META_GDPR_AT, current_time('mysql'));
        update_user_meta($user_id, self::META_PENDING_VERIFICATION, '1');
        update_user_meta($user_id, self::META_EMAIL_VERIFIED, '0');

        $token = wp_generate_password(32, false, false);
        update_user_meta($user_id, self::META_VERIFY_TOKEN_HASH, wp_hash_password($token));

        DLab_Emails::send_verification_email($user_id, $token);

        return $user_id;
    }

    /**
     * @return array{key:string}|WP_Error
     */
    public static function verify_email($user_id, $token) {
        $user = get_userdata($user_id);
        if (!$user) {
            return new WP_Error('invalid_user', __('Neplatný odkaz.', 'design-lab'));
        }

        $hash = get_user_meta($user_id, self::META_VERIFY_TOKEN_HASH, true);
        if (!$hash || !wp_check_password($token, $hash)) {
            return new WP_Error('invalid_token', __('Ověřovací odkaz je neplatný nebo vypršel.', 'design-lab'));
        }

        if (self::is_email_verified($user_id)) {
            return self::issue_password_key($user_id);
        }

        update_user_meta($user_id, self::META_EMAIL_VERIFIED, '1');
        delete_user_meta($user_id, self::META_PENDING_VERIFICATION);
        delete_user_meta($user_id, self::META_VERIFY_TOKEN_HASH);

        return self::issue_password_key($user_id);
    }

    /**
     * @return array{key:string}
     */
    private static function issue_password_key($user_id) {
        $key = wp_generate_password(32, false, false);
        update_user_meta($user_id, self::META_PASSWORD_KEY, wp_hash_password($key));
        update_user_meta($user_id, self::META_PASSWORD_KEY_EXPIRES, time() + DAY_IN_SECONDS);

        return array('key' => $key);
    }

    public static function set_member_password($user_id, $key, $password, $password_confirm) {
        $user = get_userdata($user_id);
        if (!$user) {
            return new WP_Error('invalid_user', __('Neplatný požadavek.', 'design-lab'));
        }

        if (!self::is_email_verified($user_id)) {
            return new WP_Error('not_verified', __('Nejdříve ověřte e-mail.', 'design-lab'));
        }

        if (!self::validate_password_key($user_id, $key)) {
            return new WP_Error('invalid_key', __('Odkaz pro nastavení hesla vypršel.', 'design-lab'));
        }

        if (strlen($password) < 8) {
            return new WP_Error('weak_password', __('Heslo musí mít alespoň 8 znaků.', 'design-lab'));
        }

        if ($password !== $password_confirm) {
            return new WP_Error('password_mismatch', __('Hesla se neshodují.', 'design-lab'));
        }

        wp_set_password($password, $user_id);
        delete_user_meta($user_id, self::META_PASSWORD_KEY);
        delete_user_meta($user_id, self::META_PASSWORD_KEY_EXPIRES);

        return true;
    }

    private static function validate_password_key($user_id, $key) {
        $hash    = get_user_meta($user_id, self::META_PASSWORD_KEY, true);
        $expires = (int) get_user_meta($user_id, self::META_PASSWORD_KEY_EXPIRES, true);

        if (!$hash || $expires < time()) {
            return false;
        }

        return wp_check_password($key, $hash);
    }

    public function block_unverified_login($user, $username, $password) {
        if ($user instanceof WP_User && self::is_pending_verification($user->ID) && !self::is_email_verified($user->ID)) {
            return new WP_Error(
                'dlab_not_verified',
                __('Účet není ověřen. Zkontrolujte e-mail s ověřovacím odkazem.', 'design-lab')
            );
        }
        return $user;
    }

    public static function get_verification_url($user_id, $token) {
        return add_query_arg(array(
            self::QUERY_VERIFY => '1',
            self::QUERY_UID    => $user_id,
            self::QUERY_TOKEN  => rawurlencode($token),
        ), home_url('/'));
    }

    public function shortcode_set_password() {
        if (is_user_logged_in() && self::is_email_verified(get_current_user_id())) {
            $dash = self::get_page_url('dashboard');
            return '<p class="dlab-auth-notice">' .
                esc_html__('Heslo již máte nastavené.', 'design-lab') .
                ($dash ? ' <a class="textlink textlink-underline" href="' . esc_url($dash) . '">' . esc_html__('Přejít do účtu', 'design-lab') . '</a>' : '') .
                '</p>';
        }

        $uid = isset($_GET[self::QUERY_SET_UID]) ? (int) $_GET[self::QUERY_SET_UID] : 0;
        $key = isset($_GET[self::QUERY_SET_KEY]) ? sanitize_text_field(wp_unslash($_GET[self::QUERY_SET_KEY])) : '';

        if (!$uid || $key === '' || !self::validate_password_key($uid, $key)) {
            return '<p class="dlab-auth-notice dlab-auth-notice--error">' .
                esc_html__('Odkaz pro nastavení hesla je neplatný nebo vypršel. Ověřte e-mail znovu nebo kontaktujte správce.', 'design-lab') .
                '</p>';
        }

        ob_start();
        $this->render_messages();
        include DLAB_PLUGIN_DIR . 'public/partials/set-password-form.php';
        return ob_get_clean();
    }

    private function render_messages() {
        $codes = self::auth_message_codes();

        if (!empty($_GET['dlab_auth_success'])) {
            $code = sanitize_key(wp_unslash($_GET['dlab_auth_success']));
            if (isset($codes[$code])) {
                echo '<div class="dlab-auth-notice dlab-auth-notice--success" role="status"><p>' . esc_html($codes[$code]) . '</p></div>';
            }
        }

        if (!empty($_GET['dlab_auth_error'])) {
            $code = sanitize_key(rawurldecode(wp_unslash($_GET['dlab_auth_error'])));
            $msg  = !empty($_GET['dlab_auth_message'])
                ? sanitize_text_field(rawurldecode(wp_unslash($_GET['dlab_auth_message'])))
                : (isset($codes[$code]) ? $codes[$code] : __('Došlo k chybě.', 'design-lab'));
            echo '<div class="dlab-auth-notice dlab-auth-notice--error" role="alert"><p>' . esc_html($msg) . '</p></div>';
        }
    }
}
