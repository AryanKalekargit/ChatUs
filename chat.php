<?php
// chat.php
require_once 'middleware/auth.php';
require_once 'config/database.php';
require_once 'models/User.php';

$db = (new Database())->getConnection();
$userModel = new User($db);

$currentUser = $userModel->getUserById($_SESSION['user_id']);

if (!$currentUser) {
    header("Location: logout.php");
    exit();
}

$theme = $currentUser['theme_preference'] ?? 'dark';
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="<?php echo htmlspecialchars($theme); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ChatUs - Secure Messaging</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Outfit:wght@300;400;600;700&family=Zen+Dots&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <!-- Custom CSS -->
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>

<div id="emoji-backdrop" class="emoji-backdrop d-none"></div>

<div class="chat-app-container">
    
    <!-- LEFT SIDEBAR -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <div class="d-flex align-items-center">
                <button id="sidebar-toggle" class="btn btn-icon me-2 d-none d-md-block">
                    <i class="bi bi-list"></i>
                </button>
                <h1 class="brand-title"><i class="bi bi-chat-square-dots-fill me-2"></i>ChatUs</h1>
            </div>
            <div class="d-flex gap-2">
                <button id="theme-toggle" class="btn btn-icon <?php echo $theme === 'dark' ? 'text-warning' : 'text-dark'; ?>">
                    <i class="bi <?php echo $theme === 'dark' ? 'bi-sun-fill' : 'bi-moon-stars-fill'; ?>"></i>
                </button>
                <div class="dropdown">
                    <button class="btn btn-icon" type="button" data-bs-toggle="dropdown">
                        <i class="bi bi-three-dots-vertical"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#profileModal"><i class="bi bi-person me-2"></i>Profile</a></li>
                        <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#createGroupModal"><i class="bi bi-people me-2"></i>New Group</a></li>
                        <?php if($_SESSION['role'] === 'admin'): ?>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="admin/dashboard.php"><i class="bi bi-shield-lock me-2"></i>Admin Panel</a></li>
                        <?php endif; ?>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                    </ul>
                </div>
            </div>
        </div>
        
        <div class="search-bar">
            <input type="text" id="user-search" class="form-control" placeholder="Search users...">
        </div>
        
        <!-- Filters -->
        <div class="px-3 pb-2 d-flex gap-2 border-bottom border-secondary border-opacity-10 align-items-center" id="sidebar-filters" style="min-height: 45px;">
            <button class="btn btn-sm text-primary border-primary filter-chip active" data-filter="all" style="border-radius: 20px; width: 40px; height: 28px; padding: 0; display: flex; align-items: center; justify-content: center;" title="All"><i class="bi bi-grid-fill"></i></button>
            <button class="btn btn-sm text-muted filter-chip" data-filter="group" style="border-radius: 20px; width: 40px; height: 28px; padding: 0; display: flex; align-items: center; justify-content: center;" title="Groups"><i class="bi bi-people-fill"></i></button>
            <button class="btn btn-sm text-muted filter-chip" data-filter="user" style="border-radius: 20px; width: 40px; height: 28px; padding: 0; display: flex; align-items: center; justify-content: center;" title="Chats"><i class="bi bi-chat-fill"></i></button>
        </div>

        <div class="contact-list" id="contact-list">
            <!-- Populated via AJAX -->
            <div class="p-3 text-center text-muted spinner-border-wrapper">
                <div class="spinner-border text-info" role="status"></div>
                <div class="mt-2">Loading contacts...</div>
            </div>
        </div>

        <!-- User Self-Profile Footer -->
        <div class="sidebar-footer" id="sidebar-self-profile" style="cursor: pointer;" data-bs-toggle="modal" data-bs-target="#profileModal">
            <div class="d-flex align-items-center w-100">
                <div id="self-pfp-container">
                    <?php 
                    $name = $currentUser['username'];
                    $img = $currentUser['profile_image'];
                    if ($img && $img !== 'assets/images/default.png'): ?>
                        <img src="<?php echo htmlspecialchars($img); ?>" class="avatar sm" id="self-pfp">
                    <?php else: 
                        $initials = strtoupper(substr($name, 0, 1));
                        $charCodeSum = array_sum(array_map('ord', str_split($name)));
                        $colorIdx = $charCodeSum % 6;
                    ?>
                        <div class="avatar-initials sm avatar-bg-<?php echo $colorIdx; ?>" id="self-pfp-initials"><?php echo $initials; ?></div>
                    <?php endif; ?>
                </div>
                <div class="ms-3 flex-grow-1 overflow-hidden">
                    <h6 class="m-0 text-truncate fw-bold"><?php echo htmlspecialchars($currentUser['username']); ?></h6>
                    <small class="text-muted text-truncate d-block" style="font-size: 0.7rem;"><?php echo htmlspecialchars($currentUser['about'] ?? 'Hey there!'); ?></small>
                </div>
                <div class="text-end">
                    <small class="snapchat-date">Joined <?php echo date('F Y', strtotime($currentUser['created_at'])); ?></small>
                </div>
            </div>
        </div>
        <div class="resizer resizer-right" id="sidebar-resizer"></div>
    </div>
    
    <!-- MOBILE BOTTOM NAVIGATION -->
    <div class="mobile-bottom-nav d-none" id="mobile-bottom-nav">
        <button class="nav-item active" data-filter="all">
            <i class="bi bi-chat-square-dots"></i>
            <span>Chats</span>
        </button>
        <button class="nav-item" data-filter="group">
            <i class="bi bi-people"></i>
            <span>Groups</span>
        </button>
        <button class="nav-item fab-item" id="mobile-fab-btn">
            <i class="bi bi-plus-lg"></i>
        </button>
        <button class="nav-item" data-filter="user">
            <i class="bi bi-person-search"></i>
            <span>Users</span>
        </button>
        <button class="nav-item" id="mobile-settings-btn">
            <i class="bi bi-gear"></i>
            <span>Settings</span>
        </button>
    </div>

    <!-- MAIN CHAT AREA -->
    <div class="main-chat d-flex flex-column h-100 w-100 position-relative" id="main-chat">
        
        <!-- Chat Header -->
        <div class="chat-header d-none" id="chat-header">
            <div class="chat-header-info" id="chat-header-info" style="cursor: pointer;">
                <button class="btn btn-icon d-md-none me-2" id="mobile-back-btn">
                    <i class="bi bi-arrow-left"></i>
                </button>
                <div id="active-chat-avatar-container" class="d-flex align-items-center">
                    <img src="assets/images/default.png" alt="Avatar" class="avatar" id="active-chat-avatar">
                </div>
                <div>
                    <h5 class="contact-name m-0 d-flex align-items-center">
                        <span id="active-chat-status-dot" class="status-dot d-none me-2"></span>
                        <span id="active-chat-name">Select a chat</span>
                    </h5>
                    <span class="contact-status" id="active-chat-status">Online</span>
                </div>
            </div>
        </div>

        <!-- Chat Messages Container -->
        <div class="chat-messages" id="chat-messages">
            <!-- Default Empty State -->
            <div class="h-100 d-flex flex-column align-items-center justify-content-center text-muted" id="empty-chat-state">
                <i class="bi bi-chat-quote display-1 mb-3 text-secondary" style="opacity: 0.2;"></i>
                <h4>Welcome to ChatUs</h4>
                <p>Select a contact to start secure encrypted messaging.</p>
            </div>
        </div>

        <!-- Chat Input Area -->
        <div class="chat-input-area d-none" id="chat-input-area">
            <button class="btn btn-icon" id="attach-img-btn" onclick="document.getElementById('image-upload').click();"><i class="bi bi-image"></i></button>
            <input type="file" id="image-upload" class="d-none" accept="image/png, image/jpeg, image/jpg" multiple>
            
            <div class="form-check form-switch ms-2 me-2 d-flex align-items-center" title="Once-View Image">
                <input class="form-check-input" type="checkbox" id="once-view-toggle">
                <i class="bi bi-eye-slash text-warning ms-1" style="font-size: 0.8rem;"></i>
            </div>
            
            <!-- Elite Recording UI Overlay -->
            <div id="recording-overlay" class="recording-overlay d-none">
                <div class="d-flex align-items-center gap-2 w-100">
                    <button class="btn btn-icon text-danger p-0" id="discard-recording-btn"><i class="bi bi-trash3"></i></button>
                    <div class="recording-dot"></div>
                    <div id="recording-timer" class="text-white fw-bold me-2" style="min-width: 45px;">00:00</div>
                    
                    <!-- Real-time Visualizer -->
                    <canvas id="recording-visualizer" width="150" height="30" class="flex-grow-1"></canvas>
                    
                    <button class="btn btn-primary btn-icon rounded-circle ms-2" id="stop-send-record-btn">
                        <i class="bi bi-send-fill" style="transform: rotate(45deg);"></i>
                    </button>
                </div>
            </div>
            
            <div class="input-wrapper position-relative">
                <button class="btn btn-icon p-0 me-2" id="emoji-btn" type="button"><i class="bi bi-emoji-smile"></i></button>
                <input type="text" id="message-input" class="chat-input" placeholder="Type an encrypted message...">
                
                <!-- Custom Emoji Picker -->
                <div id="emoji-picker" class="emoji-picker d-none">
                    <div class="emoji-list">
                        <span>😀</span><span>😃</span><span>😄</span><span>😁</span><span>😆</span><span>😅</span><span>😂</span><span>🤣</span>
                        <span>😊</span><span>😇</span><span>🙂</span><span>🙃</span><span>😉</span><span>😌</span><span>😍</span><span>🥰</span>
                        <span>😘</span><span>😗</span><span>😙</span><span>😚</span><span>😋</span><span>😛</span><span>😜</span><span>🤪</span>
                        <span>🤨</span><span>🧐</span><span>🤓</span><span>😎</span><span>🤩</span><span>🥳</span><span>😏</span><span>😒</span>
                        <span>😞</span><span>😔</span><span>😟</span><span>😕</span><span>🙁</span><span>☹️</span><span>😣</span><span>😖</span>
                        <span>😫</span><span>😩</span><span>🥺</span><span>😢</span><span>😭</span><span>😤</span><span>😠</span><span>😡</span>
                        <span>🤯</span><span>😳</span><span>🥵</span><span>🥶</span><span>😱</span><span>😨</span><span>😰</span><span>😥</span>
                        <span>😓</span><span>🤗</span><span>🤔</span><span>🤭</span><span>🤫</span><span>🤥</span><span>😶</span><span>😐</span>
                        <span>😑</span><span>😬</span><span>🙄</span><span>😯</span><span>😦</span><span>😧</span><span>😮</span><span>😲</span>
                        <span>🥱</span><span>😴</span><span>🤤</span><span>😪</span><span>😵</span><span>🤐</span><span>🥴</span><span>🤢</span>
                        <span>🤮</span><span>🤧</span><span>😷</span><span>🤒</span><span>🤕</span><span>🤑</span><span>🤠</span><span>😈</span>
                        <span>👿</span><span>👹</span><span>👺</span><span>🤡</span><span>💩</span><span>👻</span><span>💀</span><span>☠️</span>
                        <span>👽</span><span>👾</span><span>🤖</span><span>🎃</span><span>😺</span><span>😸</span><span>😻</span><span>😼</span>
                        <span>😽</span><span>🙀</span><span>😿</span><span>😾</span><span>🤲</span><span>👐</span><span>🙌</span><span>👏</span>
                        <span>🤝</span><span>👍</span><span>👎</span><span>👊</span><span>✊</span><span>🤛</span><span>🤜</span><span>🤞</span>
                        <span>✌️</span><span>🤟</span><span>🤘</span><span>👌</span><span>👈</span><span>👉</span><span>👆</span><span>👇</span>
                        <span>☝️</span><span>✋</span><span>🤚</span><span>🖐</span><span>🖖</span><span>👋</span><span>🤙</span><span>💪</span>
                        <span>🦾</span><span>🖕</span><span>✍️</span><span>🙏</span><span>🦶</span><span>🦵</span><span>🦿</span><span>💄</span>
                        <span>💋</span><span>👄</span><span>🦷</span><span>👅</span><span>👂</span><span>🦻</span><span>👃</span><span>👣</span>
                        <span>👁</span><span>👀</span><span>🧠</span><span>🗣</span><span>👤</span><span>👥</span><span>🐶</span><span>🐱</span>
                        <span>🐭</span><span>🐹</span><span>🐰</span><span>🦊</span><span>🐻</span><span>🐼</span><span>🐨</span><span>🐯</span>
                        <span>🦁</span><span>🐮</span><span>🐷</span><span>🐽</span><span>🐸</span><span>🐵</span><span>🙈</span><span>🙉</span>
                        <span>🙊</span><span>🐒</span><span>🐔</span><span>🐧</span><span>🐦</span><span>🐤</span><span>🐣</span><span>🐥</span>
                        <span>🦆</span><span>🦅</span><span>🦉</span><span>🦇</span><span>🐺</span><span>🐗</span><span>🐴</span><span>🦄</span>
                        <span>🐝</span><span>🐛</span><span>🦋</span><span>🐌</span><span>🐞</span><span>🐜</span><span>🦟</span><span>🦗</span>
                        <span>🕷</span><span>🕸</span><span>🦂</span><span>🐢</span><span>🐍</span><span>🦎</span><span>🦖</span><span>🦕</span>
                        <span>🐙</span><span>🦑</span><span>🦐</span><span>🦞</span><span>🦀</span><span>🐡</span><span>🐠</span><span>🐟</span>
                        <span>🐬</span><span>🐳</span><span>🐋</span><span>🦈</span><span>🐊</span><span>🐅</span><span> leopards </span><span>🐆</span>
                        <span>🦓</span><span>🦍</span><span>🦧</span><span>🐘</span><span>🦛</span><span>🦏</span><span>🐪</span><span>🐫</span>
                        <span>🦒</span><span>🦘</span><span>🐃</span><span>🐂</span><span>🐄</span><span>🐎</span><span>🐖</span><span>🐏</span>
                    </div>
                </div>
            </div>
            
            <button class="btn btn-icon" id="mic-btn"><i class="bi bi-mic"></i></button>
            <button class="btn btn-send" id="send-btn"><i class="bi bi-send-fill"></i></button>
        </div>
    </div>

    <!-- RIGHT PANEL: Profile Information (Hidden by default) -->
    <aside id="right-profile-panel" class="d-none h-100 position-relative z-3">
        <div class="resizer resizer-left" id="profile-resizer"></div>
        <div class="p-4 d-flex align-items-center justify-content-between border-bottom">
            <h3 class="m-0 fw-bold text-uppercase text-muted" style="font-family: var(--font-headline); font-size: 0.75rem; letter-spacing: 0.1em;">Profile Info</h3>
            <button id="close-profile-btn" class="btn btn-sm btn-icon border-0">
                <i class="bi bi-x-lg text-muted"></i>
            </button>
        </div>
        <div class="flex-1 overflow-auto p-4" style="flex: 1; overflow-y: auto;">
            <!-- Avatar Area -->
            <div class="position-relative mb-4 text-center">
                <!-- Joined Date (Snapchat style) -->
                <div id="profile-panel-joined-container" class="mb-3 d-none">
                    <span class="snapchat-date-lg" id="profile-panel-joined-text">Joined April 2026</span>
                </div>
                
                <div class="profile-avatar-wrapper position-relative mx-auto mb-4">
                    <div id="profile-panel-img-container">
                        <img src="assets/images/default.png" class="profile-img-lg" id="profile-panel-img">
                    </div>
                </div>
                <!-- Change Image Overlay (Groups Only) -->
                <div id="change-group-img-overlay" class="d-none position-absolute top-0 start-0 w-100 h-100 bg-dark bg-opacity-50 flex-column align-items-center justify-content-center" style="cursor:pointer; opacity:0; transition: opacity 0.3s; border-radius: 24px;" onmouseenter="this.style.opacity='1'" onmouseleave="this.style.opacity='0'">
                    <div class="d-flex gap-3">
                        <div class="text-center" onclick="document.getElementById('group-image-input').click()">
                            <i class="bi bi-camera-fill text-white fs-3"></i>
                            <div class="text-white small fw-bold">UPDATE</div>
                        </div>
                        <div class="text-center" id="btn-remove-group-img-trigger">
                            <i class="bi bi-trash3-fill text-danger fs-3"></i>
                            <div class="text-danger small fw-bold">REMOVE</div>
                        </div>
                    </div>
                    <input type="file" id="group-image-input" class="d-none" accept="image/*">
                </div>
            </div>

            <!-- Profile Info Area -->
            <div class="text-center mb-4">
                <h4 id="profile-panel-name" class="m-0 text-white fw-bold mb-1" style="font-family: var(--font-headline);">Username</h4>
                <div class="d-flex align-items-center justify-content-center gap-2 mb-2">
                    <span id="profile-panel-status-dot" class="status-dot d-none" style="margin:0;"></span>
                    <div id="profile-panel-status-text" class="text-muted small text-uppercase" style="letter-spacing:0.1em; font-size:0.65rem;">OFFLINE</div>
                </div>
                
                <!-- Participant Count / Subtext -->
                <div id="profile-panel-sub-container" class="mb-3">
                    <span id="profile-panel-sub" class="text-primary fw-medium" style="font-size: 0.9rem;">@sub</span>
                </div>

                <!-- Bio / About -->
                <p id="profile-panel-about" class="text-muted small px-3" style="font-family: var(--font-body); line-height: 1.6;">About info...</p>
                
                <!-- Group Edit Name Input -->
                <div id="edit-group-name-container" class="d-none mt-3 px-3">
                    <div class="input-group input-group-sm">
                        <input type="text" id="edit-group-name-input" class="form-control bg-dark text-white border-secondary" placeholder="Group Name">
                        <button id="save-group-name-btn" class="btn btn-outline-secondary"><i class="bi bi-check-lg"></i></button>
                    </div>
                </div>
            </div>

            <hr style="border-color: rgba(255,255,255,0.05);">

            <!-- Members List (Groups Only) -->
            <div id="profile-panel-members" class="d-none mb-4">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h4 class="m-0 fw-bold text-uppercase text-muted" style="font-family: var(--font-headline); font-size: 0.7rem; letter-spacing: 0.1em;">Members</h4>
                </div>
                <!-- Dynamic Member List -->
                <div id="profile-panel-members-list" class="d-flex flex-column gap-2">
                </div>
            </div>

            <!-- Media Section -->
            <div class="space-y-4 mb-4">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h4 class="m-0 fw-bold text-uppercase text-muted" style="font-family: var(--font-headline); font-size: 0.7rem; letter-spacing: 0.1em;">Shared Media</h4>
                </div>
                <div id="profile-panel-media-grid" class="row g-2">
                    <!-- Images go here -->
                </div>
            </div>

            <!-- Groups in Common Section -->
            <div id="profile-panel-common-groups-section" class="d-none mb-4">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h4 class="m-0 fw-bold text-uppercase text-muted" style="font-family: var(--font-headline); font-size: 0.7rem; letter-spacing: 0.1em;">Groups in Common</h4>
                </div>
                <div id="profile-panel-common-groups-list" class="d-flex flex-column gap-2">
                    <!-- Shared groups go here -->
                </div>
            </div>
            
        </div>
    </aside>

