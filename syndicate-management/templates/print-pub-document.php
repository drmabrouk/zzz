<?php if (!defined('ABSPATH')) exit; ?>
<?php
$id = intval($_GET['id']);
global $wpdb;
$doc = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}sm_pub_documents WHERE id = %d", $id));
if (!$doc) wp_die('Document not found');

$options = json_decode($doc->options, true);
$syndicate = SM_Settings::get_syndicate_info();
$stamp_url = get_option('sm_pub_stamp_url');
$footer_text = get_option('sm_pub_footer_statement');
?>
<!DOCTYPE html>
<html dir="rtl">
<head>
    <meta charset="UTF-8">
    <title><?php echo esc_html($doc->title); ?></title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Rubik:wght@400;700&display=swap');
        body { font-family: 'Rubik', Arial, sans-serif; margin: 0; padding: 0; background: #fff; color: #333; }
        .page { width: 210mm; min-height: 297mm; padding: 20mm; margin: 10mm auto; border: 1px solid #eee; position: relative; box-sizing: border-box; }

        .header { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #333; padding-bottom: 10px; margin-bottom: 30px; }
        .header-logo { height: 80px; }
        .header-info { text-align: left; font-size: 12px; }

        .doc-title { text-align: center; margin-bottom: 40px; }
        .doc-title h1 { font-size: 24px; text-decoration: underline; margin-bottom: 5px; }
        .doc-meta { font-size: 12px; color: #666; }

        .content { line-height: 1.8; font-size: 16px; min-height: 150mm; }

        .footer { border-top: 1px solid #eee; padding-top: 20px; margin-top: 40px; display: flex; justify-content: space-between; align-items: flex-end; }
        .footer-text { font-size: 11px; color: #777; flex: 1; }
        .footer-stamp { width: 120px; text-align: center; }
        .footer-stamp img { width: 100px; opacity: 0.8; }

        .qr-code { width: 80px; height: 80px; background: #f0f0f0; border: 1px solid #ccc; display: flex; align-items: center; justify-content: center; font-size: 10px; text-align: center; }

        @media print {
            body { background: none; }
            .page { margin: 0; border: none; box-shadow: none; width: 100%; height: 100%; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <div class="no-print" style="background: #333; padding: 10px; text-align: center;">
        <button onclick="window.print()" style="padding: 10px 20px; cursor: pointer;">طباعة المستند / حفظ PDF</button>
    </div>

    <div class="page">
        <?php if ($options['header']): ?>
        <div class="header">
            <div class="header-right">
                <img src="<?php echo esc_url($syndicate['syndicate_logo']); ?>" class="header-logo">
            </div>
            <div class="header-center" style="text-align: center;">
                <h2 style="margin:0;"><?php echo esc_html($syndicate['syndicate_name']); ?></h2>
                <p style="margin:5px 0 0 0; font-size:14px;">مركز الطباعة والنشر الرقمي</p>
            </div>
            <div class="header-info">
                <div>الرقم المسلسل: <?php echo $doc->serial_number; ?></div>
                <div>تاريخ الإصدار: <?php echo date('Y-m-d', strtotime($doc->created_at)); ?></div>
            </div>
        </div>
        <?php endif; ?>

        <div class="doc-title">
            <h1><?php echo esc_html($doc->title); ?></h1>
        </div>

        <div class="content">
            <?php echo $doc->content; ?>
        </div>

        <div class="footer">
            <div class="footer-text">
                <?php if ($options['footer']): ?>
                    <p><?php echo nl2br(esc_html($footer_text)); ?></p>
                <?php endif; ?>
                <div style="font-size: 10px; margin-top: 10px;">تم توليد هذا المستند آلياً بواسطة نظام إدارة النقابة الإلكتروني</div>
            </div>

            <?php if ($options['qr']): ?>
            <div class="qr-code">
                <!-- In a real system, generate a QR code pointing to a verification URL -->
                <div style="padding: 5px;">كود التحقق الرقمي<br>QR CODE</div>
            </div>
            <?php endif; ?>

            <?php if ($options['footer'] && $stamp_url): ?>
            <div class="footer-stamp">
                <div style="font-size: 12px; margin-bottom: 5px;">ختم المؤسسة</div>
                <img src="<?php echo esc_url($stamp_url); ?>">
            </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Auto print if requested
        if (window.location.search.indexOf('autoprint=1') > -1) {
            window.onload = function() { window.print(); }
        }
    </script>
</body>
</html>
