<?php
// admin/dashboard.php
require_once dirname(__DIR__) . '/middleware/adminAuth.php';
$theme = $_SESSION['theme'] ?? 'dark';
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="<?php echo htmlspecialchars($theme); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ChatUs - Admin Portal</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&family=Zen+Dots&display=swap" rel="stylesheet">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="../assets/css/style.css" rel="stylesheet">
    <style>
        body {
            background: var(--bg-color);
            color: var(--text-color);
            font-family: 'Manrope', sans-serif;
            height: 100vh;
            overflow: hidden;
            display: flex;
        }

        .admin-sidebar {
            width: 280px;
            display: flex;
            flex-direction: column;
            z-index: 100;
        }

        .admin-brand {
            padding: 40px 20px;
            text-align: center;
        }

        .brand-logo {
            font-family: 'Space Grotesk', sans-serif;
            font-size: 2rem;
            font-weight: 800;
            color: var(--primary);
            letter-spacing: -0.04em;
            margin-bottom: 5px;
        }

        .admin-content {
            flex: 1;
            padding: 40px;
            overflow-y: auto;
            position: relative;
            z-index: 10;
        }

        .nav-link {
            color: var(--text-muted);
            padding: 15px 30px;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.1em;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            border-left: 4px solid transparent;
        }

        .nav-link i { font-size: 1.1rem; }

        .nav-link:hover, .nav-link.active {
            color: var(--primary);
            border-left-color: var(--primary);
        }

        .btn-theme-toggle {
            display: flex;
            align-items: center;
            padding: 8px 14px;
            border-radius: 12px;
            background: var(--primary-dim);
            color: var(--primary);
            font-weight: 700;
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            border: 1px solid var(--primary);
            transition: 0.2s;
            cursor: pointer;
            margin: 0 20px 20px 20px;
        }

        .btn-theme-toggle:hover {
            color: var(--primary);
            border-color: var(--primary);
            box-shadow: 0 0 10px var(--primary-dim);
        }

        .stat-val {
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 0.15em;
            color: var(--text-muted);
            font-weight: 700;
        }

        .stat-label {
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 0.15em;
            color: var(--text-muted);
            font-weight: 700;
        }

        .stat-icon-circle {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: grid;
            place-items: center;
            font-size: 1.25rem;
            margin-bottom: 15px;
            color: var(--primary);
            border: 1px solid var(--primary-dim);
        }

        .admin-card {
            background: var(--sidebar-bg);
            border-radius: 24px;
            padding: 30px;
            border: 1px solid var(--modal-border);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            height: 100%;
            cursor: pointer;
        }

        .admin-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px var(--primary-dim);
            border-color: var(--primary);
        }

        .stat-value {
            font-size: 2.5rem;
            font-weight: 800;
            font-family: 'Space Grotesk', sans-serif;
            letter-spacing: -2px;
            margin-top: 5px;
        }

        .table thead th {
            text-transform: uppercase;
            font-size: 0.7rem;
            letter-spacing: 0.1em;
            border-bottom: 1px solid var(--modal-border);
            padding: 20px;
            color: var(--text-muted);
            font-weight: 800;
        }

        .table tbody td {
            padding: 16px 20px;
            border-bottom: 1px solid var(--modal-border);
            font-size: 0.9rem;
            vertical-align: middle;
        }

        .badge-neon {
            color: var(--primary);
            border: 1px solid var(--primary);
            padding: 4px 10px;
            border-radius: 6px;
            font-weight: 700;
            font-size: 0.7rem;
            text-transform: uppercase;
        }

        .btn-action {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            padding: 0;
            display: grid;
            place-items: center;
            transition: 0.2s;
        }
        
        /* Total Immersion Background Blur */
        body.immersion-active #admin-main,
        body.immersion-active .admin-sidebar {
            filter: blur(40px);
            transition: filter 0.4s cubic-bezier(0.165, 0.84, 0.44, 1);
        }
    </style>
