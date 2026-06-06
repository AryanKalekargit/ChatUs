// assets/js/chat.js
document.addEventListener('DOMContentLoaded', () => {
    const contactList = document.getElementById('contact-list');
    const chatHeader = document.getElementById('chat-header');
    const chatMessages = document.getElementById('chat-messages');
    const chatInputArea = document.getElementById('chat-input-area');
    const emptyChatState = document.getElementById('empty-chat-state');

    let activeAvatar = document.getElementById('active-chat-avatar');
    let activeName = document.getElementById('active-chat-name');

    // Store current active user/group data for live updates
    window.ACTIVE_USER_PROFILE_IMAGE = null;
    window.ACTIVE_GROUP_IMAGE = null;

    // Helper to check online status
    function isOnline(lastActive) {
        if (!lastActive) return false;
        const lastActiveDate = new Date(lastActive);
        const now = new Date();
        const diffInSeconds = Math.floor((now - lastActiveDate) / 1000);
        return diffInSeconds < 300; // 5 minutes
    }

    // Dynamic Self-Theme Helper
    function applySelfTheme(imageUrl) {
        if (!imageUrl || imageUrl.includes('default.png')) {
            document.documentElement.style.setProperty('--self-dynamic-color', 'var(--yellow-radium)');
            return;
        }

        const img = new Image();
        img.crossOrigin = "Anonymous";
        img.src = imageUrl;
        img.onload = function () {
            const canvas = document.createElement('canvas');
            const ctx = canvas.getContext('2d');
            canvas.width = 5;
            canvas.height = 5;
            ctx.drawImage(img, 0, 0, 5, 5);
            const data = ctx.getImageData(0, 0, 5, 5).data;
            let r = 0, g = 0, b = 0;
            for (let i = 0; i < data.length; i += 4) {
                r += data[i]; g += data[i + 1]; b += data[i + 2];
            }
            r = Math.floor(r / (data.length / 4));
            g = Math.floor(g / (data.length / 4));
            b = Math.floor(b / (data.length / 4));

            // Brighten for neon effect
            const brightColor = `rgb(${Math.max(r, 180)}, ${Math.max(g, 180)}, ${Math.max(b, 50)})`;
            document.documentElement.style.setProperty('--self-dynamic-color', brightColor);
        };
    }

    // Mobile View Helpers
    function showChat() {
        if (window.innerWidth <= 768) {
            document.querySelector('.chat-app-container').classList.add('chat-active');
            document.getElementById('mobile-bottom-nav')?.classList.add('d-none');
        }
    }

    function showSidebar() {
        if (window.innerWidth <= 768) {
            document.querySelector('.chat-app-container').classList.remove('chat-active');
            document.getElementById('mobile-bottom-nav')?.classList.remove('d-none');
        }
    }

    // --- ZENITH AUDIO ENGINE (Web Audio API) ---
    let audioCtx = null;
    function getAudioCtx() {
        if (!audioCtx) audioCtx = new (window.AudioContext || window.webkitAudioContext)();
        if (audioCtx.state === 'suspended') audioCtx.resume();
        return audioCtx;
    }

    window.playZenithSound = function (type) {
        try {
            const ctx = getAudioCtx();
            const now = ctx.currentTime;

            if (type === 'pop') {
                const osc = ctx.createOscillator();
                const gain = ctx.createGain();
                osc.type = 'sine';
                osc.frequency.setValueAtTime(400, now);
                osc.frequency.exponentialRampToValueAtTime(150, now + 0.1);
                gain.gain.setValueAtTime(0.15, now);
                gain.gain.linearRampToValueAtTime(0, now + 0.1);
                osc.connect(gain);
                gain.connect(ctx.destination);
                osc.start(); osc.stop(now + 0.1);
            } else if (type === 'sent') {
                const osc = ctx.createOscillator();
                const gain = ctx.createGain();
                osc.type = 'triangle';
                osc.frequency.setValueAtTime(880, now);
                osc.frequency.exponentialRampToValueAtTime(1760, now + 0.05);
                gain.gain.setValueAtTime(0.1, now);
                gain.gain.exponentialRampToValueAtTime(0.01, now + 0.3);
                osc.connect(gain);
                gain.connect(ctx.destination);
                osc.start(); osc.stop(now + 0.3);
            } else if (type === 'received') {
                const osc = ctx.createOscillator();
                const gain = ctx.createGain();
                osc.type = 'sine';
                osc.frequency.setValueAtTime(220, now);
                osc.frequency.exponentialRampToValueAtTime(660, now + 0.1);
                gain.gain.setValueAtTime(0.1, now);
                gain.gain.exponentialRampToValueAtTime(0.01, now + 0.4);
                osc.connect(gain);
                gain.connect(ctx.destination);
                osc.start(); osc.stop(now + 0.4);
            }
        } catch (e) { console.warn('Audio synthesis failed', e); }
    };

    // --- SKELETON RENDERER ---
    function renderSkeletons(container, type, count = 5) {
        if (!container) return;
        container.innerHTML = '';
        for (let i = 0; i < count; i++) {
            if (type === 'contact') {
                const div = document.createElement('div');
                div.className = 'contact-item d-flex align-items-center opacity-50';
                div.innerHTML = `
                    <div class="skeleton skeleton-avatar me-3"></div>
                    <div class="flex-grow-1">
                        <div class="skeleton skeleton-text skeleton-text-lg w-75"></div>
                        <div class="skeleton skeleton-text skeleton-text-sm w-50"></div>
                    </div>
                `;
                container.appendChild(div);
            } else if (type === 'message') {
                const wrapper = document.createElement('div');
                wrapper.className = `message-wrapper ${i % 2 === 0 ? 'sent' : 'received'}`;
                wrapper.innerHTML = `
                    <div class="message-bubble skeleton" style="width: ${100 + Math.random() * 150}px; height: 45px;"></div>
                `;
                container.appendChild(wrapper);
            }
        }
    }

    // Toggle Profile Panel from Chat Header
    document.getElementById('chat-header-info')?.addEventListener('click', (e) => {
        // Don't trigger if clicking the back button
        if (e.target.closest('#mobile-back-btn')) return;

        const panel = document.getElementById('right-profile-panel');
        if (panel.classList.contains('d-none')) {
            panel.classList.remove('d-none');
            panel.classList.add('d-flex');
        } else {
            panel.classList.add('d-none');
            panel.classList.remove('d-flex');
        }
    });

    // Dynamic Theme Helper (Wow Factor)
    function applyDynamicTheme(name, imageUrl) {
        // Default to name-based signature
        const charCodeSum = (name || '').split('').reduce((acc, char) => acc + char.charCodeAt(0), 0);
        const colorIdx = charCodeSum % 6;

        // Reset theme classes on body
        document.body.classList.forEach(cls => {
            if (cls.startsWith('theme-grad-')) document.body.classList.remove(cls);
        });

        // If no image, just use the name-based class
        if (!imageUrl || imageUrl.includes('default.png')) {
            document.body.classList.add(`theme-grad-${colorIdx}`);
            return;
        }

        // Image-based Extraction logic
        const img = new Image();
        img.crossOrigin = "Anonymous";
        img.src = imageUrl;
        img.onload = function () {
            const canvas = document.createElement('canvas');
            const ctx = canvas.getContext('2d');
            canvas.width = 10;
            canvas.height = 10;
            ctx.drawImage(img, 0, 0, 10, 10);
            const data = ctx.getImageData(0, 0, 10, 10).data;

            let r = 0, g = 0, b = 0;
            for (let i = 0; i < data.length; i += 4) {
                r += data[i]; g += data[i + 1]; b += data[i + 2];
            }
            r = Math.floor(r / (data.length / 4));
            g = Math.floor(g / (data.length / 4));
            b = Math.floor(b / (data.length / 4));

            const primaryColor = `rgb(${r}, ${g}, ${b})`;
            // Boost saturation for secondary color
            const secondaryColor = `rgb(${Math.min(255, r + 60)}, ${Math.min(255, g + 60)}, ${Math.min(255, b + 60)})`;
            const gradient = `linear-gradient(135deg, ${primaryColor}, ${secondaryColor})`;

            document.documentElement.style.setProperty('--dynamic-sent-bg', gradient);
        };
        img.onerror = () => {
            document.body.classList.add(`theme-grad-${colorIdx}`);
        };
    }

    const messageInput = document.getElementById('message-input');
    const sendBtn = document.getElementById('send-btn');
    const micBtn = document.getElementById('mic-btn');
    const onceViewToggle = document.getElementById('once-view-toggle');
    const sidebar = document.getElementById('sidebar');
    const sidebarToggle = document.getElementById('sidebar-toggle');

    // Sidebar Collapse Logic
    if (sidebarToggle) {
        const toggleIcon = sidebarToggle.querySelector('i');

        sidebarToggle.addEventListener('click', () => {
            sidebar.classList.toggle('collapsed');
            const isCollapsed = sidebar.classList.contains('collapsed');
            localStorage.setItem('sidebar-collapsed', isCollapsed);

            // Swap icons: left when expanded, right when collapsed
            if (toggleIcon) {
                toggleIcon.className = isCollapsed ? 'bi bi-chevron-right' : 'bi bi-chevron-left';
            }
        });

        // Initialize from storage
        if (localStorage.getItem('sidebar-collapsed') === 'true') {
            sidebar.classList.add('collapsed');
            if (toggleIcon) toggleIcon.className = 'bi bi-chevron-right';
        } else if (toggleIcon) {
            toggleIcon.className = 'bi bi-chevron-left';
        }
    }

    const imageUpload = document.getElementById('image-upload');

    let activeUserId = null;
    let activeGroupId = null;
    let pollInterval = null;
    let lastMessagesData = '';
    let lastConversationsData = '';

    // Audio Recording
    let mediaRecorder;
    let audioChunks = [];
    let isRecording = false;
    let audioBlobToUpload = null;

    const emojiBtn = document.getElementById('emoji-btn');
    const emojiPicker = document.getElementById('emoji-picker');
    const emojiBackdrop = document.getElementById('emoji-backdrop');

    if (emojiBtn && emojiPicker) {
        emojiBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            const isOpen = !emojiPicker.classList.contains('d-none');
            if (isOpen) {
                emojiPicker.classList.add('d-none');
                emojiBackdrop?.classList.add('d-none');
                document.body.classList.remove('immersion-active');
            } else {
                emojiPicker.classList.remove('d-none');
                emojiBackdrop?.classList.remove('d-none');
                document.body.classList.add('immersion-active');
            }
        });

        // Event delegation for emoji selection
        emojiPicker.addEventListener('click', (e) => {
            if (e.target.tagName === 'SPAN') {
                const emoji = e.target.textContent;
                const start = messageInput.selectionStart;
                const end = messageInput.selectionEnd;
                const text = messageInput.value;
                messageInput.value = text.substring(0, start) + emoji + text.substring(end);
                messageInput.focus();

                // Close picker and backdrop after selection
                emojiPicker.classList.add('d-none');
                emojiBackdrop?.classList.add('d-none');
                document.body.classList.remove('immersion-active');

                // Set cursor position after emoji
                const newPos = start + emoji.length;
                messageInput.setSelectionRange(newPos, newPos);
            }
        });

        // Close picker when clicking anywhere else
        document.addEventListener('click', (e) => {
            if (!emojiPicker.contains(e.target) && e.target !== emojiBtn && !emojiBtn.contains(e.target)) {
                emojiPicker.classList.add('d-none');
                emojiBackdrop?.classList.add('d-none');
                document.body.classList.remove('immersion-active');
            }
        });
    }

    // Helper for Avatars (Initials fallback)
    function getAvatarHtml(name, imageUrl, sizeClass = '') {
        if (imageUrl && imageUrl !== 'assets/images/default.png') {
            return `<img src="${imageUrl}" class="avatar ${sizeClass}">`;
        }
        const initials = (name || '?').substring(0, 1).toUpperCase();
        const colors = ['#00f2ff', '#ff00ff', '#00ff66', '#ff9900', '#7700ff', '#ff3300'];
        const charCodeSum = (name || '').split('').reduce((acc, char) => acc + char.charCodeAt(0), 0);
        const color = colors[charCodeSum % colors.length];
        return `<div class="avatar-initials ${sizeClass}" style="background-color: ${color}">${initials}</div>`;
    }

    function getUsernameColor(name) {
        const colors = ['#00f2ff', '#ff00ff', '#00ff66', '#ff9900', '#7700ff', '#ff3300'];
        const charCodeSum = (name || '').split('').reduce((acc, char) => acc + char.charCodeAt(0), 0);
        return colors[charCodeSum % colors.length];
    }

    // Groups logic
    const createGroupModal = new bootstrap.Modal(document.getElementById('createGroupModal'));
    const addMemberModalEl = document.getElementById('addMemberModal');
    if (addMemberModalEl) {
        addMemberModalEl.addEventListener('shown.bs.modal', () => {
            document.getElementById('add-member-input')?.focus();
        });
    }
    const groupMembersList = document.getElementById('group-members-list');
    const btnCreateGroup = document.getElementById('btn-create-group');
    const newGroupName = document.getElementById('new-group-name');
    let allUsers = [];

    // Sidebar Filter Logic
    let currentFilter = 'all';
    document.querySelectorAll('.filter-chip').forEach(chip => {
        chip.addEventListener('click', () => {
            document.querySelectorAll('.filter-chip').forEach(c => {
                c.classList.remove('text-primary', 'border-primary');
                c.classList.add('text-muted');
            });
            chip.classList.remove('text-muted');
            chip.classList.add('text-primary', 'border-primary');
            currentFilter = chip.getAttribute('data-filter');
            lastConversationsData = ''; // Force re-render on filter change
            loadRecentConversations();
        });
    });

    // Sync Mobile Bottom Nav
    document.querySelectorAll('.mobile-bottom-nav .nav-item[data-filter]').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.mobile-bottom-nav .nav-item').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            currentFilter = btn.getAttribute('data-filter');
            // Sync with sidebar chips if visible
            document.querySelectorAll('.filter-chip').forEach(chip => {
                if (chip.getAttribute('data-filter') === currentFilter) {
                    chip.classList.add('active');
                    chip.classList.remove('text-muted');
                    chip.classList.add('text-primary', 'border-primary');
                } else {
                    chip.classList.remove('active', 'text-primary', 'border-primary');
                    chip.classList.add('text-muted');
                }
            });
            lastConversationsData = '';
            loadRecentConversations();
        });
    });

    document.getElementById('mobile-fab-btn')?.addEventListener('click', () => {
        const modal = new bootstrap.Modal(document.getElementById('createGroupModal'));
        modal.show();
    });

    document.getElementById('mobile-settings-btn')?.addEventListener('click', () => {
        const modal = new bootstrap.Modal(document.getElementById('profileModal'));
        modal.show();
    });

    activeName = document.getElementById('active-chat-name');

    // Load Recent Conversations (Sidebar default)
    function loadRecentConversations() {
        if (!contactList) return;

        // Show skeletons initially iff list is empty or has placeholder
        if (contactList.children.length === 0 || contactList.querySelector('.spinner-border-wrapper')) {
            renderSkeletons(contactList, 'contact');
        }

        fetch('controllers/MessageController.php?action=get_recent_conversations')
            .then(res => res.json())
            .then(res => {
                if (res.status === 'success') {
                    const currentData = JSON.stringify(res.data);
                    if (currentData === lastConversationsData) return; // Prevent redundant re-render
                    lastConversationsData = currentData;

                    contactList.innerHTML = '';
                    let renderedCount = 0;

                    res.data.forEach(chat => {
                        // Apply Filter
                        if (currentFilter !== 'all' && chat.type !== currentFilter) return;

                        const div = document.createElement('div');
                        div.className = 'contact-item' + (activeUserId == chat.target_id && chat.type == 'user' ? ' active' : '') + (activeGroupId == chat.target_id && chat.type == 'group' ? ' active' : '');

                        let name = chat.type === 'group' ? chat.group_name : chat.user_name;
                        const isOnlineStatus = chat.type === 'user' ? isOnline(chat.last_active) : false;

                        let imgHtml = getAvatarHtml(name, chat.type === 'group' ? chat.group_image : chat.user_image);
                        let img = `
                            <div class="avatar-wrapper">
                                ${imgHtml}
                                ${chat.type === 'user' ? `<span class="status-dot ${isOnlineStatus ? 'online' : 'offline'}"></span>` : ''}
                            </div>
                        `;

                        let statusHtml = '';
                        let unreadHtml = '';
                        if (chat.unread_count > 0 && chat.sender_id != window.CURRENT_USER_ID) {
                            unreadHtml = `<span class="unread-badge">${chat.unread_count}</span>`;
                        }

                        let lastSeenStr = '';
                        if (chat.type === 'user') {
                            const isOnlineStatus = isOnline(chat.last_active);
                            if (!isOnlineStatus && chat.last_active) {
                                const d = new Date(chat.last_active);
                                lastSeenStr = 'last seen at ' + d.getHours().toString().padStart(2, '0') + ':' + d.getMinutes().toString().padStart(2, '0');
                            } else {
                                lastSeenStr = isOnlineStatus ? 'Online' : 'Offline';
                            }

                            let tickHtml = '';
                            if (chat.sender_id == window.CURRENT_USER_ID && chat.message_id) {
                                tickHtml = chat.status === 'viewed' ? '<i class="bi bi-check-all text-info me-1"></i>' : '<i class="bi bi-check me-1"></i>';
                            }

                            statusHtml = `<span class="contact-status ${isOnlineStatus ? 'text-success' : 'text-muted'}">${tickHtml}${chat.last_message || lastSeenStr}</span>`;
                        } else {
                            let groupStatus = chat.last_message || 'No messages yet';
                            if (chat.online_count > 0) {
                                groupStatus = `<span class="text-success fw-bold">${chat.online_count} online</span>`;
                            }
                            statusHtml = `<span class="contact-status text-info">${groupStatus}</span>`;
                        }

                        div.innerHTML = `
                            ${img}
                            <div class="contact-info">
                                <h6 class="contact-name d-flex align-items-center m-0">${name || 'Unknown'} ${unreadHtml}</h6>
                                ${statusHtml}
                            </div>
                        `;

                        const targetObj = chat.type === 'group'
                            ? { id: chat.target_id, name: name || 'Unknown Group', group_image: chat.group_image || 'assets/images/default.png', online_count: chat.online_count }
                            : { id: chat.target_id, username: name || 'Unknown User', profile_image: chat.user_image || 'assets/images/default.png', last_active: chat.last_active, about: chat.user_about };

                        div.onclick = () => selectContact(chat.type === 'user' ? targetObj : null, chat.type === 'group' ? targetObj : null, div);

                        contactList.appendChild(div);
                        renderedCount++;
                    });

                    if (renderedCount === 0) {
                        if (currentFilter === 'group') contactList.innerHTML = '<div class="text-muted small p-3 text-center">No groups joined</div>';
                        else if (currentFilter === 'user') contactList.innerHTML = '<div class="text-muted small p-3 text-center">No direct messages</div>';
                        else contactList.innerHTML = '<div class="text-muted small p-3 text-center">No conversations yet</div>';
                    }
                }
            });
    }


    // Load Users for Group Creation (Only from users you have messaged)
    function loadUsersForGroup() {
        groupMembersList.innerHTML = '<div class="text-center p-3 text-muted"><div class="spinner-border spinner-border-sm text-info me-2"></div>Loading contacts...</div>';

        fetch('controllers/MessageController.php?action=get_recent_conversations')
            .then(res => res.json())
            .then(res => {
                if (res.status === 'success') {
                    groupMembersList.innerHTML = '';
                    const userChats = res.data.filter(c => c.type === 'user');

                    if (userChats.length === 0) {
                        groupMembersList.innerHTML = '<div class="text-muted small p-3 text-center">No chat history found. You can only add users you have messaged with.</div>';
                        return;
                    }

                    userChats.forEach(chat => {
                        const memberCheckbox = document.createElement('div');
                        memberCheckbox.className = 'form-check mb-2 d-flex align-items-center';
                        memberCheckbox.innerHTML = `
                            <input class="form-check-input group-member-checkbox me-2" type="checkbox" value="${chat.target_id}" id="user-sel-${chat.target_id}">
                            <label class="form-check-label d-flex align-items-center cursor-pointer" for="user-sel-${chat.target_id}">
                                ${getAvatarHtml(chat.user_name, chat.user_image, 'sm')}
                                <span class="ms-1">${chat.user_name}</span>
                            </label>
                        `;
                        groupMembersList.appendChild(memberCheckbox);
                    });
                }
            })
            .catch(e => {
                console.error("Error populating contact list for group", e);
                groupMembersList.innerHTML = '<div class="text-danger small p-3 text-center">Failed to load contacts.</div>';
            });
    }

    // Attach Modal listener
    const createGroupModalEl = document.getElementById('createGroupModal');
    if (createGroupModalEl) {
        createGroupModalEl.addEventListener('show.bs.modal', loadUsersForGroup);
    }

    // Server Ping
    function pingServer() {
        fetch('controllers/ProfileController.php?action=ping');
    }
    setInterval(pingServer, 30000);
    pingServer(); // Initally ping

    // Create Group Action
    btnCreateGroup.addEventListener('click', () => {
        const name = newGroupName.value.trim();
        if (!name) return alert('Group name required');

        const selectedMembers = Array.from(document.querySelectorAll('.group-member-checkbox:checked')).map(cb => cb.value);

        const fd = new FormData();
        fd.append('action', 'create_group');
        fd.append('name', name);
        selectedMembers.forEach(id => fd.append('members[]', id));

        fetch('controllers/GroupController.php', { method: 'POST', body: fd })
            .then(res => res.json())
            .then(res => {
                if (res.status === 'success') {
                    createGroupModal.hide();
                    newGroupName.value = '';
                    document.querySelectorAll('.group-member-checkbox').forEach(cb => cb.checked = false);
                    if (window.showAppToast) window.showAppToast('Group created successfully!', 'success');
                    contactList.innerHTML = '<div class="p-3 text-center"><div class="spinner-border text-info"></div></div>';
                    loadRecentConversations();
                } else {
                    if (window.showAppToast) window.showAppToast(res.message, 'danger');
                    else alert(res.message);
                }
            });
    });

    // Select Contact
    function selectContact(user, group, element) {
        if (element) {
            document.querySelectorAll('.contact-item').forEach(el => el.classList.remove('active'));
            element.classList.add('active');
            window.playZenithSound('pop');
        }

        if (user) {
            activeUserId = user.id;
            activeGroupId = null;
            window.ACTIVE_USER_PROFILE_IMAGE = user.profile_image;

            const avatarHtml = getAvatarHtml(user.username, user.profile_image);
            const avatarContainer = document.getElementById('active-chat-avatar-container');
            if (avatarContainer) avatarContainer.innerHTML = avatarHtml;
            activeAvatar = document.getElementById('active-chat-avatar');

            activeName.textContent = user.username;
            window.ACTIVE_USER_ABOUT = user.about;
            window.ACTIVE_USER_LAST_ACTIVE = user.last_active;

            // Check Online Status
            const isOnlineStatus = isOnline(user.last_active);
            const activeStatusDot = document.getElementById('active-chat-status-dot');
            if (activeStatusDot) {
                activeStatusDot.classList.remove('d-none');
                activeStatusDot.className = 'status-dot me-2 ' + (isOnlineStatus ? 'online' : 'offline');
            }

            let statusText = isOnlineStatus ? 'Online' : 'Offline';
            if (!isOnlineStatus && user.last_active) {
                const d = new Date(user.last_active);
                statusText = 'last seen at ' + d.getHours().toString().padStart(2, '0') + ':' + d.getMinutes().toString().padStart(2, '0');
            }

            document.getElementById('active-chat-status').textContent = statusText;
            document.getElementById('active-chat-status').className = 'contact-status ' + (isOnlineStatus ? 'text-success' : 'text-muted');
        } else if (group) {
            activeUserId = null;
            activeGroupId = group.id;
            window.ACTIVE_GROUP_IMAGE = group.group_image;

            const avatarHtml = getAvatarHtml(group.name, group.group_image);
            const avatarContainer = document.getElementById('active-chat-avatar-container');
            if (avatarContainer) avatarContainer.innerHTML = avatarHtml;
            activeAvatar = document.getElementById('active-chat-avatar');

            activeName.textContent = group.name || 'Unknown Group';
            const activeStatusDot = document.getElementById('active-chat-status-dot');
            if (activeStatusDot) activeStatusDot.classList.add('d-none');

            let groupStatus = 'Group Chat';
            if (group.online_count > 0) {
                groupStatus = group.online_count + ' online';
            }
            document.getElementById('active-chat-status').textContent = groupStatus;
            document.getElementById('active-chat-status').className = 'contact-status ' + (group.online_count > 0 ? 'text-success fw-bold' : 'text-info');
        }

        // Apply Dynamic Theme (Image-based extraction or Name-based fallback)
        applyDynamicTheme(user ? user.username : group.name, user ? user.profile_image : group.group_image);

        // Update Profile Panel (Right Panel)
        const joinedContainer = document.getElementById('profile-panel-joined-container');
        if (joinedContainer) {
            const rawDate = (user ? (user.user_created_at || user.created_at) : (group.group_created_at || group.created_at));
            if (rawDate) {
                const joinedDate = new Date(rawDate);
                document.getElementById('profile-panel-joined-text').textContent = 'Joined ' + joinedDate.toLocaleDateString([], { month: 'long', year: 'numeric' });
                joinedContainer.classList.remove('d-none');
            } else {
                joinedContainer.classList.add('d-none');
            }
        }

        // Show chat UI
        emptyChatState.classList.add('d-none');
        chatHeader.classList.remove('d-none');
        chatInputArea.classList.remove('d-none');

        // Mobile view handling
        showChat();

        // Setup Chat refresh
        loadMessages();
        if (pollInterval) clearInterval(pollInterval);
        pollInterval = setInterval(() => {
            loadMessages();
            loadRecentConversations(); // Keep sidebar updated
            pingServerAndCheckTyping();
            updateProfilePanelLive();
        }, 2000); // 2 second AJAX polling

        // Auto-focus input and scroll to latest
        setTimeout(() => {
            messageInput.focus();
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }, 100);
    }
    window.selectContactGlobal = selectContact;

    // Load Messages for Active Chat
    function loadMessages() {
        if (!activeUserId && !activeGroupId) return;

        const targetUrl = activeGroupId
            ? `controllers/MessageController.php?action=fetch_messages&group_id=${activeGroupId}`
            : `controllers/MessageController.php?action=fetch_messages&receiver_id=${activeUserId}`;

        fetch(targetUrl)
            .then(res => res.json())
            .then(res => {
                if (res.status === 'success') {
                    const currentData = JSON.stringify(res.data);
                    if (currentData === lastMessagesData) return; // Abort DOM destruction if no changes
                    lastMessagesData = currentData;

                    // Check scroll state
                    const isAtBottom = chatMessages.scrollHeight - chatMessages.scrollTop <= chatMessages.clientHeight + 50;
                    const oldScrollTop = chatMessages.scrollTop;

                    chatMessages.innerHTML = '';
                    let lastDateString = null;

                    res.data.forEach(msg => {
                        const msgDate = new Date(msg.created_at);
                        const dateString = msgDate.toLocaleDateString([], { dateStyle: 'long' });

                        if (dateString !== lastDateString) {
                            const dateDivider = document.createElement('div');
                            dateDivider.className = 'date-divider';

                            // Humanize "Today" and "Yesterday"
                            const today = new Date().toLocaleDateString([], { dateStyle: 'long' });
                            const yesterdayDate = new Date();
                            yesterdayDate.setDate(yesterdayDate.getDate() - 1);
                            const yesterday = yesterdayDate.toLocaleDateString([], { dateStyle: 'long' });

                            let displayDate = dateString;
                            if (dateString === today) displayDate = 'Today';
                            else if (dateString === yesterday) displayDate = 'Yesterday';

                            dateDivider.innerHTML = `<span class="date-pill">${displayDate}</span>`;
                            chatMessages.appendChild(dateDivider);
                            lastDateString = dateString;
                        }
                        // For groups, sender_id is not me, but it could be anyone else
                        // For direct msgs, if sender_id == me, it's sent.
                        const isSent = msg.sender_id == window.CURRENT_USER_ID;

                        let additionalContent = '';
                        if (msg.image_path) {
                            if (msg.once_view == 1) {
                                if (msg.viewed == 1 && !isSent) {
                                    additionalContent = `<div class="text-muted fst-italic"><i class="bi bi-eye-slash"></i> Image viewed</div>`;
                                } else if (isSent) {
                                    additionalContent = `<div class="view-once-pill mt-2" style="font-size: 0.85rem;"><i class="bi bi-shield-lock-fill"></i> View Once Photo</div>`;
                                } else {
                                    additionalContent = `<div class="position-relative mt-2">
                                        <span class="once-view-badge">1x</span>
                                        <button class="btn btn-sm btn-outline-info" onclick="viewOnceImage(this, ${msg.id}, '${msg.image_path}')">
                                            <i class="bi bi-image"></i> View Image
                                        </button>
                                    </div>`;
                                }
                            } else {
                                const activeName = activeUserId ? document.getElementById('active-chat-name').innerText : msg.sender_name;
                                const senderNameStr = isSent ? 'You' : activeName;
                                const fullDate = new Date(msg.created_at).toLocaleString([], { dateStyle: 'medium', timeStyle: 'short' });
                                additionalContent = `<img src="${msg.image_path}" class="img-msg gallery-img mt-2" onclick="openLightbox(this)" data-sender="${senderNameStr}" data-time="${fullDate}">`;
                            }
                        }
                        if (msg.audio_path) {
                            additionalContent += renderCustomAudioPlayer(msg.audio_path);
                        }

                        const msgWrapper = document.createElement('div');
                        msgWrapper.className = `message-wrapper ${isSent ? 'sent' : 'received'}`;

                        if (!isSent) {
                            const pfpWrapper = document.createElement('div');
                            pfpWrapper.className = 'msg-avatar-container';
                            pfpWrapper.innerHTML = getAvatarHtml(msg.sender_name, msg.profile_image, 'xs');
                            msgWrapper.appendChild(pfpWrapper);
                        }

                        const msgDiv = document.createElement('div');
                        msgDiv.className = `message-bubble ${isSent ? 'message-sent' : 'message-received'}`;
                        const time = new Date(msg.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });

                        const tickHtml = (isSent && !activeGroupId) ? (msg.status === 'viewed' ? '<i class="bi bi-check-all status-tick tick-viewed"></i>' : '<i class="bi bi-check status-tick tick-sent"></i>') : '';

                        const nameColor = getUsernameColor(msg.sender_name);
                        const senderNameDisplay = (!isSent && activeGroupId) ? `<div class="fw-bold" style="font-size:0.75rem; color: ${nameColor}; margin-bottom: 2px;">${msg.sender_name}</div>` : '';

                        msgDiv.innerHTML = `
                            ${senderNameDisplay}
                            ${msg.message ? `<div>${msg.message}</div>` : ''}
                            ${additionalContent}
                            <span class="timestamp">${time} ${tickHtml}</span>
                        `;
                        msgWrapper.appendChild(msgDiv);
                        chatMessages.appendChild(msgWrapper);
                    });

                    if (isAtBottom) {
                        chatMessages.scrollTop = chatMessages.scrollHeight;
                    } else {
                        chatMessages.scrollTop = oldScrollTop;
                    }

                    // Play sound if new message arrived and it's not from me
                    if (res.data.length > 0) {
                        const lastMsg = res.data[res.data.length - 1];
                        if (lastMsg.sender_id != window.CURRENT_USER_ID && currentData !== lastMessagesData) {
                            window.playZenithSound('received');
                        }
                    }
                }
            })
            .catch(err => console.error(err));
    }

    // Audio Recording Logic
    let recordingTimerInterval = null;
    let recordingStartTime = 0;
    let audioContext, analyser, dataArray, source, animationId;

    function startRecording() {
        navigator.mediaDevices.getUserMedia({ audio: true })
            .then(stream => {
                mediaRecorder = new MediaRecorder(stream);
                audioChunks = [];
                isRecording = true;

                // Visualizer Setup: Ensure Context Resumes
                audioContext = new (window.AudioContext || window.webkitAudioContext)();
                if (audioContext.state === 'suspended') audioContext.resume();

                analyser = audioContext.createAnalyser();
                source = audioContext.createMediaStreamSource(stream);
                source.connect(analyser);
                analyser.fftSize = 64;
                const bufferLength = analyser.frequencyBinCount;
                dataArray = new Uint8Array(bufferLength);

                drawVisualizer();

                mediaRecorder.ondataavailable = e => {
                    audioChunks.push(e.data);
                };

                mediaRecorder.onstop = () => {
                    if (isRecording === false) { // Was discarded
                        audioBlobToUpload = null;
                    } else {
                        audioBlobToUpload = new Blob(audioChunks, { type: 'audio/webm' });
                        sendMessage(); // Auto-send on stop
                    }
                    isRecording = false;
                    // Stop tracks
                    stream.getTracks().forEach(track => track.stop());
                    if (audioContext) audioContext.close();
                    cancelAnimationFrame(animationId);
                };

                mediaRecorder.start();
                showRecordingUI();
            })
            .catch(err => {
                console.error("Microphone access denied", err);
                if (window.showAppToast) window.showAppToast("Mic access denied!", "danger");
            });
    }

    function drawVisualizer() {
        const canvas = document.getElementById('recording-visualizer');
        if (!canvas) return;
        const ctx = canvas.getContext('2d');

        const renderFrame = () => {
            animationId = requestAnimationFrame(renderFrame);
            analyser.getByteFrequencyData(dataArray);

            const width = canvas.width = canvas.offsetWidth;
            const height = canvas.height = canvas.offsetHeight;
            ctx.clearRect(0, 0, width, height);

            // Draw 3 layers of waves with different opacities and phases
            drawWaveLayer(ctx, width, height, 1, 'rgba(255, 255, 255, 0.8)', 1.5);
            drawWaveLayer(ctx, width, height, 0.7, 'rgba(255, 255, 255, 0.4)', 2.5);
            drawWaveLayer(ctx, width, height, 1.3, 'rgba(255, 255, 255, 0.2)', 0.8);
        };
        renderFrame();
    }

    function drawWaveLayer(ctx, width, height, phaseShift, color, amplitudeMult) {
        ctx.beginPath();
        ctx.lineWidth = 1.5;
        ctx.strokeStyle = color;

        const midY = height / 2;
        const avgFreq = Array.from(dataArray).reduce((a, b) => a + b, 0) / dataArray.length;
        const amplitude = (avgFreq / 255) * height * amplitudeMult;

        for (let x = 0; x <= width; x += 3) {
            const relativeX = x / width;
            // Smooth sine wave with time-based phase
            const phase = (Date.now() / 200) * phaseShift;
            const y = midY + amplitude * Math.sin(relativeX * Math.PI * 2 + phase) * Math.sin(relativeX * Math.PI);

            if (x === 0) ctx.moveTo(x, y);
            else ctx.lineTo(x, y);
        }
        ctx.stroke();
    }

    function stopRecording(discard = false) {
        if (!mediaRecorder || mediaRecorder.state !== 'recording') return;
        if (discard) isRecording = false;
        mediaRecorder.stop();
        hideRecordingUI();
    }

    function showRecordingUI() {
        document.getElementById('recording-overlay').classList.remove('d-none');
        document.getElementById('emoji-backdrop')?.classList.remove('d-none');
        document.body.classList.add('immersion-active');
        recordingStartTime = Date.now();
        document.getElementById('recording-timer').textContent = '00:00';
        recordingTimerInterval = setInterval(() => {
            const elapsed = Math.floor((Date.now() - recordingStartTime) / 1000);
            const m = Math.floor(elapsed / 60).toString().padStart(2, '0');
            const s = (elapsed % 60).toString().padStart(2, '0');
            document.getElementById('recording-timer').textContent = `${m}:${s}`;
        }, 1000);
    }

    function hideRecordingUI() {
        document.getElementById('recording-overlay').classList.add('d-none');
        document.getElementById('emoji-backdrop')?.classList.add('d-none');
        document.body.classList.remove('immersion-active');
        clearInterval(recordingTimerInterval);
    }

    micBtn?.addEventListener('click', () => {
        if (!isRecording) startRecording();
    });

    document.getElementById('stop-send-record-btn')?.addEventListener('click', () => {
        stopRecording(false);
    });

    document.getElementById('discard-recording-btn')?.addEventListener('click', (e) => {
        e.stopPropagation();
        stopRecording(true);
    });

    // Custom Audio Player v2 Renderer: Scrubber & Dual Timer
    function renderCustomAudioPlayer(audioPath) {
        const uniqueId = 'audio-' + Math.random().toString(36).substr(2, 9);
        setTimeout(() => drawStaticWaveform(`${uniqueId}-canvas`), 50);
        return `
            <div class="custom-audio-player" id="${uniqueId}-container">
                <div class="player-main-controls">
                    <button class="play-pause-btn" onclick="toggleCustomAudio('${audioPath}', '${uniqueId}', this)">
                        <i class="bi bi-play-fill" id="${uniqueId}-icon"></i>
                    </button>
                    <div class="flex-grow-1 d-flex flex-column gap-1">
                        <canvas id="${uniqueId}-canvas" width="120" height="20" class="opacity-50"></canvas>
                        <input type="range" class="audio-scrubber" id="${uniqueId}-scrubber" value="0" step="0.1" min="0" oninput="handleAudioScrub('${uniqueId}')">
                    </div>
                </div>
                <div class="audio-info-row">
                    <span id="${uniqueId}-current">00:00</span>
                    <span id="${uniqueId}-total">00:00</span>
                </div>
                <audio id="${uniqueId}-element" src="${audioPath}" 
                    onloadedmetadata="initAudioMetadata('${uniqueId}')"
                    ontimeupdate="updateAudioTimer('${uniqueId}')" 
                    onended="resetAudioPlayer('${uniqueId}')"></audio>
            </div>
        `;
    }

    function drawStaticWaveform(canvasId) {
        const canvas = document.getElementById(canvasId);
        if (!canvas) return;
        const ctx = canvas.getContext('2d');
        const w = canvas.width;
        const h = canvas.height;
        ctx.fillStyle = 'rgba(255, 255, 255, 0.5)';
        for (let i = 0; i < 40; i++) {
            const bh = 3 + Math.random() * (h - 3);
            const bx = i * (w / 40);
            const by = (h - bh) / 2;
            ctx.beginPath();
            ctx.roundRect(bx, by, 1.5, bh, 1);
            ctx.fill();
        }
    }

    window.initAudioMetadata = function (id) {
        const audio = document.getElementById(id + '-element');
        const total = document.getElementById(id + '-total');
        const scrubber = document.getElementById(id + '-scrubber');
        if (audio.duration && audio.duration !== Infinity) {
            const m = Math.floor(audio.duration / 60).toString().padStart(2, '0');
            const s = Math.floor(audio.duration % 60).toString().padStart(2, '0');
            total.textContent = `${m}:${s}`;
            scrubber.max = audio.duration;
        }
    };

    window.toggleCustomAudio = function (path, id, btn) {
        const audio = document.getElementById(id + '-element');
        const icon = document.getElementById(id + '-icon');
        const backdrop = document.getElementById('emoji-backdrop');
        const container = document.getElementById(id + '-container').closest('.message');

        if (audio.paused) {
            // Stop all other audios
            document.querySelectorAll('audio').forEach(el => {
                if (el !== audio) {
                    el.pause();
                    // Find icons for other players and reset them
                    const otherId = el.id.replace('-element', '');
                    const otherIcon = document.getElementById(otherId + '-icon');
                    if (otherIcon) otherIcon.className = 'bi bi-play-fill';
                    const otherContainer = document.getElementById(otherId + '-container')?.closest('.message');
                    otherContainer?.classList.remove('message-focus-active');
                }
            });

            audio.play();
            icon.className = 'bi bi-pause-fill';
            backdrop?.classList.remove('d-none');
            container?.classList.add('message-focus-active');
            document.body.classList.add('immersion-active');
        } else {
            audio.pause();
            icon.className = 'bi bi-play-fill';
            backdrop?.classList.add('d-none');
            container?.classList.remove('message-focus-active');
            document.body.classList.remove('immersion-active');
        }
    };

    window.handleAudioScrub = function (id) {
        const audio = document.getElementById(id + '-element');
        const scrubber = document.getElementById(id + '-scrubber');
        audio.currentTime = scrubber.value;
    };

    window.updateAudioTimer = function (id) {
        const audio = document.getElementById(id + '-element');
        const current = document.getElementById(id + '-current');
        const scrubber = document.getElementById(id + '-scrubber');

        const elapsed = Math.floor(audio.currentTime);
        const m = Math.floor(elapsed / 60).toString().padStart(2, '0');
        const s = (elapsed % 60).toString().padStart(2, '0');
        current.textContent = `${m}:${s}`;
        scrubber.value = audio.currentTime;

        // If total is 00:00, try to update it (some browsers load it late)
        const total = document.getElementById(id + '-total');
        if (total.textContent === '00:00' && audio.duration && audio.duration !== Infinity) {
            window.initAudioMetadata(id);
        }
    };

    window.resetAudioPlayer = function (id) {
        const icon = document.getElementById(id + '-icon');
        const backdrop = document.getElementById('emoji-backdrop');
        const container = document.getElementById(id + '-container').closest('.message');
        icon.className = 'bi bi-play-fill';
        backdrop?.classList.add('d-none');
        container?.classList.remove('message-focus-active');
        document.body.classList.remove('immersion-active');
    };

    // Send Message
    function sendMessage() {
        const text = messageInput.value.trim();
        const files = Array.from(imageUpload.files);

        if (!text && files.length === 0 && !audioBlobToUpload) return;

        if (onceViewToggle.checked && files.length > 1) {
            alert("You can only send one 'View Once' image at a time.");
            imageUpload.value = '';
            return;
        }

        let uploadPromises = [];

        if (files.length > 0) {
            files.forEach((file, index) => {
                const fd = new FormData();
                fd.append('action', 'send_message');
                if (activeUserId) fd.append('receiver_id', activeUserId);
                if (activeGroupId) fd.append('group_id', activeGroupId);
                fd.append('once_view', onceViewToggle.checked);
                fd.append('image', file);

                // Attach text message to the first image
                if (index === 0 && text) {
                    fd.append('message', text);
                } else {
                    fd.append('message', '');
                }

                uploadPromises.push(fetch('controllers/MessageController.php', { method: 'POST', body: fd }));
            });
        }

        // If no files, but text or audio exists
        if (files.length === 0 && (text || audioBlobToUpload)) {
            const formData = new FormData();
            formData.append('action', 'send_message');
            if (activeUserId) formData.append('receiver_id', activeUserId);
            if (activeGroupId) formData.append('group_id', activeGroupId);
            formData.append('message', text);
            formData.append('once_view', onceViewToggle.checked);

            if (audioBlobToUpload) {
                formData.append('audio', audioBlobToUpload, 'audio_message.webm');
            }

            uploadPromises.push(fetch('controllers/MessageController.php', { method: 'POST', body: formData }));
        }

        window.playZenithSound('sent');

        Promise.all(uploadPromises).then(() => {
            messageInput.value = '';
            imageUpload.value = ''; // Reset file input
            audioBlobToUpload = null; // Reset audio
            lastMessagesData = ''; // Force data redraw
            loadMessages();
            loadRecentConversations(); // Update sidebar immediately
            setTimeout(() => chatMessages.scrollTop = chatMessages.scrollHeight, 100);
        }).catch(err => console.error("Error sending message:", err));
    }

    sendBtn.addEventListener('click', sendMessage);
    messageInput.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') sendMessage();
    });

    imageUpload.addEventListener('change', () => {
        if (imageUpload.files.length > 0) {
            // Send image directly when user selects it
            sendMessage();
        }
    });



    // Mobile Back Button
    document.getElementById('mobile-back-btn')?.addEventListener('click', (e) => {
        e.stopPropagation();
        showSidebar();
    });

    // Close Profile Panel Logic
    document.getElementById('close-profile-btn')?.addEventListener('click', () => {
        const panel = document.getElementById('right-profile-panel');
        panel.classList.add('d-none');
        panel.classList.remove('d-flex');
    });

    // User Search filter via API
    document.getElementById('user-search')?.addEventListener('input', (e) => {
        const term = e.target.value.trim();
        if (term === '') {
            lastConversationsData = ''; // Force background refresh to redraw sidebar
            loadRecentConversations();
            return;
        }

        // Search API
        fetch('controllers/MessageController.php?action=search_users&term=' + encodeURIComponent(term))
            .then(res => res.json())
            .then(res => {
                if (res.status === 'success') {
                    contactList.innerHTML = '<div class="p-2 small text-muted">Global Search Results</div>';
                    res.data.forEach(user => {
                        const div = document.createElement('div');
                        div.className = 'contact-item';
                        div.innerHTML = `
                            ${getAvatarHtml(user.username, user.profile_image)}
                            <div class="contact-info">
                                <h6 class="contact-name">${user.username}</h6>
                                <span class="contact-status text-muted">${user.about || 'Hey there! I am using ChatUs.'}</span>
                            </div>
                        `;
                        div.onclick = () => selectContact(user, null, div);
                        contactList.appendChild(div);
                    });
                }
            });
    });

    // Profile Settings
    const profileModal = document.getElementById('profileModal');
    profileModal?.addEventListener('show.bs.modal', () => {
        fetch('controllers/ProfileController.php?action=get_my_profile')
            .then(res => res.json())
            .then(res => {
                if (res.status === 'success') {
                    const imgContainer = document.getElementById('profile-modal-img-container');
                    // Use 'lg' size for a nice large modal avatar
                    imgContainer.innerHTML = getAvatarHtml(res.data.username, res.data.profile_image, 'lg');

                    // Constrain the 240px 'lg' avatar to 160px for the modal
                    const newImg = imgContainer.querySelector('.avatar, .avatar-initials');
                    if (newImg) {
                        newImg.id = 'profile-modal-img';
                        newImg.style.width = '160px';
                        newImg.style.height = '160px';
                        if (newImg.classList.contains('avatar-initials')) {
                            newImg.style.fontSize = '4rem';
                        }
                    }

                    document.getElementById('profile-about-input').value = res.data.about || '';
                }
            });
    });

    document.getElementById('btn-remove-pfp')?.addEventListener('click', () => {
        window.appConfirm('Are you sure you want to remove your profile picture?', () => {
            const fd = new FormData();
            fd.append('action', 'remove_pfp');

            fetch('controllers/ProfileController.php', { method: 'POST', body: fd })
                .then(res => res.json())
                .then(res => {
                    if (res.status === 'success') {
                        // Update modal UI immediately
                        const imgContainer = document.getElementById('profile-modal-img-container');
                        imgContainer.innerHTML = getAvatarHtml(window.CURRENT_USERNAME || 'Me', null, 'lg');
                        const newImg = imgContainer.querySelector('.avatar, .avatar-initials');
                        if (newImg) {
                            newImg.style.width = '160px';
                            newImg.style.height = '160px';
                            newImg.style.fontSize = '4rem';
                        }
                        loadRecentConversations();
                    } else {
                        alert(res.message);
                    }
                });
        });
    });

    document.getElementById('btn-save-profile')?.addEventListener('click', () => {
        const about = document.getElementById('profile-about-input').value;
        const img = document.getElementById('profile-img-upload').files[0];

        const fd = new FormData();
        fd.append('action', 'update_profile');
        fd.append('about', about);
        if (img) fd.append('profile_image', img);

        const btn = document.getElementById('btn-save-profile');
        const originalText = btn.textContent;
        btn.textContent = 'Saving...';
        btn.disabled = true;

        fetch('controllers/ProfileController.php', { method: 'POST', body: fd })
            .then(res => res.json())
            .then(res => {
                btn.textContent = originalText;
                btn.disabled = false;
                if (res.status === 'success') {
                    bootstrap.Modal.getInstance(profileModal).hide();
                    loadRecentConversations(); // Reload sidebar
                    if (window.showAppToast) window.showAppToast('Profile updated successfully!', 'success');
                    // Update global state if needed
                    if (about) window.ACTIVE_USER_ABOUT = about;
                    // Clear file input
                    document.getElementById('profile-img-upload').value = '';
                } else {
                    if (window.showAppToast) window.showAppToast(res.message, 'danger');
                    else alert(res.message);
                }
            });
    });

    loadRecentConversations();
    loadUsersForGroup();

    // Typing Logic
    let typingTimeout;
    messageInput.addEventListener('input', () => {
        if (activeUserId) {
            const fd = new FormData();
            fd.append('action', 'ping');
            fd.append('typing_to', activeUserId);
            fetch('controllers/ProfileController.php', { method: 'POST', body: fd });

            clearTimeout(typingTimeout);
            typingTimeout = setTimeout(() => {
                const fdClear = new FormData();
                fdClear.append('action', 'ping');
                fetch('controllers/ProfileController.php', { method: 'POST', body: fdClear });
            }, 2000);
        }
    });

    function pingServerAndCheckTyping() {
        if (!activeUserId) return;
        const fd = new FormData();
        fd.append('action', 'ping');
        fd.append('active_user_id', activeUserId);

        fetch('controllers/ProfileController.php', { method: 'POST', body: fd })
            .then(res => res.json())
            .then(res => {
                if (res.status === 'success' && res.data) {
                    window.ACTIVE_USER_LAST_ACTIVE = res.data.last_active;
                    const isOnlineStatus = isOnline(res.data.last_active);

                    const activeStatusDot = document.getElementById('active-chat-status-dot');
                    if (activeStatusDot) {
                        activeStatusDot.classList.remove('d-none');
                        activeStatusDot.className = 'status-dot me-2 ' + (isOnlineStatus ? 'online' : 'offline');
                    }

                    if (res.data.is_typing_to == window.CURRENT_USER_ID) {
                        document.getElementById('active-chat-status').innerHTML = `<span class="typing-indicator">typing...</span>`;
                    } else {
                        let statusText = isOnlineStatus ? 'Online' : 'Offline';
                        if (!isOnlineStatus && res.data.last_active) {
                            const d = new Date(res.data.last_active);
                            statusText = 'last seen at ' + d.getHours().toString().padStart(2, '0') + ':' + d.getMinutes().toString().padStart(2, '0');
                        }
                        document.getElementById('active-chat-status').textContent = statusText;
                        document.getElementById('active-chat-status').className = 'contact-status ' + (isOnlineStatus ? 'text-success' : 'text-muted');
                    }
                }
            });
    }


    function fetchGroupsInCommon() {
        const section = document.getElementById('profile-panel-common-groups-section');
        const list = document.getElementById('profile-panel-common-groups-list');
        if (!section || !list) return;

        section.classList.add('d-none');
        list.innerHTML = '';

        if (!activeUserId) return;

        fetch('controllers/GroupController.php?action=get_groups_in_common&other_user_id=' + activeUserId)
            .then(res => res.json())
            .then(res => {
                if (res.status === 'success' && res.data.length > 0) {
                    section.classList.remove('d-none');
                    res.data.forEach(group => {
                        const div = document.createElement('div');
                        div.className = 'member-item d-flex align-items-center p-2 rounded border border-secondary border-opacity-10 cursor-pointer hover-bg-dim';
                        div.style.background = 'rgba(255,255,255,0.03)';
                        div.onclick = () => {
                            selectContact(null, { id: group.id, name: group.name, group_image: group.group_image }, div);
                            rightProfilePanel.classList.add('d-none');
                        };

                        div.innerHTML = `
                            ${getAvatarHtml(group.name, group.group_image, 'sm')}
                            <div class="ms-3 flex-grow-1 overflow-hidden">
                                <div class="fw-bold small text-truncate" style="color: var(--text-color);">${group.name}</div>
                            </div>
                            <i class="bi bi-chevron-right text-muted small"></i>
                        `;
                        list.appendChild(div);
                    });
                }
            });
    }

    // Right Profile Panel Click Handler
    const rightProfilePanel = document.getElementById('right-profile-panel');
    document.getElementById('chat-header-info')?.addEventListener('click', () => {
        if (!activeUserId && !activeGroupId) return;

        rightProfilePanel.classList.remove('d-none');
        rightProfilePanel.classList.add('d-flex');

        const panelImgContainer = document.getElementById('profile-panel-img-container');
        const panelName = document.getElementById('profile-panel-name');
        const panelSub = document.getElementById('profile-panel-sub');
        const panelAbout = document.getElementById('profile-panel-about');
        const membersSection = document.getElementById('profile-panel-members');
        const memberList = document.getElementById('profile-panel-members-list');
        const memberCount = document.getElementById('profile-panel-members-count');
        const groupImgOverlay = document.getElementById('change-group-img-overlay');
        const editGroupNameContainer = document.getElementById('edit-group-name-container');

        // Reset/Clear Panel
        panelImgContainer.innerHTML = '<div class="p-4 text-center"><div class="spinner-border spinner-border-sm text-info"></div></div>';
        panelName.textContent = 'Loading...';
        panelSub.textContent = '';
        panelAbout.textContent = '';
        document.getElementById('profile-panel-status-dot').classList.add('d-none');
        document.getElementById('profile-panel-status-text').textContent = '';

        if (activeUserId) {
            // User context
            const name = activeName.textContent;
            panelImgContainer.innerHTML = getAvatarHtml(name, window.ACTIVE_USER_PROFILE_IMAGE, 'lg');
            panelName.textContent = name;

            let lastActiveStr = 'Offline';
            if (window.ACTIVE_USER_LAST_ACTIVE) {
                const d = new Date(window.ACTIVE_USER_LAST_ACTIVE);
                lastActiveStr = 'Last Seen: ' + d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
            }
            panelSub.textContent = lastActiveStr;
            panelAbout.textContent = window.ACTIVE_USER_ABOUT || 'Hey there! I am using ChatUs.';

            membersSection.classList.add('d-none');
            groupImgOverlay.classList.remove('d-flex');
            groupImgOverlay.classList.add('d-none');
            editGroupNameContainer.classList.add('d-none');

            // Fetch Groups in Common specifically for users
            fetchGroupsInCommon();
        } else if (activeGroupId) {
            // Group context
            membersSection.classList.remove('d-none');
            groupImgOverlay.classList.remove('d-none');
            groupImgOverlay.classList.add('d-flex');
            editGroupNameContainer.classList.remove('d-none');

            // Hide section for group info as it's redundant
            document.getElementById('profile-panel-common-groups-section')?.classList.add('d-none');

            // Reset status dot/text for groups
            document.getElementById('profile-panel-status-dot').classList.add('d-none');
            document.getElementById('profile-panel-status-text').textContent = 'Group Conversation';

            // Fetch Members
            fetch('controllers/GroupController.php?action=get_group_info&group_id=' + activeGroupId)
                .then(res => res.json())
                .then(res => {
                    if (res.status === 'success') {
                        panelImgContainer.innerHTML = getAvatarHtml(res.data.group_name, res.data.group_image, 'lg');
                        panelName.textContent = res.data.group_name;

                        const onlineCount = res.data.members.filter(m => isOnline(m.last_active)).length;
                        let onlineStr = '';
                        if (onlineCount > 0) {
                            onlineStr = ` • <span class="text-success fw-bold">${onlineCount} online</span>`;
                        }

                        panelSub.innerHTML = `<span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-3 py-1">${res.data.members.length} Participants${onlineStr}</span>`;
                        panelAbout.textContent = 'Managed Group Conversation. Secure and encrypted environment for all members.';
                        document.getElementById('edit-group-name-input').value = res.data.group_name;

                        let currentUserRole = 'member';
                        const me = res.data.members.find(m => m.id == window.CURRENT_USER_ID);
                        if (me) currentUserRole = me.role;

                        let addMemberBtnHtml = '';
                        if (currentUserRole === 'owner') {
                            addMemberBtnHtml = `
                                <button class="btn btn-sm btn-outline-primary w-100 mb-3" id="btn-add-member">
                                    <i class="bi bi-person-plus-fill"></i> Add Member
                                </button>
                            `;
                        }

                        memberList.innerHTML = addMemberBtnHtml;

                        res.data.members.forEach(member => {
                            const li = document.createElement('div');
                            li.className = 'member-item d-flex align-items-center p-2 mb-2 rounded border border-secondary border-opacity-25';
                            li.style.background = 'rgba(255,255,255,0.04)';

                            const displayName = member.id == window.CURRENT_USER_ID ? member.username + ' (You)' : member.username;
                            let roleBadge = '';
                            if (member.role === 'owner') {
                                roleBadge = '<span class="text-primary text-uppercase fw-bold"><i class="bi bi-star-fill text-warning me-1"></i>Owner</span>';
                            }

                            // Deterministic color hashing for the member
                            let cSum = 0;
                            const unameForHash = member.username || '';
                            for (let i = 0; i < unameForHash.length; i++) cSum += unameForHash.charCodeAt(i);
                            const userColor = ['#00f2ff', '#ff00ff', '#00ff66', '#ffcc00', '#aa00ff', '#ff6600'][cSum % 6];
                            li.style.setProperty('--member-color', userColor);

                            if (member.nickname) {
                                roleBadge += `<div class="fw-medium fst-italic mt-1" style="font-size: 0.8rem; color: ${userColor}; text-shadow: 0 0 10px ${userColor}40;">~ ${member.nickname}</div>`;
                            }

                            let actionsHtml = `<div class="d-flex ms-auto gap-1 align-items-center">`;
                            if (member.id != window.CURRENT_USER_ID) {
                                actionsHtml += `
                                    <button class="btn btn-sm btn-icon text-secondary rounded p-1" 
                                            onmouseenter="this.classList.replace('text-secondary', 'text-info')" 
                                            onmouseleave="this.classList.replace('text-info', 'text-secondary')"
                                            onclick="startDirectChat(${member.id}, '${member.username}', '${member.profile_image}')" title="Message">
                                        <i class="bi bi-chat-dots-fill fs-5"></i>
                                    </button>
                                `;
                            }
                            if (currentUserRole === 'owner' && member.id != window.CURRENT_USER_ID) {
                                actionsHtml += `
                                    <button class="btn btn-sm btn-icon text-secondary rounded p-1" 
                                            onmouseenter="this.classList.replace('text-secondary', 'text-warning')" 
                                            onmouseleave="this.classList.replace('text-warning', 'text-secondary')"
                                            onclick="promptSetNickname(${activeGroupId}, ${member.id}, '${member.username}', '${member.nickname || ''}')" title="Set Nickname">
                                        <i class="bi bi-pencil-square fs-5"></i>
                                    </button>
                                    <button class="btn btn-sm btn-icon text-secondary rounded p-1" 
                                            onmouseenter="this.classList.replace('text-secondary', 'text-danger')" 
                                            onmouseleave="this.classList.replace('text-danger', 'text-secondary')"
                                            onclick="removeMemberFromGroup(${activeGroupId}, ${member.id}, '${member.username}')" title="Remove Member">
                                        <i class="bi bi-person-x-fill fs-5"></i>
                                    </button>
                                `;
                            }
                            actionsHtml += `</div>`;

                            li.innerHTML = `
                                ${getAvatarHtml(member.username, member.profile_image)}
                                <div class="flex-grow-1" style="margin-left: 2px;">
                                    <div class="fw-bold small" style="line-height:1.2;">${displayName}</div>
                                    <div class="text-muted mt-1" style="font-size:0.7rem;">${roleBadge}</div>
                                </div>
                                ${actionsHtml}
                            `;
                            memberList.appendChild(li);
                        });

                        // Bind event for Add Member if exists
                        const addMemberBtn = document.getElementById('btn-add-member');
                        if (addMemberBtn) {
                            addMemberBtn.onclick = () => {
                                window.promptAddMember(activeGroupId);
                            };
                        }
                    }
                });
        }

        // Fetch Shared Media
        if (typeof fetchSharedMedia === 'function') fetchSharedMedia();
    });

    // Function to update profile panel live info if open
    function updateProfilePanelLive() {
        if (rightProfilePanel.classList.contains('d-none')) return;

        if (activeUserId) {
            const isUserOnline = isOnline(window.ACTIVE_USER_LAST_ACTIVE);
            const statusDot = document.getElementById('profile-panel-status-dot');
            const statusText = document.getElementById('profile-panel-status-text');

            if (statusDot) {
                statusDot.classList.remove('d-none');
                statusDot.className = 'status-dot ' + (isUserOnline ? 'online' : 'offline');
            }
            if (statusText) {
                statusText.textContent = isUserOnline ? 'ONLINE' : 'OFFLINE';
                statusText.className = isUserOnline ? 'text-primary small text-uppercase fw-bold' : 'text-muted small text-uppercase';
            }
        }
    }

    function fetchSharedMedia() {
        const grid = document.getElementById('profile-panel-media-grid');
        grid.innerHTML = '<div class="col-12 text-center text-muted small py-4">Loading media...</div>';

        let url = 'controllers/MessageController.php?action=get_shared_media';
        if (activeGroupId) url += '&group_id=' + activeGroupId;
        else url += '&receiver_id=' + activeUserId;

        fetch(url)
            .then(res => res.json())
            .then(res => {
                if (res.status === 'success' && res.data.length > 0) {
                    grid.innerHTML = '';
                    res.data.forEach(item => {
                        const col = document.createElement('div');
                        col.className = 'col-4';
                        col.innerHTML = `
                            <div class="ratio ratio-1x1 rounded-2 overflow-hidden border border-secondary border-opacity-25" style="cursor:pointer;">
                                <img src="${item.image_path}" class="object-fit-cover w-100 h-100" onclick="openLightbox('${item.image_path}', '${item.sender_id}', '${item.created_at}')">
                            </div>
                        `;
                        grid.appendChild(col);
                    });
                } else {
                    grid.innerHTML = '<div class="col-12 text-center text-muted small py-4">No media shared yet</div>';
                }
            });
    }

    // Save Group Name
    document.getElementById('save-group-name-btn')?.addEventListener('click', () => {
        const newName = document.getElementById('edit-group-name-input').value.trim();
        if (!newName || !activeGroupId) return;

        const fd = new FormData();
        fd.append('action', 'update_group_name');
        fd.append('group_id', activeGroupId);
        fd.append('name', newName);

        fetch('controllers/GroupController.php', { method: 'POST', body: fd })
            .then(res => res.json())
            .then(res => {
                if (res.status === 'success') {
                    document.getElementById('profile-panel-name').textContent = newName;
                    activeName.textContent = newName;
                    loadRecentConversations();
                } else {
                    alert(res.message);
                }
            });
    });

    // Group Image Upload
    document.getElementById('change-group-img-overlay')?.addEventListener('click', () => {
        document.getElementById('group-image-input').click();
    });

    document.getElementById('group-image-input')?.addEventListener('change', (e) => {
        const file = e.target.files[0];
        if (!file || !activeGroupId) return;

        const fd = new FormData();
        fd.append('action', 'update_group_image');
        fd.append('group_id', activeGroupId);
        fd.append('group_image', file);

        fetch('controllers/GroupController.php', { method: 'POST', body: fd })
            .then(res => res.json())
            .then(res => {
                if (res.status === 'success') {
                    // Update UI immediately (Sidebar)
                    const panelImgContainer = document.getElementById('profile-panel-img-container');
                    const avatarHtml = getAvatarHtml(activeName.textContent, res.path, 'lg');
                    panelImgContainer.innerHTML = avatarHtml;

                    const newImg = panelImgContainer.querySelector('.avatar, .avatar-initials');
                    if (newImg) {
                        newImg.style.width = '240px';
                        newImg.style.height = '240px';
                    }

                    // Update UI (Chat Header)
                    const headerAvatarContainer = document.getElementById('active-chat-avatar-container');
                    headerAvatarContainer.innerHTML = getAvatarHtml(activeName.textContent, res.path);

                    loadRecentConversations();
                } else {
                    alert(res.message);
                }
            });
    });

    document.getElementById('btn-remove-group-img-trigger')?.addEventListener('click', (e) => {
        e.stopPropagation(); // Avoid triggering file input if it is a child
        if (!activeGroupId) return;

        window.appConfirm('Remove group profile picture?', () => {
            const fd = new FormData();
            fd.append('action', 'remove_group_image');
            fd.append('group_id', activeGroupId);

            fetch('controllers/GroupController.php', { method: 'POST', body: fd })
                .then(res => res.json())
                .then(res => {
                    if (res.status === 'success') {
                        // Update Sidebar
                        const panelImgContainer = document.getElementById('profile-panel-img-container');
                        panelImgContainer.innerHTML = getAvatarHtml(activeName.textContent, null, 'lg');

                        // Update Chat Header
                        const headerAvatarContainer = document.getElementById('active-chat-avatar-container');
                        headerAvatarContainer.innerHTML = getAvatarHtml(activeName.textContent, null);

                        loadRecentConversations();
                    } else {
                        alert(res.message);
                    }
                });
        });
    });

    // Close Right Panel
    document.getElementById('close-profile-btn')?.addEventListener('click', () => {
        rightProfilePanel.classList.add('d-none');
        rightProfilePanel.classList.remove('d-flex');
    });

    // --- RESIZER LOGIC ---
    function initResizers() {
        const sidebar = document.getElementById('sidebar');
        const sidebarResizer = document.getElementById('sidebar-resizer');
        const profilePanel = document.getElementById('right-profile-panel');
        const profileResizer = document.getElementById('profile-resizer');

        if (!sidebar || !sidebarResizer) return;

        // Restore saved widths
        const savedSidebarWidth = localStorage.getItem('sidebar-width');
        if (savedSidebarWidth && window.innerWidth > 768) {
            sidebar.style.width = savedSidebarWidth + 'px';
        }

        const savedProfileWidth = localStorage.getItem('profile-width');
        if (savedProfileWidth && window.innerWidth > 768 && profilePanel) {
            profilePanel.style.width = savedProfileWidth + 'px';
        }

        // Left Sidebar Resizing
        sidebarResizer.addEventListener('mousedown', (e) => {
            if (sidebar.classList.contains('collapsed')) return;
            e.preventDefault();
            sidebarResizer.classList.add('resizing');
            document.body.style.cursor = 'col-resize';

            const onMouseMove = (e) => {
                let newWidth = e.clientX;
                if (newWidth > 280 && newWidth < 600) {
                    sidebar.style.width = newWidth + 'px';
                    localStorage.setItem('sidebar-width', newWidth);
                }
            };

            const onMouseUp = () => {
                sidebarResizer.classList.remove('resizing');
                document.body.style.cursor = 'default';
                document.removeEventListener('mousemove', onMouseMove);
                document.removeEventListener('mouseup', onMouseUp);
            };

            document.addEventListener('mousemove', onMouseMove);
            document.addEventListener('mouseup', onMouseUp);
        });

        // Right Profile Panel Resizing (If exists)
        if (profilePanel && profileResizer) {
            profileResizer.addEventListener('mousedown', (e) => {
                e.preventDefault();
                profileResizer.classList.add('resizing');
                document.body.style.cursor = 'col-resize';
                const startX = e.clientX;
                const startWidth = profilePanel.offsetWidth;

                const onMouseMove = (e) => {
                    let newWidth = startWidth + (startX - e.clientX);
                    if (newWidth > 300 && newWidth < 600) {
                        profilePanel.style.width = newWidth + 'px';
                        localStorage.setItem('profile-width', newWidth);
                    }
                };

                const onMouseUp = () => {
                    profileResizer.classList.remove('resizing');
                    document.body.style.cursor = 'default';
                    document.removeEventListener('mousemove', onMouseMove);
                    document.removeEventListener('mouseup', onMouseUp);
                };

                document.addEventListener('mousemove', onMouseMove);
                document.addEventListener('mouseup', onMouseUp);
            });
        }
    }

    // --- MICRO-INTERACTIONS (Swipe & Reactions) ---
    function initMicroInteractions() {
        let touchStartX = 0;
        let activeSwipeWrapper = null;

        // Delegated listener for chat messages
        chatMessages.addEventListener('touchstart', (e) => {
            const wrapper = e.target.closest('.message-wrapper');
            if (!wrapper) return;
            touchStartX = e.touches[0].clientX;
            activeSwipeWrapper = wrapper;
        }, { passive: true });

        chatMessages.addEventListener('touchmove', (e) => {
            if (!activeSwipeWrapper) return;
            const touchX = e.touches[0].clientX;
            const diff = touchStartX - touchX;

            // Only swipe "received" messages to the left to show reply
            if (activeSwipeWrapper.classList.contains('received') && diff > 30) {
                activeSwipeWrapper.classList.add('swiping');
                const translate = Math.min(diff, 80);
                activeSwipeWrapper.style.transform = `translateX(${-translate}px)`;

                // Show reply icon placeholder if one doesn't exist
                if (!activeSwipeWrapper.querySelector('.swipe-reply-icon')) {
                    const icon = document.createElement('div');
                    icon.className = 'swipe-reply-icon';
                    icon.innerHTML = '<i class="bi bi-reply-fill"></i>';
                    activeSwipeWrapper.appendChild(icon);
                }
            }
        }, { passive: true });

        chatMessages.addEventListener('touchend', (e) => {
            if (!activeSwipeWrapper) return;
            if (activeSwipeWrapper.classList.contains('swiping')) {
                const diff = touchStartX - e.changedTouches[0].clientX;
                if (diff > 60) {
                    window.playZenithSound('pop');
                    // Logic for initiating reply or showing reply UI would go here
                    if (window.showAppToast) window.showAppToast('Replying to message...', 'info');
                }
            }
            activeSwipeWrapper.classList.remove('swiping');
            activeSwipeWrapper.style.transform = '';
            activeSwipeWrapper = null;
        });

        // Long press for reactions (Message Bubbles)
        let pressTimer;
        chatMessages.addEventListener('mousedown', (e) => {
            const bubble = e.target.closest('.message-bubble');
            if (!bubble) return;
            pressTimer = window.setTimeout(() => showReactionPicker(bubble, e), 500);
        });

        chatMessages.addEventListener('touchstart', (e) => {
            const bubble = e.target.closest('.message-bubble');
            if (!bubble) return;
            pressTimer = window.setTimeout(() => showReactionPicker(bubble, e.touches[0]), 500);
        });

        const cancelPress = () => window.clearTimeout(pressTimer);
        chatMessages.addEventListener('mouseup', cancelPress);
        chatMessages.addEventListener('mouseleave', cancelPress);
        chatMessages.addEventListener('touchend', cancelPress);
        chatMessages.addEventListener('touchmove', cancelPress);
    }

    function showReactionPicker(bubble, coordSource) {
        window.playZenithSound('pop');
        // Remove existing pickers
        document.querySelectorAll('.reaction-bar').forEach(p => p.remove());

        const bar = document.createElement('div');
        bar.className = 'reaction-bar';
        const emojis = ['❤️', '😂', '😮', '😢', '🔥', '👍'];
        emojis.forEach(emoji => {
            const span = document.createElement('span');
            span.textContent = emoji;
            span.onclick = (e) => {
                e.stopPropagation();
                addReaction(bubble, emoji);
                bar.remove();
            };
            bar.appendChild(span);
        });

        bubble.parentElement.appendChild(bar);
    }

    function addReaction(bubble, emoji) {
        window.playZenithSound('received');
        let container = bubble.querySelector('.message-reaction-container');
        if (!container) {
            container = document.createElement('div');
            container.className = 'message-reaction-container';
            bubble.appendChild(container);
        }

        // Find existing same emoji
        const existing = Array.from(container.querySelectorAll('.reaction-pill')).find(p => p.textContent.includes(emoji));
        if (existing) {
            existing.remove();
            if (container.children.length === 0) container.remove();
        } else {
            const pill = document.createElement('div');
            pill.className = 'reaction-pill';
            pill.textContent = emoji + ' 1';
            container.appendChild(pill);
        }
    }

    initResizers();
    initMicroInteractions();
});