</div>

<!-- Create Group Modal -->
<div class="modal fade" id="createGroupModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-primary"><i class="bi bi-people-fill me-2"></i>Create New Group</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Group Name</label>
                    <input type="text" id="new-group-name" class="form-control" placeholder="e.g. Project Alpha">
                </div>
                <div class="mb-3">
                    <label class="form-label">Select Members</label>
                    <div id="group-members-list" class="border rounded p-2" style="max-height: 200px; overflow-y: auto;">
                        <!-- Members populated via JS -->
                    </div>
                </div>
            </div>
            <div class="modal-footer border-secondary">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-sporty" id="btn-create-group">Create Group</button>
            </div>
        </div>
    </div>
</div>

<!-- Profile Modal -->
<div class="modal fade" id="profileModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-primary"><i class="bi bi-person-fill me-2"></i>Edit Profile</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="text-center mb-4">
                    <div id="profile-modal-img-container" class="mx-auto mb-3" style="width: 160px; height: 160px;">
                        <!-- Initialized via JS -->
                        <img src="" id="profile-modal-img" class="avatar lg" style="width: 160px; height: 160px; margin:0;">
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-danger border-0" id="btn-remove-pfp">
                        <i class="bi bi-trash3 me-1"></i> Remove Photo
                    </button>
                </div>

                <div class="mb-3">
                    <label class="form-label text-light fw-bold small text-uppercase" style="letter-spacing:0.05em;">Change Profile Image</label>
                    <input type="file" id="profile-img-upload" class="form-control bg-dark text-white border-secondary" accept="image/png, image/jpeg, image/jpg">
                </div>

                <div class="mb-3">
                    <label class="form-label text-light fw-bold small text-uppercase" style="letter-spacing:0.05em;">Bio / About Me</label>
                    <textarea id="profile-about-input" class="form-control bg-dark text-white border-secondary" rows="3" placeholder="Tell us about yourself..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-sporty" id="btn-save-profile">Save Changes</button>
            </div>
        </div>
    </div>
