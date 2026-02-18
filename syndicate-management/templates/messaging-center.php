<?php if (!defined('ABSPATH')) exit; ?>
<?php
$my_id = get_current_user_id();
$user = wp_get_current_user();
$roles = (array)$user->roles;
$is_official = in_array('sm_syndicate_admin', $roles) || in_array('sm_system_admin', $roles) || in_array('administrator', $roles);
$my_gov = get_user_meta($my_id, 'sm_governorate', true);

// Get member_id if current user is a member
$member_id = 0;
global $wpdb;
$member_by_wp = $wpdb->get_row($wpdb->prepare("SELECT id, governorate FROM {$wpdb->prefix}sm_members WHERE wp_user_id = %d", $my_id));
if ($member_by_wp) {
    $member_id = $member_by_wp->id;
    $my_gov = $member_by_wp->governorate;
}
?>

<div class="sm-messaging-center" style="display: grid; grid-template-columns: 350px 1fr; gap: 0; background: #fff; border: 1px solid var(--sm-border-color); border-radius: 12px; overflow: hidden; min-height: 700px;">
    <!-- Sidebar: Conversations -->
    <div style="border-left: 1px solid var(--sm-border-color); background: #f8fafc; display: flex; flex-direction: column;">
        <div style="padding: 20px; border-bottom: 1px solid var(--sm-border-color); background: #fff;">
            <h3 style="margin:0; font-size: 1.1em; color: var(--sm-dark-color);">مركز المراسلات</h3>
            <?php if ($my_gov): ?>
                <div style="font-size: 0.8em; color: var(--sm-primary-color); font-weight: 700; margin-top: 5px;">محافظة: <?php echo esc_html(SM_Settings::get_governorates()[$my_gov] ?? $my_gov); ?></div>
            <?php endif; ?>
        </div>
        <div id="conversations-list" style="flex: 1; overflow-y: auto;">
            <div id="conv-loading" style="text-align: center; padding: 20px; color: #718096;">جاري تحميل المحادثات...</div>
        </div>
    </div>

    <!-- Main View: Chat -->
    <div id="chat-window" style="display: flex; flex-direction: column; background: #fff; position: relative;">
        <div id="chat-header" style="padding: 20px 30px; border-bottom: 1px solid var(--sm-border-color); display: flex; align-items: center; justify-content: space-between; visibility: hidden; background: #fff;">
            <div style="display: flex; align-items: center; gap: 15px;">
                <div id="active-user-avatar" style="width: 45px; height: 45px; border-radius: 50%; background: #e2e8f0; display: flex; align-items: center; justify-content: center; overflow: hidden;"></div>
                <div>
                    <h3 id="active-user-name" style="margin:0; font-size: 1.1em; color: var(--sm-dark-color);"></h3>
                    <div id="active-user-status" style="font-size: 0.75em; color: #38a169;">متصل الآن</div>
                </div>
            </div>
        </div>
        
        <div id="chat-messages" style="flex: 1; padding: 30px; overflow-y: auto; background: #f7fafc; display: flex; flex-direction: column; gap: 20px;">
            <div style="text-align: center; color: #a0aec0; margin-top: 150px;">
                <span class="dashicons dashicons-format-chat" style="font-size: 60px; width: 60px; height: 60px;"></span>
                <p style="font-size: 1.1em; margin-top: 15px;">اختر محادثة للبدء في المراسلة</p>
            </div>
        </div>

        <div id="chat-input-area" style="padding: 25px 30px; border-top: 1px solid var(--sm-border-color); display: none; background: #fff;">
            <form id="chat-form" style="display: flex; flex-direction: column; gap: 15px;">
                <input type="hidden" name="member_id" id="chat_member_id">
                <input type="hidden" name="receiver_id" id="chat_receiver_id">

                <div style="display: flex; gap: 15px; align-items: flex-end;">
                    <div style="flex: 1; position: relative;">
                        <textarea name="message" id="msg-input" class="sm-textarea" style="width: 100%; border-radius: 20px; padding: 12px 20px; padding-left: 50px; resize: none; min-height: 50px; max-height: 150px;" rows="1" placeholder="اكتب رسالتك هنا..." required></textarea>

                        <div style="position: absolute; left: 10px; bottom: 8px;">
                            <label for="message_file" style="cursor: pointer; color: #718096; padding: 5px; display: block;">
                                <span class="dashicons dashicons-paperclip" style="font-size: 20px;"></span>
                            </label>
                            <input type="file" id="message_file" name="message_file" style="display: none;" onchange="updateFileStatus(this)">
                        </div>
                    </div>
                    <button type="submit" class="sm-btn" style="width: 50px; height: 50px; border-radius: 50%; padding: 0; display: flex; align-items: center; justify-content: center; background: var(--sm-primary-color);"><span class="dashicons dashicons-send" style="color: #fff; transform: rotate(180deg); margin-bottom: 2px;"></span></button>
                </div>
                <div id="file-status" style="font-size: 0.8em; color: var(--sm-primary-color); display: none; padding: 0 10px;"></div>
            </form>
        </div>
    </div>