// Global Function for Once-View image
function viewOnceImage(btn, msgId, path) {
    // Open the Secure Overlay
    const overlay = document.getElementById('viewonce-overlay');
    const secureImg = document.getElementById('viewonce-image');

    // Disable native behaviors
    secureImg.oncontextmenu = () => false;
    secureImg.ondragstart = () => false;
    secureImg.src = path;

    overlay.classList.remove('d-none');

    // Tell server it was viewed
    const fd = new FormData();
    fd.append('action', 'mark_viewed');
    fd.append('message_id', msgId);
    fetch('controllers/MessageController.php', { method: 'POST', body: fd }).then(() => {
        // Optimistically remove from DOM loop so it doesn't flicker when overlay closed
        btn.parentElement.innerHTML = `<div class="text-muted fst-italic"><i class="bi bi-eye-slash"></i> Image viewed</div>`;
    });
}

function closeSecureOverlay() {
    const overlay = document.getElementById('viewonce-overlay');
    if (!overlay.classList.contains('d-none')) {
        overlay.classList.add('d-none');
        document.getElementById('viewonce-image').src = '';
    }
}

// Logic to handle closing the overlay manually
document.getElementById('viewonce-close').addEventListener('click', closeSecureOverlay);

// Anti-Screenshot Measures
document.addEventListener('contextmenu', e => {
    if (!document.getElementById('viewonce-overlay').classList.contains('d-none')) {
        e.preventDefault(); // Stop right-click
    }
});

