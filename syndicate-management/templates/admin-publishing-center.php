<?php if (!defined('ABSPATH')) exit; ?>
<?php
$user = wp_get_current_user();
$templates = SM_DB::get_pub_templates();
$generated = SM_DB::get_pub_documents();
$syndicate = SM_Settings::get_syndicate_info();
?>

<div class="sm-publishing-center" dir="rtl">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
        <h2 style="margin:0; font-weight: 900; color: var(--sm-dark-color);">مركز الطباعة والنشر الرقمي</h2>
        <div style="display: flex; gap: 10px;">
            <button onclick="smOpenInternalTab('create-document', this)" class="sm-btn" style="width: auto; background: var(--sm-primary-color); border-radius: 8px;">+ إنشاء مستند جديد</button>
            <button onclick="smOpenInternalTab('document-logs', this)" class="sm-btn sm-btn-outline" style="width: auto; border-radius: 8px;">سجل المنشورات</button>
        </div>
    </div>

    <!-- Layout Tabs -->
    <div class="sm-tabs-wrapper" style="display: flex; gap: 10px; margin-bottom: 25px; border-bottom: 2px solid #eee; padding-bottom: 10px;">
        <button class="sm-tab-btn sm-active" onclick="smOpenInternalTab('create-document', this)">إنشاء وتحرير</button>
        <button class="sm-tab-btn" onclick="smOpenInternalTab('approved-templates', this)">القوالب المعتمدة</button>
        <button class="sm-tab-btn" onclick="smOpenInternalTab('document-logs', this)">سجل المستندات</button>
        <button class="sm-tab-btn" onclick="smOpenInternalTab('pub-settings', this)">إعدادات الهوية</button>
    </div>

    <!-- TAB: CREATE DOCUMENT -->
    <div id="create-document" class="sm-internal-tab">
        <div style="display: grid; grid-template-columns: 1fr 350px; gap: 30px;">
            <!-- Editor Column -->
            <div style="background: #fff; padding: 30px; border-radius: 15px; border: 1px solid var(--sm-border-color); box-shadow: var(--sm-shadow);">
                <div class="sm-form-group">
                    <label class="sm-label">عنوان المستند (Title):</label>
                    <input type="text" id="pub_doc_title" class="sm-input" placeholder="مثال: بيان حالة عضوية، شهادة خبرة...">
                </div>

                <div id="pub-editor-toolbar" style="background: #f8fafc; padding: 10px; border: 1px solid #e2e8f0; border-radius: 8px 8px 0 0; display: flex; gap: 10px; flex-wrap: wrap; align-items: center; border-bottom: none;">
                    <select onchange="smExecCommand('fontName', this.value)" class="sm-select" style="width: auto; height: 30px; font-size: 11px;">
                        <option value="Arial">Arial</option>
                        <option value="Rubik">Rubik</option>
                        <option value="Traditional Arabic">Traditional Arabic</option>
                        <option value="Sakkal Majalla">Sakkal Majalla</option>
                    </select>
                    <select onchange="smExecCommand('fontSize', this.value)" class="sm-select" style="width: auto; height: 30px; font-size: 11px;">
                        <option value="3">عادي</option>
                        <option value="4">متوسط</option>
                        <option value="5">كبير</option>
                        <option value="6">كبير جداً</option>
                    </select>
                    <div style="height: 20px; width: 1px; background: #cbd5e0;"></div>
                    <button onclick="smExecCommand('bold')" class="editor-tool-btn" title="عريض"><span class="dashicons dashicons-editor-bold"></span></button>
                    <button onclick="smExecCommand('italic')" class="editor-tool-btn" title="مائل"><span class="dashicons dashicons-editor-italic"></span></button>
                    <button onclick="smExecCommand('underline')" class="editor-tool-btn" title="تحته خط"><span class="dashicons dashicons-editor-underline"></span></button>
                    <div style="height: 20px; width: 1px; background: #cbd5e0;"></div>
                    <button onclick="smExecCommand('justifyRight')" class="editor-tool-btn"><span class="dashicons dashicons-editor-alignright"></span></button>
                    <button onclick="smExecCommand('justifyCenter')" class="editor-tool-btn"><span class="dashicons dashicons-editor-aligncenter"></span></button>
                    <button onclick="smExecCommand('justifyLeft')" class="editor-tool-btn"><span class="dashicons dashicons-editor-alignleft"></span></button>
                    <div style="height: 20px; width: 1px; background: #cbd5e0;"></div>
                    <input type="color" onchange="smExecCommand('foreColor', this.value)" style="width:30px; height:30px; padding:0; border:none; background:none; cursor:pointer;">
                </div>

                <!-- THE EDITOR CANVAS -->
                <div id="pub-document-editor" contenteditable="true" style="min-height: 500px; padding: 50px; border: 1px solid #e2e8f0; border-radius: 0 0 8px 8px; background: #fff; line-height: 1.8; font-family: 'Arial'; outline: none; box-shadow: inset 0 2px 4px rgba(0,0,0,0.02);">
                    <div style="text-align: center; margin-bottom: 30px;">
                        <h3>اكتب محتوى المستند هنا</h3>
                        <p>يمكنك استخدام القوالب الجاهزة من القائمة الجانبية لتوفير الوقت.</p>
                    </div>
                </div>

                <div style="margin-top: 25px; display: flex; justify-content: flex-end; gap: 15px;">
                    <button onclick="smGenerateDocument('pdf')" class="sm-btn" style="width:auto; background: #2c3e50;"><span class="dashicons dashicons-pdf"></span> توليد PDF</button>
                    <button onclick="smGenerateDocument('image')" class="sm-btn" style="width:auto; background: #27ae60;"><span class="dashicons dashicons-format-image"></span> تصدير كصورة</button>
                </div>
            </div>

            <!-- Sidebar Controls -->
            <div style="display: flex; flex-direction: column; gap: 20px;">
                <div style="background: #fff; padding: 20px; border-radius: 12px; border: 1px solid var(--sm-border-color);">
                    <h4 style="margin-top: 0; border-bottom: 1px solid #eee; padding-bottom: 10px;">إدراج بيانات ديناميكية</h4>
                    <p style="font-size: 11px; color: #718096;">انقر لإدراج الحقل في مكان المؤشر:</p>
                    <div style="display: flex; flex-wrap: wrap; gap: 8px;">
                        <button onclick="smInsertPlaceholder('{MEMBER_NAME}')" class="placeholder-btn">اسم العضو</button>
                        <button onclick="smInsertPlaceholder('{NATIONAL_ID}')" class="placeholder-btn">الرقم القومي</button>
                        <button onclick="smInsertPlaceholder('{MEMBERSHIP_NO}')" class="placeholder-btn">رقم القيد</button>
                        <button onclick="smInsertPlaceholder('{GRADE}')" class="placeholder-btn">الدرجة</button>
                        <button onclick="smInsertPlaceholder('{SPECIALIZATION}')" class="placeholder-btn">التخصص</button>
                        <button onclick="smInsertPlaceholder('{DATE_NOW}')" class="placeholder-btn">تاريخ اليوم</button>
                        <button onclick="smInsertPlaceholder('{SERIAL_NO}')" class="placeholder-btn">رقم المرجع</button>
                    </div>
                </div>

                <div style="background: #fff; padding: 20px; border-radius: 12px; border: 1px solid var(--sm-border-color);">
                    <h4 style="margin-top: 0; border-bottom: 1px solid #eee; padding-bottom: 10px;">تحميل قالب</h4>
                    <select id="pub_template_select" class="sm-select" style="margin-bottom: 15px;">
                        <option value="">-- اختر قالباً --</option>
                        <?php foreach($templates as $t): ?>
                            <option value="<?php echo $t->id; ?>"><?php echo esc_html($t->title); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button onclick="smLoadTemplateToEditor()" class="sm-btn sm-btn-outline" style="font-size: 12px;">تطبيق القالب المختار</button>
                </div>

                <div style="background: #FFF5F5; padding: 20px; border-radius: 12px; border: 1px solid #feb2b2;">
                    <h4 style="margin-top: 0; color: #c53030;">أدوات الهوية</h4>
                    <label style="display: block; font-size: 12px; margin-bottom: 10px;"><input type="checkbox" id="pub_include_header" checked> تضمين الترويسة الرسمية</label>
                    <label style="display: block; font-size: 12px; margin-bottom: 10px;"><input type="checkbox" id="pub_include_footer" checked> تضمين التذييل والختم</label>
                    <label style="display: block; font-size: 12px; margin-bottom: 10px;"><input type="checkbox" id="pub_include_qr" checked> إدراج كود التحقق (QR)</label>
                </div>
            </div>
        </div>
    </div>

    <!-- TAB: APPROVED TEMPLATES -->
    <div id="approved-templates" class="sm-internal-tab" style="display: none;">
        <div style="background: #fff; padding: 25px; border-radius: 15px; border: 1px solid var(--sm-border-color);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h4 style="margin: 0;">إدارة قوالب المستندات</h4>
                <button onclick="smCreateNewTemplate()" class="sm-btn" style="width: auto;">+ إضافة قالب جديد</button>
            </div>
            <div class="sm-table-container">
                <table class="sm-table">
                    <thead>
                        <tr>
                            <th>اسم القالب</th>
                            <th>النوع</th>
                            <th>تاريخ الإضافة</th>
                            <th>إجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($templates)): ?>
                            <tr><td colspan="4" style="text-align:center; padding: 30px;">لا توجد قوالب معتمدة حالياً.</td></tr>
                        <?php else: ?>
                            <?php foreach($templates as $t): ?>
                                <tr>
                                    <td style="font-weight: 700;"><?php echo esc_html($t->title); ?></td>
                                    <td><span class="sm-badge sm-badge-low"><?php echo $t->doc_type; ?></span></td>
                                    <td><?php echo date('Y-m-d', strtotime($t->created_at)); ?></td>
                                    <td>
                                        <button onclick='smEditTemplate(<?php echo json_encode($t); ?>)' class="sm-btn sm-btn-outline" style="width:auto; height:28px; font-size:11px;">تعديل</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- TAB: DOCUMENT LOGS -->
    <div id="document-logs" class="sm-internal-tab" style="display: none;">
        <div style="background: #fff; padding: 25px; border-radius: 15px; border: 1px solid var(--sm-border-color);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h4 style="margin: 0;">سجل المستندات التي تم توليدها</h4>
                <input type="text" id="pub_log_search" placeholder="بحث بالرقم المسلسل أو العنوان..." class="sm-input" style="width: 300px;" oninput="smFilterLogs()">
            </div>
            <div class="sm-table-container">
                <table class="sm-table" id="pub-logs-table">
                    <thead>
                        <tr>
                            <th>الرقم المسلسل</th>
                            <th>عنوان المستند</th>
                            <th>تاريخ الإصدار</th>
                            <th>بواسطة</th>
                            <th>التحميلات</th>
                            <th>إجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($generated as $d): ?>
                            <tr>
                                <td style="font-family: monospace; font-weight: 700; color: var(--sm-primary-color);"><?php echo $d->serial_number; ?></td>
                                <td style="font-weight: 700;"><?php echo esc_html($d->title); ?></td>
                                <td style="font-size: 11px;"><?php echo $d->created_at; ?></td>
                                <td><?php echo esc_html($d->creator_name); ?></td>
                                <td><span class="sm-badge" style="background:#edf2f7; color:#2d3748;"><?php echo $d->download_count; ?></span></td>
                                <td>
                                    <div style="display: flex; gap: 5px;">
                                        <button onclick="smDownloadGenerated(<?php echo $d->id; ?>, 'pdf')" class="sm-btn" style="width:auto; height:26px; font-size:10px; background:#111F35;">PDF</button>
                                        <button onclick="smDownloadGenerated(<?php echo $d->id; ?>, 'image')" class="sm-btn" style="width:auto; height:26px; font-size:10px; background:#27ae60;">IMG</button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- TAB: IDENTITY SETTINGS -->
    <div id="pub-settings" class="sm-internal-tab" style="display: none;">
        <div style="max-width: 800px; background: #fff; padding: 30px; border-radius: 15px; border: 1px solid var(--sm-border-color);">
            <h4 style="margin-top: 0; border-bottom: 1px solid #eee; padding-bottom: 15px; margin-bottom: 20px;">إعدادات الهوية البصرية للمستندات</h4>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div class="sm-form-group">
                    <label class="sm-label">شعار الترويسة الرسمي:</label>
                    <div style="display: flex; gap: 10px;">
                        <input type="text" id="pub_id_logo" class="sm-input" value="<?php echo esc_attr($syndicate['syndicate_logo']); ?>">
                        <button onclick="smOpenMediaUploader('pub_id_logo')" class="sm-btn sm-btn-outline" style="width:auto;">اختيار</button>
                    </div>
                </div>
                <div class="sm-form-group">
                    <label class="sm-label">ختم المؤسسة الرقمي:</label>
                    <div style="display: flex; gap: 10px;">
                        <input type="text" id="pub_id_stamp" class="sm-input" value="<?php echo esc_attr(get_option('sm_pub_stamp_url')); ?>">
                        <button onclick="smOpenMediaUploader('pub_id_stamp')" class="sm-btn sm-btn-outline" style="width:auto;">اختيار</button>
                    </div>
                </div>
                <div class="sm-form-group" style="grid-column: span 2;">
                    <label class="sm-label">نص التحقق الرسمي (Footer Statement):</label>
                    <textarea id="pub_id_footer_statement" class="sm-input" rows="3"><?php echo esc_textarea(get_option('sm_pub_footer_statement', 'يعتبر هذا المستند رسمياً وصادراً من المنصة الإلكترونية للنقابة، ويمكن التحقق من صحته عبر رمز الاستجابة السريع.')); ?></textarea>
                </div>
            </div>
            <button onclick="smSavePubIdentity()" class="sm-btn" style="margin-top: 20px; width: auto; padding: 0 40px;">حفظ إعدادات الهوية</button>
        </div>
    </div>

