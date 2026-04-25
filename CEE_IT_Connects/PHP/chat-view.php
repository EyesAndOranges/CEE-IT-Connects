<style>
    .chat-container {
        margin: -20px;
        display: flex !important;
        height: calc(100vh - 70px);
        width: calc(100% + 40px);
        overflow: hidden;
    }

    .chat-list, .profile-sidebar {
        height: 100%;
        overflow-y: auto;
        flex-shrink: 0;
    }

    .chat-list {
        border-right: 1px solid #eee;
    }

    .message-area {
        flex-grow: 1;
        display: flex;
        flex-direction: column;
        height: 100%;
        overflow: hidden;
    }

    .chat-inner-header {
        padding: 15px 20px;
        border-bottom: 1px solid #eee;
        display: flex;
        align-items: center;
        gap: 15px;
        background: #F9F9F9;
    }

    #text { font-size: 14px; }
    .fw-bold { font-size: 15px; }

    .message-content {
        flex-grow: 1;
        padding: 20px;
        overflow-y: auto;
        display: flex;
        flex-direction: column;
        background: #FCFCFC;
    }

    .chat-input-section {
        padding: 15px 25px;
        display: flex;
        align-items: center;
        gap: 25px;
        border-top: 1px solid #eee;
        background: #fff;
        margin-top: auto;
    }

    .message-input-box {
        flex-grow: 1;
        background: #fff;
        border: 1px solid #ddd;
        border-radius: 25px;
        padding: 8px 20px;
        outline: none;
    }

    /* COLUMN 3: PROFILE SIDEBAR (Fixed Width) */
    .profile-sidebar {
        width: 300px;
        border-left: 1px solid #eee;
        padding: 30px 20px;
        background: #fff;
        flex-shrink: 0;
        display: flex;
        flex-direction: column;
        align-items: center;
        overflow-y: auto;
    }

    /* TAB CONTAINER */
    .tab-item {
        flex: 1;
        padding-bottom: 10px;
        cursor: pointer;
        font-weight: 600;
        color: #888;
        text-align: center;
    }

    .tab-item.active-tab { color: #29335C; }

    .avatar-circle {
        width: 45px;
        height: 45px;
        background-color: #e0e0e0;
        border-radius: 50%;
        flex-shrink: 0;
    }

    .big-avatar {
        width: 100px;
        height: 100px;
        background-color: #e0e0e0;
        border-radius: 50%;
        margin-bottom: 15px;
    }

    /* TAB SLIDER SYSTEM */
    .media-tabs {
        display: flex;
        width: 100%;
        position: relative;
        border-bottom: 2px solid #eee;
        margin-top: 20px;
    }

    .content-pane { display: none; width: 100%; }
    .active-pane { display: block; }

    /* THE SLIDING LINE */
    .tab-indicator {
        position: absolute;
        bottom: -2px;
        left: 0;
        height: 3px;
        width: 50%;
        background: #29335C;
        transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .tab-content-area {
        position: relative;
        width: 100%;
        align-self: stretch;
    }

    .content-pane.active-pane {
        display: block;
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(5px); }
        to { opacity: 1; transform: translateY(0); }
    }

    /* ICONS & BUBBLES */
    .icon-btn { font-size: 1.3rem; color: #29335C; cursor: pointer; }
    .send-btn { color: #29335C; font-size: 1.3rem; transform: rotate(0deg); }
    
    .bubble { max-width: 70%; padding: 7px 15px; border-radius: 20px; margin-bottom: 8px; }
    .incoming { background: #ffcc80; color: #333; align-self: flex-start; }
    .outgoing { background: #f8d7da; color: #333; align-self: flex-end; }
</style>

<div class="chat-container">
    <div class="chat-list">
        <div class="p-3"><h3><strong>Chats</strong></h3></div>
        <div class="chat-user-item d-flex align-items-center p-3 border-bottom bg-light">
            <div class="avatar-circle me-3"></div>
            <div>
                <div class="fw-bold">First Name Last Name</div>
                <small class="text-muted">Messages</small>
            </div>
        </div>
    </div>

    <div class="message-area">
        <div class="chat-inner-header">
            <div class="avatar-circle" style="width:40px; height:40px;"></div>
            <span class="fw-bold">First Name Last Name</span>
        </div>
        
        <div id="text" class="message-content">
            <div class="text-center text-muted small mb-4">02/03/2026</div>
            <div class="bubble incoming">Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor.</div>
            <div class="bubble outgoing">ut labore et dolore magna aliqua.</div>
            <div class="bubble outgoing">labore et dolore magna.</div>
        </div>

        <div class="chat-input-section">
            <i class="fa-solid fa-file-lines icon-btn"></i>
            <i class="fa-regular fa-image icon-btn"></i>
            <input type="text" class="message-input-box" placeholder="Message">
            <i class="fa-solid fa-paper-plane send-btn icon-btn"></i>
        </div>
    </div>

    <div class="profile-sidebar">
        <div class="big-avatar"></div> <h5 class="fw-bold">First Name Last Name</h5>
        
        <div class="w-100 text-start mt-3" style="font-size: 0.85rem;">
            <div class="mb-2"><i class="fa-solid fa-phone me-2 text-muted"></i> +63 123 456 7890</div>
            <div class="mb-3"><i class="fa-solid fa-envelope me-2 text-muted"></i> firstlastname@gmail.com</div>
        </div>

        <div class="media-tabs" id="profileTabs">
            <div class="tab-item active-tab" onclick="switchTab('media')">Media</div>
            <div class="tab-item" onclick="switchTab('files')">Files</div>
            <div class="tab-indicator" id="tabIndicator"></div>
        </div>

        <div class="tab-content-area">
            <div id="mediaPane" class="content-pane active-pane">
                <div class="media-grid">
                    <div class="media-box"></div>
                    <div class="media-box" style="background:#666;"></div>
                    <div class="media-box" style="background:#999;"></div>
                </div>
            </div>

            <div id="filesPane" class="content-pane">
                <div class="mt-2" style="width: 100%; align-self: stretch">
                    <div class="py-2 border-bottom small d-flex align-items-center gap-2">
                        <i class="fa-solid fa-file-pdf text-danger"></i> Internship_Form.pdf
                    </div>
                    <div class="py-2 border-bottom small d-flex align-items-center gap-2">
                        <i class="fa-solid fa-file-word text-primary"></i> Resume_Draft.docx
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function switchTab(type) {
    const indicator = document.getElementById('tabIndicator');
    const mediaPane = document.getElementById('mediaPane');
    const filesPane = document.getElementById('filesPane');
    const tabs = document.querySelectorAll('.tab-item');

    if (type === 'media') {
        // Move indicator to left
        indicator.style.transform = 'translateX(0%)';
        
        // Update active classes
        tabs[0].classList.add('active-tab');
        tabs[1].classList.remove('active-tab');
        
        // Show media, hide files
        mediaPane.classList.add('active-pane');
        filesPane.classList.remove('active-pane');
    } else {
        // Move indicator to right (50% because there are 2 tabs)
        indicator.style.transform = 'translateX(100%)';
        
        // Update active classes
        tabs[1].classList.add('active-tab');
        tabs[0].classList.remove('active-tab');
        
        // Show files, hide media
        filesPane.classList.add('active-pane');
        mediaPane.classList.remove('active-pane');
    }
}
</script>