document.addEventListener('keydown', e => {
    if (!document.getElementById('viewonce-overlay').classList.contains('d-none')) {
        // Block PrintScreen, and combinations (OS X uses Meta+Shift+3/4)
        if (e.key === 'PrintScreen' || (e.metaKey && e.shiftKey) || (e.ctrlKey && e.key === 's') || (e.ctrlKey && e.key === 'p')) {
            e.preventDefault();
            closeSecureOverlay();
            alert("Screenshots or saving are strictly prohibited in View Once mode.");
        }
        if (e.key === 'Escape') {
            closeSecureOverlay();
        }
    }
});

// React to window blur/screenshot tools that hijack focus
window.addEventListener('blur', closeSecureOverlay);
document.addEventListener('visibilitychange', () => {
    if (document.hidden) closeSecureOverlay();
});

/* ==============================================================
 * LIGHTBOX / IMAGE GALLERY LOGIC
 * ============================================================== */
let currentGalleryImages = [];
let currentGalleryIndex = 0;
let currentZoom = 1;

const lightboxOverlay = document.getElementById('lightbox-overlay');
const lightboxImage = document.getElementById('lightbox-image');
const lightboxSender = document.getElementById('lightbox-sender-name');
const lightboxMetaTime = document.getElementById('lightbox-timestamp');