</head>
    <div id="emoji-backdrop" class="emoji-backdrop d-none"></div>

    <div class="mesh-bg"></div>

    <!-- Admin Sidebar -->
    <div class="admin-sidebar admin-sidebar-glass shadow-lg">
        <div class="admin-brand">
            <div class="brand-logo">ChatUs</div>
            <div class="text-primary fw-bold" style="font-size: 0.6rem; letter-spacing: 0.25em; text-transform: uppercase;">ADMIN PORTAL</div>
        </div>
        <nav class="nav flex-column mt-2 flex-grow-1">
            <a class="nav-link active" href="#" onclick="loadSection('stats')"><i class="bi bi-grid-fill me-3"></i> Overview</a>
            <a class="nav-link" href="#" onclick="loadSection('users')"><i class="bi bi-people-fill me-3"></i> Users</a>
            <a class="nav-link" href="#" onclick="loadSection('messages')"><i class="bi bi-chat-dots-fill me-3"></i> Messages</a>
            <a class="nav-link" href="#" onclick="loadSection('groups')"><i class="bi bi-collection-fill me-3"></i> Groups</a>
        </nav>
        <div class="pt-2">
            <button id="theme-toggle" class="btn-theme-toggle w-auto mx-auto mb-3">
                <i class="bi <?php echo $theme === 'dark' ? 'bi-sun-fill' : 'bi-moon-stars-fill'; ?> me-2"></i>
                <span><?php echo $theme === 'dark' ? 'Light Mode' : 'Dark Mode'; ?></span>
            </button>
        </div>
        <div class="p-4 pt-0">
            <a href="../chat.php" class="btn btn-sporty w-100 mb-3 rounded-4 py-2" style="font-size: 0.8rem;">
                <i class="bi bi-arrow-left me-2"></i> EXIT PORTAL
            </a>
            <a href="../logout.php" class="btn btn-danger w-100 rounded-4 py-2 shadow-sm" style="font-weight: 700; font-size: 0.8rem;">
                <i class="bi bi-box-arrow-right me-2"></i> LOGOUT
            </a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="admin-content" id="admin-main">
        <div class="d-flex justify-content-between align-items-center mb-5">
            <h2 class="m-0 fw-bold" id="page-title" style="letter-spacing: -2px; font-family: 'Space Grotesk', sans-serif;">System Overview</h2>
            <div class="text-muted small fw-bold opacity-75"><?php echo date('l, jS F Y'); ?></div>
        </div>
        
        <!-- Stats Grid -->
        <div class="row g-4" id="stats-section">
            <div class="col-md-4">
                <div class="admin-card">
                    <div class="stat-icon-circle"><i class="bi bi-person-lines-fill"></i></div>
                    <div class="stat-label">Total Users</div>
                    <div class="stat-value" id="total-users">0</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="admin-card">
                    <div class="stat-icon-circle"><i class="bi bi-chat-text-fill"></i></div>
                    <div class="stat-label">Network Traffic</div>
                    <div class="stat-value" id="total-messages">0</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="admin-card">
                    <div class="stat-icon-circle"><i class="bi bi-layers-fill"></i></div>
                    <div class="stat-label">Active Groups</div>
                    <div class="stat-value" id="total-groups">0</div>
                </div>
            </div>
        </div>

        <!-- Data Section -->
        <div id="data-section" class="mt-2 d-none">
            <div class="admin-table-container">
                <div class="table-responsive">
                    <table class="table table-hover align-middle" id="data-table">
                        <thead id="data-thead"></thead>
                        <tbody id="data-tbody"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modals copied from chat.php for Immersive Confirmations -->
    <div class="modal fade" id="appConfirmModal" tabindex="-1" style="z-index: 1095;">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content shadow-lg">
                <div class="modal-header">
                    <h5 class="modal-title text-warning"><i class="bi bi-question-circle-fill me-2"></i>Confirm Action</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center py-4">
                    <p id="appConfirmMessage" class="mb-0"></p>
                </div>
                <div class="modal-footer justify-content-center">
                    <button type="button" class="btn btn-secondary btn-sm px-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-warning btn-sm px-3 fw-bold" id="appConfirmActionBtn">Confirm</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Toast notification also needed -->
    <div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 2000;">
        <div id="appToast" class="toast align-items-center border-0" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body" id="appToastBody"></div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="modal"></button>
            </div>
        </div>
    </div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/theme.js"></script>