</div>

<style>
.editor-tool-btn {
    width: 32px; height: 32px; display: flex; align-items: center; justify-content: center;
    background: #fff; border: 1px solid #e2e8f0; border-radius: 6px; cursor: pointer; color: #4a5568;
    transition: 0.2s;
}
.editor-tool-btn:hover { background: #edf2f7; color: var(--sm-primary-color); border-color: var(--sm-primary-color); }
.placeholder-btn {
    font-size: 10px; background: #f1f5f9; border: 1px solid #e2e8f0; border-radius: 4px;
    padding: 4px 8px; cursor: pointer; transition: 0.2s; font-weight: 700;
}
.placeholder-btn:hover { background: var(--sm-dark-color); color: #fff; }
</style>

<script>
function smExecCommand(cmd, val = null) {
    document.execCommand(cmd, false, val);
    document.getElementById('pub-document-editor').focus();
}

function smInsertPlaceholder(text) {
    document.execCommand('insertText', false, text);
}

function smLoadTemplateToEditor() {
    const id = document.getElementById('pub_template_select').value;
    if (!id) return;

    fetch(ajaxurl + '?action=sm_get_pub_template&id=' + id)
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            document.getElementById('pub_doc_title').value = res.data.title;
            document.getElementById('pub-document-editor').innerHTML = res.data.content;
            smShowNotification('تم تحميل القالب بنجاح');
        }
    });
}