</div>

<!-- User Info Modal -->
<div class="modal fade" id="userInfoModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-info"><i class="bi bi-info-circle-fill me-2"></i>Contact Info</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center">
                <img src="" id="info-modal-img" class="rounded-circle mb-3" style="width: 120px; height: 120px; object-fit: cover; border: 3px solid var(--neon-blue);">
                <h4 id="info-modal-name" class="mb-1"></h4>
                <p id="info-modal-about" class="text-muted small mb-3"></p>
                <div class="badge bg-opacity-25 bg-primary text-primary border p-2 w-100 text-start">
                    <i class="bi bi-clock-history me-2"></i><span id="info-modal-last-active"></span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Nickname Modal -->
<div class="modal fade" id="nicknameModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-warning"><i class="bi bi-pencil-square me-2"></i>Set Nickname</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted small mb-3">Set a specific nickname for <strong id="nickname-modal-username"></strong> in this group only.</p>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-tag-fill"></i></span>
                    <input type="text" id="nickname-input" class="form-control" placeholder="Type a nickname...">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-warning btn-sm" onclick="saveNicknameFromModal()">Save</button>
            </div>
        </div>
    </div>
</div>

<!-- Add Member Modal -->
<div class="modal fade" id="addMemberModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-info"><i class="bi bi-person-plus-fill me-2"></i>Add Member</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted small mb-3">Enter the exact Username of the person you'd like to invite.</p>
                <div class="input-group">
                    <span class="input-group-text text-info"><i class="bi bi-at"></i></span>
                    <input type="text" id="add-member-input" class="form-control" placeholder="Username (e.g. ketan123)">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-info btn-sm text-dark fw-bold" onclick="saveAddMemberModal()">Add</button>
            </div>
        </div>
    </div>
