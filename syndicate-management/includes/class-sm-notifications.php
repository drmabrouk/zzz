<?php

class SM_Notifications {

    public static function get_template($type) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}sm_notification_templates WHERE template_type = %s",
            $type
        ));
    }

    public static function save_template($data) {
        global $wpdb;
        return $wpdb->replace(
            "{$wpdb->prefix}sm_notification_templates",
            array(
                'template_type' => sanitize_text_field($data['template_type']),
                'subject' => sanitize_text_field($data['subject']),
                'body' => sanitize_textarea_field($data['body']),
                'days_before' => intval($data['days_before']),
                'is_enabled' => isset($data['is_enabled']) ? 1 : 0
            )
        );
    }

    public static function send_template_notification($member_id, $type, $extra_placeholders = []) {
        $template = self::get_template($type);
        if (!$template || !$template->is_enabled) return false;

        $member = SM_DB::get_member_by_id($member_id);
        if (!$member || empty($member->email)) return false;

        $subject = $template->subject;
        $body = $template->body;

        $placeholders = array_merge([
            '{member_name}' => $member->name,
            '{national_id}' => $member->national_id,
            '{membership_number}' => $member->membership_number,
            '{governorate}' => SM_Settings::get_governorates()[$member->governorate] ?? $member->governorate,
            '{year}' => date('Y'),
        ], $extra_placeholders);

        foreach ($placeholders as $search => $replace) {
            $subject = str_replace($search, $replace, $subject);
            $body = str_replace($search, $replace, $body);
        }

        $email_settings = get_option('sm_email_design_settings', [
            'header_bg' => '#111F35',
            'header_text' => '#ffffff',
            'footer_text' => '#64748b',
            'accent_color' => '#F63049'
        ]);

        $syndicate = SM_Settings::get_syndicate_info();

        $html_message = self::wrap_in_template($subject, $body, $email_settings, $syndicate);

        $headers = array('Content-Type: text/html; charset=UTF-8');
        $sent = wp_mail($member->email, $subject, $html_message, $headers);

        self::log_notification($member_id, $type, $member->email, $subject, $sent ? 'success' : 'failed');

        return $sent;
    }

    private static function wrap_in_template($subject, $body, $design, $syndicate) {
        $logo_html = !empty($syndicate['syndicate_logo']) ? '<img src="'.esc_url($syndicate['syndicate_logo']).'" style="max-height:60px; margin-bottom:10px;">' : '';

        ob_start();
        ?>
        <div dir="rtl" style="font-family: 'Arial', sans-serif; background: #f4f7f6; padding: 40px 20px; color: #333;">
            <div style="max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.05);">
                <div style="background: <?php echo $design['header_bg']; ?>; color: <?php echo $design['header_text']; ?>; padding: 40px 30px; text-align: center;">
                    <?php echo $logo_html; ?>
                    <h2 style="margin: 0; font-size: 20px;"><?php echo esc_html($syndicate['syndicate_name']); ?></h2>
                </div>
                <div style="padding: 40px 30px; line-height: 1.8; font-size: 16px;">
                    <h3 style="color: <?php echo $design['accent_color']; ?>; margin-top: 0; margin-bottom: 20px;"><?php echo esc_html($subject); ?></h3>
                    <?php echo nl2br(esc_html($body)); ?>
                </div>
                <div style="padding: 20px 30px; background: #f8fafc; text-align: center; font-size: 12px; color: <?php echo $design['footer_text']; ?>; border-top: 1px solid #edf2f7;">
                    <p style="margin: 0;"><?php echo esc_html($syndicate['address']); ?> | <?php echo esc_html($syndicate['phone']); ?></p>
                    <p style="margin: 5px 0 0 0;">© <?php echo date('Y'); ?> <?php echo esc_html($syndicate['syndicate_name']); ?>. جميع الحقوق محفوظة.</p>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    private static function log_notification($member_id, $type, $email, $subject, $status) {
        global $wpdb;
        $wpdb->insert("{$wpdb->prefix}sm_notification_logs", [
            'member_id' => $member_id,
            'notification_type' => $type,
            'recipient_email' => $email,
            'subject' => $subject,
            'status' => $status,
            'sent_at' => current_time('mysql')
        ]);
    }

    public static function run_daily_checks() {
        self::check_membership_renewals();
        self::check_license_expirations();
        self::check_payment_dues();
    }

    private static function check_membership_renewals() {
        $template = self::get_template('membership_renewal');
        if (!$template || !$template->is_enabled) return;

        global $wpdb;
        $current_year = date('Y');
        // Members who haven't paid for current year
        $members = $wpdb->get_results($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}sm_members WHERE last_paid_membership_year < %d",
            $current_year
        ));

        foreach ($members as $m) {
            // Avoid duplicate emails in the same month for same type?
            // Maybe just check if we logged one in the last 20 days.
            if (!self::already_notified($m->id, 'membership_renewal', 25)) {
                self::send_template_notification($m->id, 'membership_renewal', ['{year}' => $current_year]);
            }
        }
    }

    private static function check_license_expirations() {
        $types = ['license_practice', 'license_facility'];
        global $wpdb;

        foreach ($types as $type) {
            $template = self::get_template($type);
            if (!$template || !$template->is_enabled) continue;

            $days = $template->days_before;
            $target_date = date('Y-m-d', strtotime("+$days days"));

            $field = ($type === 'license_practice') ? 'license_expiration_date' : 'facility_license_expiration_date';

            $members = $wpdb->get_results($wpdb->prepare(
                "SELECT id, $field as expiry, facility_name FROM {$wpdb->prefix}sm_members WHERE $field = %s",
                $target_date
            ));

            foreach ($members as $m) {
                if (!self::already_notified($m->id, $type, 5)) {
                    self::send_template_notification($m->id, $type, [
                        '{expiry_date}' => $m->expiry,
                        '{facility_name}' => $m->facility_name ?? ''
                    ]);
                }
            }
        }
    }

    private static function check_payment_dues() {
        $template = self::get_template('payment_reminder');
        if (!$template || !$template->is_enabled) return;

        $members = SM_DB::get_members(['limit' => -1]);
        foreach ($members as $m) {
            $dues = SM_Finance::calculate_member_dues($m->id);
            if ($dues['balance'] > 500) { // Only remind if debt is significant
                if (!self::already_notified($m->id, 'payment_reminder', 30)) {
                    self::send_template_notification($m->id, 'payment_reminder', ['{balance}' => $dues['balance']]);
                }
            }
        }
    }

    private static function already_notified($member_id, $type, $days_limit) {
        global $wpdb;
        $last_sent = $wpdb->get_var($wpdb->prepare(
            "SELECT sent_at FROM {$wpdb->prefix}sm_notification_logs WHERE member_id = %d AND notification_type = %s ORDER BY sent_at DESC LIMIT 1",
            $member_id, $type
        ));
        if (!$last_sent) return false;
        return (strtotime($last_sent) > strtotime("-$days_limit days"));
    }

    public static function get_logs($limit = 100, $offset = 0) {
        global $wpdb;
        return $wpdb->get_results($wpdb->prepare(
            "SELECT l.*, m.name as member_name
             FROM {$wpdb->prefix}sm_notification_logs l
             LEFT JOIN {$wpdb->prefix}sm_members m ON l.member_id = m.id
             ORDER BY l.sent_at DESC LIMIT %d OFFSET %d",
            $limit, $offset
        ));
    }
}