function smGenerateDocument(format) {
    const title = document.getElementById('pub_doc_title').value;
    const content = document.getElementById('pub-document-editor').innerHTML;

    if (!title) return alert('يرجى إدخال عنوان للمستند أولاً');

    const fd = new FormData();
    fd.append('action', 'sm_generate_pub_doc');
    fd.append('title', title);
    fd.append('content', content);
    fd.append('format', format);
    fd.append('header', document.getElementById('pub_include_header').checked ? 1 : 0);
    fd.append('footer', document.getElementById('pub_include_footer').checked ? 1 : 0);
    fd.append('qr', document.getElementById('pub_include_qr').checked ? 1 : 0);
    fd.append('nonce', '<?php echo wp_create_nonce("sm_pub_action"); ?>');

    fetch(ajaxurl, { method: 'POST', body: fd })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            window.open(res.data.url, '_blank');
            location.reload();
        } else {
            alert('خطأ: ' + res.data);
        }
    });
}

function smDownloadGenerated(id, format) {
    window.open(ajaxurl + '?action=sm_print_pub_doc&id=' + id + '&format=' + format, '_blank');
}

function smSavePubIdentity() {
    const logo = document.getElementById('pub_id_logo').value;
    const stamp = document.getElementById('pub_id_stamp').value;
    const footer = document.getElementById('pub_id_footer_statement').value;

    const fd = new FormData();
    fd.append('action', 'sm_save_pub_identity');
    fd.append('logo', logo);
    fd.append('stamp', stamp);
    fd.append('footer', footer);
    fd.append('nonce', '<?php echo wp_create_nonce("sm_pub_action"); ?>');

    fetch(ajaxurl, { method: 'POST', body: fd })
    .then(r => r.json()).then(res => {
        if (res.success) smShowNotification('تم حفظ إعدادات الهوية بنجاح');
    });
}

function smFilterLogs() {
    const val = document.getElementById('pub_log_search').value.toLowerCase();
    const rows = document.querySelectorAll('#pub-logs-table tbody tr');
    rows.forEach(row => {
        row.style.display = row.innerText.toLowerCase().includes(val) ? '' : 'none';
    });
}

// Internal Tab Logic
window.smOpenInternalTab = function(tabId, element) {
    document.querySelectorAll('.sm-internal-tab').forEach(t => t.style.display = 'none');
    document.getElementById(tabId).style.display = 'block';

    if (element.classList.contains('sm-tab-btn')) {
        element.parentElement.querySelectorAll('.sm-tab-btn').forEach(b => b.classList.remove('sm-active'));
        element.classList.add('sm-active');
    }
}
</script>