<script>
    function loadStats() {
        document.getElementById('stats-section').classList.remove('d-none');
        document.getElementById('data-section').classList.add('d-none');
        document.getElementById('page-title').innerText = 'System Overview';
        
        fetch('../controllers/AdminController.php?action=get_stats')
            .then(res => res.json())
            .then(res => {
                if(res.status === 'success') {
                    document.getElementById('total-users').innerText = res.data.users;
                    document.getElementById('total-messages').innerText = res.data.messages;
                    document.getElementById('total-groups').innerText = res.data.groups;
                }
            });
    }

    function loadSection(section) {
        document.querySelectorAll('.nav-link').forEach(l => l.classList.remove('active'));
        event.currentTarget.classList.add('active');

        if(section === 'stats') {
            loadStats();
        } else if (section === 'users') {
            loadUsers();
        } else if (section === 'messages') {
            loadMessages();
        } else if (section === 'groups') {
            loadGroups();
        }
    }

    function loadStats() {
        document.getElementById('stats-section').classList.remove('d-none');
        document.getElementById('data-section').classList.add('d-none');
        document.getElementById('page-title').innerText = 'System Overview';
        
        fetch('../controllers/AdminController.php?action=get_stats')
            .then(res => res.json())
            .then(res => {
                if(res.status === 'success') {
                    document.getElementById('total-users').innerText = res.data.users;
                    document.getElementById('total-messages').innerText = res.data.messages;
                    document.getElementById('total-groups').innerText = res.data.groups;
                }
            });
    }

    function loadUsers() {
        document.getElementById('stats-section').classList.add('d-none');
        document.getElementById('data-section').classList.remove('d-none');
        document.getElementById('page-title').innerText = 'User Management';
        
        const thead = document.getElementById('data-thead');
        const tbody = document.getElementById('data-tbody');
        
        thead.innerHTML = `<tr><th>ID</th><th>Username</th><th>Email</th><th>Role</th><th>Joined</th><th>Actions</th></tr>`;
        tbody.innerHTML = '<tr><td colspan="6" class="text-center p-5 opacity-50">Loading Secure Records...</td></tr>';

        fetch('../controllers/AdminController.php?action=get_users')
            .then(r => r.json())
            .then(res => {
                if(res.status === 'success') {
                    tbody.innerHTML = '';
                    res.data.forEach(u => {
                        const date = new Date(u.created_at).toLocaleDateString();
                        tbody.innerHTML += `<tr>
                            <td><span class="text-muted fw-bold">${u.id}</span></td>
                            <td class="fw-bold">${u.username}</td>
                            <td class="opacity-75">${u.email}</td>
                            <td><span class="${u.role === 'admin' ? 'text-danger' : 'text-primary'} fw-bold text-uppercase" style="font-size: 0.7rem; letter-spacing: 0.1em;">${u.role}</span></td>
                            <td class="small opacity-50">${date}</td>
                            <td>
                                <button class="btn btn-sm btn-outline-danger btn-action" onclick="deleteUser(${u.id})"><i class="bi bi-trash3-fill"></i></button>
                            </td>
                        </tr>`;
                    });
                }
            });
    }

    function loadMessages() {
        document.getElementById('stats-section').classList.add('d-none');
        document.getElementById('data-section').classList.remove('d-none');
        document.getElementById('page-title').innerText = 'Message Audit';
        
        const thead = document.getElementById('data-thead');
        const tbody = document.getElementById('data-tbody');
        
        thead.innerHTML = `<tr><th>Sender</th><th>Destination</th><th>Content</th><th>Timestamp</th><th>Actions</th></tr>`;
        tbody.innerHTML = '<tr><td colspan="5" class="text-center p-5 opacity-50">Auditing Node Traffic...</td></tr>';

        fetch('../controllers/AdminController.php?action=get_messages')
            .then(r => r.json())
            .then(res => {
                if(res.status === 'success') {
                    tbody.innerHTML = '';
                    res.data.forEach(m => {
                        const target = m.group_id ? `<span class="text-warning fw-bold text-uppercase" style="font-size:0.7rem;">Group: ${m.group_id}</span>` : `<span class="text-info fw-bold text-uppercase" style="font-size:0.7rem;">User: ${m.receiver_id}</span>`;
                        const content = m.image_path ? `<i class="bi bi-image me-2"></i>[Media]` : m.decrypted_message;
                        const date = new Date(m.created_at).toLocaleString();
                        tbody.innerHTML += `<tr>
                            <td class="fw-bold">${m.sender_name}</td>
                            <td>${target}</td>
                            <td><div class="text-truncate opacity-75" style="max-width: 250px;">${content}</div></td>
                            <td class="small opacity-50">${date}</td>
                            <td>
                                <button class="btn btn-sm btn-outline-danger btn-action" onclick="deleteMessage(${m.id})"><i class="bi bi-trash3-fill"></i></button>
                            </td>
                        </tr>`;
                    });
                }
            });
    }

    function loadGroups() {
        document.getElementById('stats-section').classList.add('d-none');
        document.getElementById('data-section').classList.remove('d-none');
        document.getElementById('page-title').innerText = 'Group Management';
        
        const thead = document.getElementById('data-thead');
        const tbody = document.getElementById('data-tbody');
        
        thead.innerHTML = `<tr><th>ID</th><th>Group Name</th><th>Administrator</th><th>Formed</th><th>Actions</th></tr>`;
        tbody.innerHTML = '<tr><td colspan="5" class="text-center p-5 opacity-50">Indexing Clusters...</td></tr>';

        fetch('../controllers/AdminController.php?action=get_groups')
            .then(r => r.json())
            .then(res => {
                if(res.status === 'success') {
                    tbody.innerHTML = '';
                    res.data.forEach(g => {
                        tbody.innerHTML += `<tr>
                            <td><span class="text-muted fw-bold">${g.id}</span></td>
                            <td class="fw-bold">${g.name}</td>
                            <td>${g.admin_name}</td>
                            <td class="small opacity-50">${new Date(g.created_at).toLocaleDateString()}</td>
                            <td>
                                <button class="btn btn-sm btn-outline-danger btn-action" onclick="deleteGroup(${g.id})"><i class="bi bi-trash3-fill"></i></button>
                            </td>
                        </tr>`;
                    });
                }
            });
    }

    // Immersive Confirm/Alert system ported to admin
    window.appConfirm = function (message, onConfirm) {
        document.getElementById('appConfirmMessage').textContent = message;
        const btn = document.getElementById('appConfirmActionBtn');
        const backdrop = document.getElementById('emoji-backdrop');

        const newBtn = btn.cloneNode(true);
        btn.parentNode.replaceChild(newBtn, btn);

        const modalEl = document.getElementById('appConfirmModal');
        const modal = new bootstrap.Modal(modalEl);

        backdrop?.classList.remove('d-none');
        document.body.classList.add('immersion-active');

        newBtn.addEventListener('click', () => {
            modal.hide();
            if (onConfirm) onConfirm();
        });

        modalEl.addEventListener('hidden.bs.modal', () => {
            backdrop?.classList.add('d-none');
            document.body.classList.remove('immersion-active');
        }, { once: true });

        modal.show();
    };

    window.showAppToast = function (message, type = 'info') {
        const toastEl = document.getElementById('appToast');
        const toastBody = document.getElementById('appToastBody');
        if (!toastEl || !toastBody) return alert(message);

        toastEl.classList.remove('bg-success', 'bg-danger', 'bg-warning', 'bg-info', 'bg-primary', 'text-white');
        toastEl.classList.add(`bg-${type}`, 'align-items-center', 'border-0');
        if (['success', 'danger', 'primary'].includes(type)) toastEl.classList.add('text-white');

        toastBody.textContent = message;
        const toast = new bootstrap.Toast(toastEl, { delay: 3500 });
        toast.show();
    };

    function deleteUser(id) {
        window.appConfirm('Erase this identity permanently? This cannot be undone.', () => {
            const fd = new FormData();
            fd.append('action', 'delete_user');
            fd.append('user_id', id);
            fetch('../controllers/AdminController.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(res => { 
                    if(res.status==='success') {
                        loadUsers();
                        window.showAppToast('Identity erased.', 'success');
                    } else {
                        window.showAppToast(res.message, 'danger');
                    }
                });
        });
    }

    function deleteMessage(id) {
        window.appConfirm('Delete this message permanently?', () => {
            const fd = new FormData();
            fd.append('action', 'delete_message');
            fd.append('message_id', id);
            fetch('../controllers/AdminController.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(res => { 
                    if(res.status==='success') {
                        loadMessages();
                        window.showAppToast('Message deleted.', 'success');
                    }
                });
        });
    }

    function deleteGroup(id) {
        window.appConfirm('Dissolve this group and erase all history?', () => {
            const fd = new FormData();
            fd.append('action', 'delete_group');
            fd.append('group_id', id);
            fetch('../controllers/AdminController.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(res => { 
                    if(res.status==='success') {
                        loadGroups();
                        window.showAppToast('Group dissolved.', 'success');
                    }
                });
        });
    }

    loadStats();
</script>
</body>
</html>
