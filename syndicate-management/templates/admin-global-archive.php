<?php if (!defined('ABSPATH')) exit; ?>
<?php
$user = wp_get_current_user();
$has_full_access = current_user_can('sm_full_access') || current_user_can('manage_options');
$my_gov = get_user_meta($user->ID, 'sm_governorate', true);

global $wpdb;
$search = sanitize_text_field($_GET['doc_search'] ?? '');
$category = sanitize_text_field($_GET['doc_category'] ?? '');

$where = "1=1";
$params = [];

if (!empty($search)) {
    $where .= " AND (d.title LIKE %s OR m.name LIKE %s OR m.national_id LIKE %s)";
    $params[] = '%' . $wpdb->esc_like($search) . '%';
    $params[] = '%' . $wpdb->esc_like($search) . '%';
    $params[] = '%' . $wpdb->esc_like($search) . '%';
}

if (!empty($category)) {
    $where .= " AND d.category = %s";
    $params[] = $category;
}

if (!$has_full_access && $my_gov) {
    $where .= " AND m.governorate = %s";
    $params[] = $my_gov;
}

$query = "
    SELECT d.*, m.name as member_name, m.national_id as member_nid
    FROM {$wpdb->prefix}sm_documents d
    JOIN {$wpdb->prefix}sm_members m ON d.member_id = m.id
    WHERE $where
    ORDER BY d.created_at DESC
";

$documents = !empty($params) ? $wpdb->get_results($wpdb->prepare($query, $params)) : $wpdb->get_results($query);
?>

<div class="sm-global-archive" dir="rtl">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
        <h3 style="margin:0;">الأرشيف الرقمي الشامل</h3>
        <div style="font-size: 14px; color: #718096;">إجمالي المستندات المؤرشفة: <?php echo count($documents); ?></div>
    </div>

    <!-- Filter Bar -->
    <div style="background: #f8fafc; padding: 20px; border-radius: 12px; border: 1px solid #e2e8f0; margin-bottom: 25px;">
        <form method="get" style="display: flex; gap: 15px; align-items: flex-end; flex-wrap: wrap;">
            <input type="hidden" name="sm_tab" value="global-archive">
            <div style="flex: 1; min-width: 250px;">
                <label class="sm-label">بحث في الأرشيف (العنوان أو اسم العضو):</label>
                <input type="text" name="doc_search" class="sm-input" value="<?php echo esc_attr($search); ?>" placeholder="أدخل كلمات البحث...">
            </div>
            <div style="width: 150px;">
                <label class="sm-label">التصنيف:</label>
                <select name="doc_category" class="sm-select">
                    <option value="">الكل</option>
                    <option value="licenses" <?php selected($category, 'licenses'); ?>>التراخيص</option>
                    <option value="certificates" <?php selected($category, 'certificates'); ?>>الشهادات</option>
                    <option value="receipts" <?php selected($category, 'receipts'); ?>>إيصالات السداد</option>
                    <option value="other" <?php selected($category, 'other'); ?>>أخرى</option>
                </select>
            </div>
            <button type="submit" class="sm-btn" style="width: auto; height: 42px;">فلترة النتائج</button>
            <a href="<?php echo remove_query_arg(['doc_search', 'doc_category']); ?>" class="sm-btn sm-btn-outline" style="width: auto; height: 42px; text-decoration:none; display:flex; align-items:center;">إعادة ضبط</a>
        </form>
    </div>

    <div class="sm-table-container">
        <table class="sm-table">
            <thead>
                <tr>
                    <th>تاريخ الأرشفة</th>
                    <th>العضو</th>
                    <th>عنوان المستند</th>
                    <th>التصنيف</th>
                    <th>النوع</th>
                    <th>الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($documents)): ?>
                    <tr><td colspan="6" style="text-align:center; padding: 40px; color: #718096;">لا توجد مستندات تطابق معايير البحث.</td></tr>
                <?php else: ?>
                    <?php
                    $catNames = ['licenses' => 'ترخيص', 'certificates' => 'شهادة', 'receipts' => 'إيصال', 'other' => 'أخرى'];
                    foreach ($documents as $d): ?>
                        <tr>
                            <td style="font-size: 11px; color: #718096;"><?php echo $d->created_at; ?></td>
                            <td>
                                <div style="font-weight: 700;"><?php echo esc_html($d->member_name); ?></div>
                                <div style="font-size: 10px; color: #94a3b8;"><?php echo esc_html($d->member_nid); ?></div>
                            </td>
                            <td style="font-weight: 600;"><?php echo esc_html($d->title); ?></td>
                            <td><span class="sm-badge sm-badge-low"><?php echo $catNames[$d->category]; ?></span></td>
                            <td style="font-size: 11px;"><?php echo $d->file_type; ?></td>
                            <td>
                                <div style="display: flex; gap: 8px;">
                                    <button onclick="smGlobalViewDoc('<?php echo $d->file_url; ?>', '<?php echo esc_js($d->title); ?>', <?php echo $d->id; ?>)" class="sm-btn" style="height:28px; font-size:11px; width:auto; background:#111F35; padding: 0 10px;">عرض</button>
                                    <a href="<?php echo add_query_arg(['sm_tab' => 'member-profile', 'member_id' => $d->member_id], admin_url('admin-ajax.php?action=sm_admin')); ?>#document-vault" class="sm-btn sm-btn-outline" style="height:28px; font-size:11px; width:auto; padding: 0 10px; text-decoration:none; display:flex; align-items:center;">الملف</a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Re-use Viewer Modal logic from profile? I should put it in public-admin-panel.php if it's used globally -->
<div id="sm-global-viewer-modal" class="sm-modal-overlay">
    <div class="sm-modal-content" style="max-width: 900px; height: 90vh; display: flex; flex-direction: column;">
        <div class="sm-modal-header">
            <h3 id="sm-global-viewer-title">عرض المستند</h3>
            <div style="display: flex; gap: 10px; align-items: center;">
                <a href="" id="sm-global-viewer-download" target="_blank" class="sm-btn" style="width:auto; height:32px; font-size:11px; background:#27ae60; text-decoration:none; display:flex; align-items:center;">تحميل</a>
                <button class="sm-modal-close" onclick="document.getElementById('sm-global-viewer-modal').style.display='none'">&times;</button>
            </div>
        </div>
        <div id="sm-global-viewer-body" style="flex: 1; background: #525659; overflow: hidden; position: relative;"></div>
    </div>
</div>

<script>
function smGlobalViewDoc(url, title, id) {
    document.getElementById('sm-global-viewer-title').innerText = title;
    document.getElementById('sm-global-viewer-download').href = url;
    const body = document.getElementById('sm-global-viewer-body');
    const isPdf = url.toLowerCase().endsWith('.pdf');
    if (isPdf) {
        body.innerHTML = `<iframe src="${url}" style="width:100%; height:100%; border:none;"></iframe>`;
    } else {
        body.innerHTML = `<div style="width:100%; height:100%; display:flex; align-items:center; justify-content:center; padding:20px;"><img src="${url}" style="max-width:100%; max-height:100%; object-fit:contain; box-shadow:0 0 50px rgba(0,0,0,0.5);"></div>`;
    }
    document.getElementById('sm-global-viewer-modal').style.display = 'flex';
}
</script>