window.openLightbox = function (imgElement) {
    // Gather all gallery images dynamically from DOM
    const allImages = Array.from(document.querySelectorAll('.gallery-img'));
    currentGalleryImages = allImages;
    currentGalleryIndex = allImages.indexOf(imgElement);

    updateLightboxUI();
    lightboxOverlay.classList.remove('d-none');

    // Reset zoom
    currentZoom = 1;
    applyZoom();
};

document.getElementById('lightbox-close').addEventListener('click', () => {
    lightboxOverlay.classList.add('d-none');
});

document.getElementById('lightbox-prev').addEventListener('click', () => navigateLightbox(-1));
document.getElementById('lightbox-next').addEventListener('click', () => navigateLightbox(1));

function navigateLightbox(direction) {
    currentGalleryIndex += direction;
    if (currentGalleryIndex < 0) currentGalleryIndex = currentGalleryImages.length - 1;
    if (currentGalleryIndex >= currentGalleryImages.length) currentGalleryIndex = 0;

    currentZoom = 1;
    applyZoom();
    updateLightboxUI();
}

function updateLightboxUI() {
    const targetImg = currentGalleryImages[currentGalleryIndex];
    if (!targetImg) return;

    lightboxImage.src = targetImg.src;
    lightboxSender.innerText = targetImg.getAttribute('data-sender') || 'Unknown';
    lightboxMetaTime.innerText = targetImg.getAttribute('data-time') || '';
}