</div>

<!-- Custom Alert Modal -->
<div class="modal fade" id="appAlertModal" tabindex="-1" style="z-index: 1095;">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content shadow-lg">
            <div class="modal-header">
                <h5 class="modal-title" id="appAlertTitle"><i class="bi bi-exclamation-triangle-fill text-warning me-2"></i>Alert</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center py-4">
                <p id="appAlertMessage" class="mb-0"></p>
            </div>
            <div class="modal-footer justify-content-center">
                <button type="button" class="btn btn-outline-primary btn-sm px-4" data-bs-dismiss="modal">OK</button>
            </div>
        </div>
    </div>
</div>

<!-- Custom Confirm Modal -->
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

<!-- Global App Toast Notification -->
<div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 9999;">
  <div id="appToast" class="toast align-items-center text-white border-0" role="alert" aria-live="assertive" aria-atomic="true">
    <div class="d-flex">
      <div class="toast-body fw-medium" id="appToastBody"></div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
    </div>
  </div>
</div>


<!-- Lightbox Gallery Image Viewer -->
<div id="lightbox-overlay" class="d-none">
    <div class="lightbox-backdrop"></div>
    <div class="lightbox-toolbar">
        <div class="lightbox-meta">
            <h5 id="lightbox-sender-name" class="m-0 text-white font-weight-bold"></h5>
            <small id="lightbox-timestamp" class="text-white-50"></small>
        </div>
        <button class="btn btn-icon lightbox-close-btn" id="lightbox-close"><i class="bi bi-x-lg text-white"></i></button>
    </div>
    
    <div class="lightbox-content-wrapper">
        <button class="lightbox-nav-btn" id="lightbox-prev"><i class="bi bi-chevron-left"></i></button>
        <img id="lightbox-image" src="" alt="Viewed Image">
        <button class="lightbox-nav-btn" id="lightbox-next"><i class="bi bi-chevron-right"></i></button>
    </div>
    
    <div class="lightbox-zoom-controls">
        <button class="btn btn-icon" id="lightbox-zoom-out"><i class="bi bi-zoom-out"></i></button>
        <button class="btn btn-icon" id="lightbox-zoom-reset"><i class="bi bi-arrows-fullscreen"></i></button>
        <button class="btn btn-icon" id="lightbox-zoom-in"><i class="bi bi-zoom-in"></i></button>
    </div>