</div>

<style>
.conv-item { cursor: pointer; border-bottom: 1px solid #edf2f7; transition: 0.2s; position: relative; }
.conv-item:hover { background: #fff !important; }
.conv-item.sm-active { background: #fff !important; border-right: 5px solid var(--sm-primary-color); box-shadow: 0 4px 6px rgba(0,0,0,0.05); z-index: 1; }
.msg-bubble { max-width: 80%; padding: 12px 20px; border-radius: 20px; font-size: 0.95em; line-height: 1.5; position: relative; box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
.msg-sent { align-self: flex-start; background: var(--sm-primary-color); color: #fff; border-bottom-right-radius: 5px; }
.msg-received { align-self: flex-end; background: #fff; border: 1px solid #e2e8f0; color: var(--sm-dark-color); border-bottom-left-radius: 5px; }
.msg-time { font-size: 0.7em; margin-top: 8px; opacity: 0.7; display: block; }
.msg-sent .msg-time { text-align: left; }
.msg-received .msg-time { text-align: right; }
.file-attachment { display: block; margin-top: 10px; padding: 10px; background: rgba(0,0,0,0.05); border-radius: 10px; text-decoration: none; color: inherit; font-size: 0.85em; border: 1px dashed rgba(0,0,0,0.1); }
.msg-sent .file-attachment { background: rgba(255,255,255,0.1); border-color: rgba(255,255,255,0.2); }
@keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
</style>

<script>
(function($) {
    let currentMemberId = null;
    let pollInterval = null;
    const isOfficial = <?php echo $is_official ? 'true' : 'false'; ?>;
    const myId = <?php echo $my_id; ?>;
    const myMemberId = <?php echo $member_id; ?>;

    window.updateFileStatus = function(input) {
        const status = document.getElementById('file-status');
        if (input.files && input.files[0]) {
            status.innerText = "📎 ملف جاهز: " + input.files[0].name;
            status.style.display = 'block';
        } else {
            status.style.display = 'none';
        }
    };

    window.loadConversationsList = function() {
        if (!isOfficial) {
            const list = document.getElementById('conversations-list');
            list.innerHTML = `
                <div class="conv-item sm-active" onclick="window.loadConversation(${myMemberId}, 'لجنة النقابة الفرعية', 0)" style="padding: 20px;">
                    <div style="display: flex; align-items: center; gap: 15px;">
                        <div style="width: 50px; height: 50px; border-radius: 50%; background: #111F35; color: #fff; display: flex; align-items: center; justify-content: center;">
                            <span class="dashicons dashicons-building" style="font-size: 24px; width: 24px; height: 24px;"></span>
                        </div>
                        <div style="flex: 1;">
                            <div style="font-weight: 800; color: #111F35;">لجنة النقابة الفرعية</div>
                            <div style="font-size: 0.75em; color: var(--sm-primary-color); font-weight: 700;"><?php echo esc_js(SM_Settings::get_governorates()[$my_gov] ?? $my_gov); ?></div>
                        </div>
                    </div>
                </div>
            `;
            document.getElementById('conv-loading').style.display = 'none';
            window.loadConversation(myMemberId, 'لجنة النقابة الفرعية', 0);
            return;
        }

        const formData = new FormData();
        formData.append('action', 'sm_get_conversations_ajax');
        formData.append('nonce', '<?php echo wp_create_nonce("sm_message_action"); ?>');

        fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(res => {
            const list = document.getElementById('conversations-list');
            document.getElementById('conv-loading').style.display = 'none';
            
            if (res.success && res.data.length > 0) {
                list.innerHTML = '';
                res.data.forEach(c => {
                    const div = document.createElement('div');
                    div.className = 'conv-item' + (currentMemberId == c.member.id ? ' sm-active' : '');
                    div.onclick = (e) => window.loadConversation(c.member.id, c.member.name, c.member.wp_user_id, e);
                    div.style.padding = '20px';
                    div.innerHTML = `
                        <div style="display: flex; align-items: center; gap: 15px;">
                            <div style="width: 50px; height: 50px; border-radius: 50%; overflow: hidden; background: #edf2f7;">
                                <img src="${c.member.photo_url || 'https://www.gravatar.com/avatar/?d=mp'}" style="width: 100%; height: 100%; object-fit: cover;">
                            </div>
                            <div style="flex: 1; min-width: 0;">
                                <div style="font-weight: 800; color: #111F35; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">${c.member.name}</div>
                                <div style="font-size: 0.8em; color: #718096; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">${c.last_message ? c.last_message.message : 'لا يوجد رسائل'}</div>
                            </div>
                        </div>
                    `;
                    list.appendChild(div);
                });
            } else {
                list.innerHTML = '<p style="text-align: center; color: #a0aec0; margin-top: 50px;">لا توجد محادثات نشطة</p>';
            }
        });
    };

    window.loadConversation = function(memberId, memberName, memberWpId, event) {
        currentMemberId = memberId;

        document.getElementById('chat-header').style.visibility = 'visible';
        document.getElementById('active-user-name').innerText = memberName;
        document.getElementById('active-user-avatar').innerHTML = `<img src="https://www.gravatar.com/avatar/${memberId}?d=mp" style="width:100%; height:100%; object-fit:cover;">`;
        document.getElementById('chat_member_id').value = memberId;
        document.getElementById('chat_receiver_id').value = isOfficial ? memberWpId : 0;
        document.getElementById('chat-input-area').style.display = 'block';

        $('.conv-item').removeClass('sm-active');
        if (event) $(event.currentTarget).addClass('sm-active');
        else $(`.conv-item[onclick*="${memberId}"]`).addClass('sm-active');

        fetchMessages(memberId);
        if (pollInterval) clearInterval(pollInterval);
        pollInterval = setInterval(() => fetchMessages(memberId, true), 5000);
    };

    window.fetchMessages = function(memberId, isPolling = false) {
        const container = document.getElementById('chat-messages');
        if (!isPolling) container.innerHTML = '<div style="text-align:center; margin-top:100px; color:#718096;">جاري تحميل الرسائل...</div>';

        const formData = new FormData();
        formData.append('action', 'sm_get_conversation_ajax');
        formData.append('member_id', memberId);
        formData.append('nonce', '<?php echo wp_create_nonce("sm_message_action"); ?>');

        fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                let newHtml = '';
                res.data.forEach(m => {
                    const isSent = m.sender_id == myId;
                    let fileHtml = '';
                    if (m.file_url) {
                        const fileName = m.file_url.split('/').pop();
                        const isImg = m.file_url.match(/\.(jpg|jpeg|png|gif|webp)$/i);
                        if (isImg) {
                            fileHtml = `<a href="${m.file_url}" target="_blank" class="file-attachment"><img src="${m.file_url}" style="max-width:100%; border-radius:10px; margin-bottom:5px; display:block;"><span>${fileName}</span></a>`;
                        } else {
                            fileHtml = `<a href="${m.file_url}" target="_blank" class="file-attachment"><span class="dashicons dashicons-pdf" style="vertical-align:middle; margin-left:5px;"></span> ${fileName}</a>`;
                        }
                    }
                    newHtml += `<div class="msg-bubble ${isSent ? 'msg-sent' : 'msg-received'}">
                        <div style="font-weight:800; font-size:0.75em; margin-bottom:6px; display:flex; align-items:center; gap:5px;">
                            <span class="dashicons dashicons-admin-users" style="font-size:14px; width:14px; height:14px;"></span>
                            ${m.sender_name || 'مستخدم'}
                        </div>
                        <div style="word-break: break-word;">${m.message}</div>
                        ${fileHtml}
                        <span class="msg-time">${m.created_at}</span>
                    </div>`;
                });
                if (container.innerHTML !== newHtml) {
                    container.innerHTML = newHtml;
                    container.scrollTop = container.scrollHeight;
                }
            }
        });
    }

    document.getElementById('chat-form').addEventListener('submit', function(e) {
        e.preventDefault();
        const btn = this.querySelector('button[type="submit"]');
        const originalHtml = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="dashicons dashicons-update" style="animation: spin 1s infinite linear; display:inline-block;"></span>';

        const formData = new FormData(this);
        formData.append('action', 'sm_send_message_ajax');
        formData.append('sm_message_nonce', '<?php echo wp_create_nonce("sm_message_action"); ?>');

        fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(res => {
            btn.disabled = false;
            btn.innerHTML = originalHtml;
            if (res.success) {
                this.querySelector('textarea').value = '';
                this.querySelector('input[type="file"]').value = '';
                document.getElementById('file-status').style.display = 'none';
                fetchMessages(formData.get('member_id'));
            } else alert('خطأ: ' + res.data);
        });
    });

    const tx = document.getElementById('msg-input');
    tx.addEventListener("input", function() {
        this.style.height = "auto";
        this.style.height = (this.scrollHeight) + "px";
    }, false);

    window.loadConversationsList();
})(jQuery);
</script>