// Keyboard Navigation
document.addEventListener('keydown', (e) => {
    if (!lightboxOverlay.classList.contains('d-none')) {
        if (e.key === 'Escape') lightboxOverlay.classList.add('d-none');
        if (e.key === 'ArrowLeft') navigateLightbox(-1);
        if (e.key === 'ArrowRight') navigateLightbox(1);
    }
});

// Zoom Logic
document.getElementById('lightbox-zoom-in').addEventListener('click', () => {
    if (currentZoom < 4) {
        currentZoom += 0.5;
        applyZoom();
    }
});

document.getElementById('lightbox-zoom-out').addEventListener('click', () => {
    if (currentZoom > 1) {
        currentZoom -= 0.5;
        applyZoom();
    }
});

document.getElementById('lightbox-zoom-reset').addEventListener('click', () => {
    currentZoom = 1;
    lightboxImage.style.transform = `scale(${currentZoom}) translate(0px, 0px)`;
});

function applyZoom() {
    lightboxImage.style.transform = `scale(${currentZoom})`;
}

// Group Membership Functions
window.promptSetNickname = function (groupId, userId, username, currentNickname) {
    document.getElementById('nickname-modal-username').textContent = username;
    document.getElementById('nickname-input').value = currentNickname || '';
    window.nicknameEditContext = { groupId, userId };

    // Hide profile panel natively while modal opens (optional, but requested implicitly to handle overlays cleanly)
    const modal = new bootstrap.Modal(document.getElementById('nicknameModal'));
    modal.show();
};