</div>

<!-- View Once Secure Modal -->
<div id="viewonce-overlay" class="d-none">
    <div class="viewonce-backdrop"></div>
    <div class="viewonce-toolbar">
        <h5 class="text-danger m-0 font-weight-bold" style="font-family: 'Space Grotesk', sans-serif;"><i class="bi bi-shield-lock-fill me-2"></i>SECURE VIEW ONCE</h5>
        <button class="btn btn-icon viewonce-close-btn bg-danger text-white rounded-circle" id="viewonce-close"><i class="bi bi-x-lg"></i></button>
    </div>
    <div class="viewonce-content-wrapper">
        <img id="viewonce-image" src="" alt="Secure Image" style="-webkit-user-drag: none; user-select: none; pointer-events: none;">
    </div>
    <div class="viewonce-warning">
        <small class="text-white-50"><i class="bi bi-exclamation-triangle-fill text-warning me-1"></i>Screenshots are strictly prohibited. The file will be destroyed upon closing.</small>
    </div>
</div>

<!-- Bootstrap & Custom JS -->
<script>
    window.CURRENT_USER_ID = <?php echo json_encode($_SESSION['user_id']); ?>;
</script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/theme.js?v=<?php echo time(); ?>"></script>
    <script src="assets/js/chat.js?v=<?php echo time(); ?>"></script>
</body>
</html>