window.saveNicknameFromModal = function () {
    const newNickname = document.getElementById('nickname-input').value.trim();
    const { groupId, userId } = window.nicknameEditContext;

    if (newNickname !== null) {
        const fd = new FormData();
        fd.append('action', 'set_nickname');
        fd.append('group_id', groupId);
        fd.append('user_id', userId);
        fd.append('nickname', newNickname);

        fetch('controllers/GroupController.php', { method: 'POST', body: fd })
            .then(res => res.json())
            .then(res => {
                if (res.status === 'success') {
                    // Close Modal
                    const modalEl = document.getElementById('nicknameModal');
                    const modal = bootstrap.Modal.getInstance(modalEl);
                    if (modal) modal.hide();

                    if (window.showAppToast) window.showAppToast('Nickname updated!', 'success');
                    // Force refresh right panel
                    document.getElementById('chat-header-info').click();
                } else {
                    if (window.showAppToast) window.showAppToast(res.message, 'danger');
                    else alert(res.message);
                }
            });
    }
};

window.promptAddMember = function (groupId) {
    document.getElementById('add-member-input').value = '';
    window.addMemberContext = { groupId };
    const modal = new bootstrap.Modal(document.getElementById('addMemberModal'));
    modal.show();
};

window.saveAddMemberModal = function () {
    const newUsername = document.getElementById('add-member-input').value.trim();
    if (!window.addMemberContext || !window.addMemberContext.groupId) return window.showAppToast('Invalid context', 'danger');
    if (!newUsername) return window.showAppToast('Username is required', 'warning');

    const fd = new FormData();
    fd.append('action', 'add_member');
    fd.append('group_id', window.addMemberContext.groupId);
    fd.append('user_id', newUsername);

    fetch('controllers/GroupController.php', { method: 'POST', body: fd })
        .then(res => res.json())
        .then(res => {
            if (res.status === 'success') {
                const modalEl = document.getElementById('addMemberModal');
                const modal = bootstrap.Modal.getInstance(modalEl);
                if (modal) modal.hide();
                document.getElementById('chat-header-info').click();
                if (window.showAppToast) window.showAppToast(res.message, 'success');
            } else {
                if (window.showAppToast) window.showAppToast(res.message, 'danger');
                else alert(res.message);
            }
        });
};

window.removeMemberFromGroup = function (groupId, userId, username) {
    window.appConfirm(`Are you absolutely sure you want to remove ${username} from the group?`, () => {
        const fd = new FormData();
        fd.append('action', 'remove_member');
        fd.append('group_id', groupId);
        fd.append('user_id', userId);

        fetch('controllers/GroupController.php', { method: 'POST', body: fd })
            .then(res => res.json())
            .then(res => {
                if (res.status === 'success') {
                    document.getElementById('chat-header-info').click();
                    if (window.showAppToast) window.showAppToast(res.message, 'success');
                } else {
                    if (window.showAppToast) window.showAppToast(res.message, 'danger');
                    else alert(res.message);
                }
            });
    });
};

window.showAppToast = function (message, type = 'info') {
    const toastEl = document.getElementById('appToast');
    const toastBody = document.getElementById('appToastBody');
    if (!toastEl || !toastBody) return alert(message);

    // Remove old state classes
    toastEl.classList.remove('bg-success', 'bg-danger', 'bg-warning', 'bg-info', 'bg-primary', 'text-white');

    // Apply new type
    toastEl.classList.add(`bg-${type}`, 'align-items-center', 'border-0');

    // Force white text ONLY for high-contrast status types
    if (['success', 'danger', 'primary'].includes(type)) {
        toastEl.classList.add('text-white');
    } else {
        // Let standard theme handling take care of it for warning/info/etc if needed
        toastEl.classList.remove('text-white');
    }

    toastBody.textContent = message;

    const toast = new bootstrap.Toast(toastEl, { delay: 3500 });
    toast.show();
};

window.startDirectChat = function (userId, username, profile_image) {
    const userObj = { id: userId, username: username, profile_image: profile_image, type: 'user' };

    if (window.selectContactGlobal) {
        window.selectContactGlobal(userObj, null, null);
    }

    // Close the right panel automatically
    const closeBtn = document.getElementById('close-profile-btn');
    if (closeBtn) closeBtn.click();
};

window.appConfirm = function (message, onConfirm) {
    document.getElementById('appConfirmMessage').textContent = message;
    const btn = document.getElementById('appConfirmActionBtn');
    const backdrop = document.getElementById('emoji-backdrop');

    const newBtn = btn.cloneNode(true);
    btn.parentNode.replaceChild(newBtn, btn);

    const modalEl = document.getElementById('appConfirmModal');
    const modal = new bootstrap.Modal(modalEl);

    backdrop?.classList.remove('d-none');

    newBtn.addEventListener('click', () => {
        modal.hide();
        if (onConfirm) onConfirm();
    });

    modalEl.addEventListener('hidden.bs.modal', () => {
        backdrop?.classList.add('d-none');
    }, { once: true });

    modal.show();
};

window.alert = function (message) {
    const alertBody = document.getElementById('appAlertMessage');
    const backdrop = document.getElementById('emoji-backdrop');
    if (alertBody) {
        alertBody.textContent = message;
        const modalEl = document.getElementById('appAlertModal');
        const modal = new bootstrap.Modal(modalEl);
        backdrop?.classList.remove('d-none');
        modalEl.addEventListener('hidden.bs.modal', () => {
            backdrop?.classList.add('d-none');
        }, { once: true });
        modal.show();
    } else {
        console.warn('Alert triggered before DOM:', message);
    }
};

// Global Modal Backdrop Sync (Profile, New Group, Nickname, etc.)
document.addEventListener('show.bs.modal', (e) => {
    // Custom alerts/confirms handle their own to avoid double-trigger issues if needed
    // but generic ones like profile/new group need it here
    const ids = ['profileModal', 'newGroupModal', 'nicknameModal', 'addMemberModal'];
    if (ids.includes(e.target.id)) {
        document.getElementById('emoji-backdrop')?.classList.remove('d-none');
    }
});

document.addEventListener('hidden.bs.modal', (e) => {
    const ids = ['profileModal', 'newGroupModal', 'nicknameModal', 'addMemberModal'];
    if (ids.includes(e.target.id)) {
        document.getElementById('emoji-backdrop')?.classList.add('d-none');
    }
});
