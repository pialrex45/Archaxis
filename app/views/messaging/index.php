<?php
// Integrated Messaging System for Ironroot Project
require_once __DIR__ . '/../../core/auth.php';
require_once __DIR__ . '/../../core/helpers.php';
// Try to include database for admin user listing (graceful fallback if unavailable)
@include_once __DIR__ . '/../../config/database.php';

// Require authentication
requireAuth();

// Get current user info
$current_user_id = getCurrentUserId();
$current_user_role = getCurrentUserRole();

// Get user details from session or database
$current_user_name = $_SESSION['user_name'] ?? $_SESSION['name'] ?? 'User';
// Prefer DB-derived display name if available
if (class_exists('Database')) {
    $dbu = dbFetchUser($current_user_id);
    if ($dbu) {
        $current_user_name = $dbu['name'] ?: $current_user_name;
        // keep session role priority; only fallback to DB role if empty
        if (empty($current_user_role)) { $current_user_role = $dbu['role'] ?? $current_user_role; }
    }
}

// File paths for messaging data
$users_file = __DIR__ . '/../../data/messaging_users.json';
$messages_file = __DIR__ . '/../../data/messaging_messages.json';

// Create data directory if it doesn't exist
$data_dir = __DIR__ . '/../../data';
if (!file_exists($data_dir)) {
    mkdir($data_dir, 0777, true);
}

// Helper functions
function loadMessagingUsers() {
    global $users_file;
    if (!file_exists($users_file)) {
        return [];
    }
    return json_decode(file_get_contents($users_file), true) ?? [];
}

// DB helpers for user names/roles
function row_get($row, $key, $idx = null) {
    if (is_array($row)) {
        if (array_key_exists($key, $row)) return $row[$key];
        if ($idx !== null && array_key_exists($idx, $row)) return $row[$idx];
        // try case variations
        $lk = strtolower($key);
        foreach ($row as $k=>$v) { if (strtolower((string)$k) === $lk) return $v; }
        return null;
    } elseif (is_object($row)) {
        if (isset($row->{$key})) return $row->{$key};
        $lk = strtolower($key);
        foreach ($row as $k=>$v) { if (strtolower((string)$k) === $lk) return $v; }
        // numeric index not applicable for objects
        return null;
    }
    return null;
}
function usersDisplayNameExpr($db) {
    static $expr = null;
    if ($expr !== null) return $expr;
    try {
        $colsRes = $db->query("SHOW COLUMNS FROM users");
        $cols = [];
        foreach (($colsRes->fetchAll() ?: []) as $c) {
            $fname = row_get($c, 'Field', 0);
            if ($fname !== null && $fname !== '') {
                $cols[strtolower((string)$fname)] = true;
            }
        }
        $pieces = [];
        if (!empty($cols['name'])) $pieces[] = "NULLIF(name,'')";
        if (!empty($cols['full_name'])) $pieces[] = "NULLIF(full_name,'')";
        if (!empty($cols['first_name']) || !empty($cols['last_name'])) {
            $fn = !empty($cols['first_name']) ? "NULLIF(first_name,'')" : "''";
            $ln = !empty($cols['last_name']) ? "NULLIF(last_name,'')" : "''";
            $pieces[] = "NULLIF(CONCAT_WS(' ', $fn, $ln),'')";
        }
        if (!empty($cols['username'])) $pieces[] = "NULLIF(username,'')";
        if (!empty($cols['email'])) $pieces[] = "NULLIF(email,'')";
        if (empty($pieces)) { $pieces[] = "'User'"; }
        $expr = 'COALESCE(' . implode(',', $pieces) . ", 'User')";
    } catch (Throwable $e) {
        $expr = "COALESCE(NULLIF(name,''), 'User')";
    }
    return $expr;
}

function dbUserMapByIds($ids) {
    $map = [];
    if (!class_exists('Database')) return $map;
    $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
    if (empty($ids)) return $map;
    try {
        $db = Database::getConnection();
        $in = implode(',', array_fill(0, count($ids), '?'));
        $expr = usersDisplayNameExpr($db);
        $sql = "SELECT id, $expr AS display_name, role FROM users WHERE id IN ($in)";
        $st = $db->prepare($sql);
        $st->execute($ids);
        foreach (($st->fetchAll() ?: []) as $u) {
            $id = (int)(row_get($u, 'id', 0) ?? 0);
            $name = (string)(row_get($u, 'display_name', 1) ?? row_get($u, 'name', 1) ?? 'User');
            $role = (string)(row_get($u, 'role', 2) ?? 'user');
            $map[$id] = [ 'id'=>$id, 'name'=>$name, 'role'=>$role ];
        }
    } catch (Throwable $e) { /* ignore */ }
    return $map;
}

function dbFetchUser($id) {
    $res = null;
    if (!class_exists('Database')) return $res;
    try {
        $db = Database::getConnection();
        $expr = usersDisplayNameExpr($db);
        $st = $db->prepare("SELECT id, $expr AS display_name, role FROM users WHERE id = ? LIMIT 1");
        $st->execute([(int)$id]);
        $u = $st->fetch();
        if ($u) {
            $res = [
                'id' => (int)(row_get($u,'id',0) ?? 0),
                'name' => (string)(row_get($u,'display_name',1) ?? row_get($u,'name',1) ?? 'User'),
                'role' => (string)(row_get($u,'role',2) ?? 'user'),
            ];
        }
    } catch (Throwable $e) { /* ignore */ }
    return $res;
}

function saveMessagingUsers($users) {
    global $users_file;
    return file_put_contents($users_file, json_encode($users, JSON_PRETTY_PRINT));
}

function loadMessages() {
    global $messages_file;
    if (!file_exists($messages_file)) {
        return [];
    }
    return json_decode(file_get_contents($messages_file), true) ?? [];
}

function saveMessages($messages) {
    global $messages_file;
    return file_put_contents($messages_file, json_encode($messages, JSON_PRETTY_PRINT));
}

// Add current user to messaging users if not exists
function ensureUserInMessaging($user_id, $user_name, $user_role) {
    $users = loadMessagingUsers();
    
    $user_exists = false;
    foreach ($users as &$user) {
        if ($user['id'] == $user_id) {
            // Update user info
            $user['name'] = $user_name;
            $user['role'] = $user_role;
            $user['last_active'] = date('Y-m-d H:i:s');
            $user_exists = true;
            break;
        }
    }
    
    if (!$user_exists) {
        $users[] = [
            'id' => $user_id,
            'name' => $user_name,
            'role' => $user_role,
            'last_active' => date('Y-m-d H:i:s')
        ];
    }
    
    saveMessagingUsers($users);
}

// Ensure current user is in messaging system
ensureUserInMessaging($current_user_id, $current_user_name, $current_user_role);

$action = $_GET['action'] ?? $_POST['action'] ?? '';

// Handle AJAX requests
if (!empty($action)) {
    header('Content-Type: application/json');
    
    switch ($action) {
        case 'get_users':
            $users = loadMessagingUsers();
            // Open messaging: for all roles, prefer full user list from DB; fallback to file
            if (class_exists('Database')) {
                try {
                    $db = Database::getConnection();
                    $expr = usersDisplayNameExpr($db);
                    $st = $db->prepare("SELECT id, $expr AS display_name, role FROM users ORDER BY display_name ASC");
                    $st->execute();
                    $dbUsers = $st->fetchAll() ?: [];
                    if (!empty($dbUsers)) {
                        $users = [];
                        foreach ($dbUsers as $u) {
                            $users[] = [
                                'id' => (int)(row_get($u,'id',0) ?? 0),
                                'name' => (string)(row_get($u,'display_name',1) ?? row_get($u,'name',1) ?? 'User'),
                                'role' => (string)(row_get($u,'role',2) ?? 'user'),
                            ];
                        }
                    }
                } catch (Throwable $e) {
                    // leave $users from file as fallback
                }
            }

            // Filter out current user only (open messaging; no role-based blocks)
            $filtered_users = array_filter($users, function($user) use ($current_user_id) {
                return isset($user['id']) && (int)$user['id'] !== (int)$current_user_id;
            });
            $filtered_users = array_map(function($u) {
                return [
                    'id' => (int)$u['id'],
                    'name' => (string)($u['name'] ?? 'User'),
                    'role' => (string)($u['role'] ?? 'user'),
                ];
            }, $filtered_users);

            // Sort by name asc
            usort($filtered_users, function($a, $b){ return strcasecmp($a['name'], $b['name']); });

            echo json_encode(['success' => true, 'users' => array_values($filtered_users)]);
            break;
        case 'get_projects':
            $projects = [];
            if (class_exists('Database')) {
                try {
                    $db = Database::getConnection();
                    if (function_exists('hasRole') && hasRole('admin')) {
                        $st = $db->query('SELECT id, name FROM projects ORDER BY name ASC');
                        $projects = $st->fetchAll() ?: [];
                    } else {
                        $pidMap = [];
                        // owner
                        $st = $db->prepare('SELECT id, name FROM projects WHERE owner_id = ?');
                        $st->execute([$current_user_id]);
                        foreach (($st->fetchAll() ?: []) as $r) { $pidMap[(int)$r['id']] = $r['name']; }
                        // tasks
                        $st = $db->prepare('SELECT DISTINCT p.id, p.name FROM tasks t INNER JOIN projects p ON p.id=t.project_id WHERE t.assigned_to = ?');
                        $st->execute([$current_user_id]);
                        foreach (($st->fetchAll() ?: []) as $r) { $pidMap[(int)$r['id']] = $r['name']; }
                        // materials (optional table)
                        try {
                            $st = $db->prepare('SELECT DISTINCT p.id, p.name FROM materials m INNER JOIN projects p ON p.id=m.project_id WHERE m.requested_by = ?');
                            $st->execute([$current_user_id]);
                            foreach (($st->fetchAll() ?: []) as $r) { $pidMap[(int)$r['id']] = $r['name']; }
                        } catch (Throwable $e) {}
                        // purchase orders (optional)
                        try {
                            $st = $db->prepare('SELECT DISTINCT p.id, p.name FROM purchase_orders po INNER JOIN projects p ON p.id=po.project_id WHERE po.created_by = ?');
                            $st->execute([$current_user_id]);
                            foreach (($st->fetchAll() ?: []) as $r) { $pidMap[(int)$r['id']] = $r['name']; }
                        } catch (Throwable $e) {}
                        // site_manager_id (optional column)
                        try {
                            $col = $db->query("SHOW COLUMNS FROM projects LIKE 'site_manager_id'");
                            if ($col && $col->rowCount() > 0) {
                                $st = $db->prepare('SELECT id, name FROM projects WHERE site_manager_id = ?');
                                $st->execute([$current_user_id]);
                                foreach (($st->fetchAll() ?: []) as $r) { $pidMap[(int)$r['id']] = $r['name']; }
                            }
                        } catch (Throwable $e) {}
                        // supervisor_id (optional column)
                        try {
                            $col = $db->query("SHOW COLUMNS FROM projects LIKE 'supervisor_id'");
                            if ($col && $col->rowCount() > 0) {
                                $st = $db->prepare('SELECT id, name FROM projects WHERE supervisor_id = ?');
                                $st->execute([$current_user_id]);
                                foreach (($st->fetchAll() ?: []) as $r) { $pidMap[(int)$r['id']] = $r['name']; }
                            }
                        } catch (Throwable $e) {}
                        // project_assignments table
                        try {
                            $tbl = $db->query("SHOW TABLES LIKE 'project_assignments'");
                            if ($tbl && $tbl->rowCount() > 0) {
                                $st = $db->prepare('SELECT DISTINCT p.id, p.name FROM project_assignments pa INNER JOIN projects p ON p.id=pa.project_id WHERE pa.user_id = ?');
                                $st->execute([$current_user_id]);
                                foreach (($st->fetchAll() ?: []) as $r) { $pidMap[(int)$r['id']] = $r['name']; }
                            }
                        } catch (Throwable $e) {}
                        foreach ($pidMap as $pid=>$pname) { $projects[] = ['id'=>$pid,'name'=>$pname]; }
                        usort($projects, function($a,$b){ return strcasecmp($a['name'],$b['name']); });
                    }
                } catch (Throwable $e) { $projects = []; }
            }
            echo json_encode(['success'=>true,'projects'=>$projects]);
            break;
        case 'get_project_members':
            $pid = (int)($_GET['project_id'] ?? 0);
            if ($pid <= 0 || !class_exists('Database')) { echo json_encode(['success'=>true,'user_ids'=>[]]); break; }
            try {
                $db = Database::getConnection();
                $uids = [];
                // owner
                $st = $db->prepare('SELECT owner_id AS uid FROM projects WHERE id = ?');
                $st->execute([$pid]);
                foreach (($st->fetchAll() ?: []) as $r) { if (!empty($r['uid'])) $uids[(int)$r['uid']] = true; }
                // tasks
                $st = $db->prepare('SELECT DISTINCT assigned_to AS uid FROM tasks WHERE project_id = ? AND assigned_to IS NOT NULL');
                $st->execute([$pid]);
                foreach (($st->fetchAll() ?: []) as $r) { if (!empty($r['uid'])) $uids[(int)$r['uid']] = true; }
                // materials
                try {
                    $st = $db->prepare('SELECT DISTINCT requested_by AS uid FROM materials WHERE project_id = ?');
                    $st->execute([$pid]);
                    foreach (($st->fetchAll() ?: []) as $r) { if (!empty($r['uid'])) $uids[(int)$r['uid']] = true; }
                } catch (Throwable $e) {}
                // purchase orders
                try {
                    $st = $db->prepare('SELECT DISTINCT created_by AS uid FROM purchase_orders WHERE project_id = ?');
                    $st->execute([$pid]);
                    foreach (($st->fetchAll() ?: []) as $r) { if (!empty($r['uid'])) $uids[(int)$r['uid']] = true; }
                } catch (Throwable $e) {}
                // site_manager_id
                try {
                    $col = $db->query("SHOW COLUMNS FROM projects LIKE 'site_manager_id'");
                    if ($col && $col->rowCount() > 0) {
                        $st = $db->prepare('SELECT site_manager_id AS uid FROM projects WHERE id = ? AND site_manager_id IS NOT NULL');
                        $st->execute([$pid]);
                        foreach (($st->fetchAll() ?: []) as $r) { if (!empty($r['uid'])) $uids[(int)$r['uid']] = true; }
                    }
                } catch (Throwable $e) {}
                // supervisor_id
                try {
                    $col = $db->query("SHOW COLUMNS FROM projects LIKE 'supervisor_id'");
                    if ($col && $col->rowCount() > 0) {
                        $st = $db->prepare('SELECT supervisor_id AS uid FROM projects WHERE id = ? AND supervisor_id IS NOT NULL');
                        $st->execute([$pid]);
                        foreach (($st->fetchAll() ?: []) as $r) { if (!empty($r['uid'])) $uids[(int)$r['uid']] = true; }
                    }
                } catch (Throwable $e) {}
                // project_manager_id optional
                try {
                    $col = $db->query("SHOW COLUMNS FROM projects LIKE 'project_manager_id'");
                    if ($col && $col->rowCount() > 0) {
                        $st = $db->prepare('SELECT project_manager_id AS uid FROM projects WHERE id = ? AND project_manager_id IS NOT NULL');
                        $st->execute([$pid]);
                        foreach (($st->fetchAll() ?: []) as $r) { if (!empty($r['uid'])) $uids[(int)$r['uid']] = true; }
                    }
                } catch (Throwable $e) {}
                // project_assignments
                try {
                    $tbl = $db->query("SHOW TABLES LIKE 'project_assignments'");
                    if ($tbl && $tbl->rowCount() > 0) {
                        $st = $db->prepare('SELECT DISTINCT user_id AS uid FROM project_assignments WHERE project_id = ?');
                        $st->execute([$pid]);
                        foreach (($st->fetchAll() ?: []) as $r) { if (!empty($r['uid'])) $uids[(int)$r['uid']] = true; }
                    }
                } catch (Throwable $e) {}
                echo json_encode(['success'=>true,'user_ids'=>array_keys($uids)]);
            } catch (Throwable $e) {
                echo json_encode(['success'=>true,'user_ids'=>[]]);
            }
            break;
            
        case 'get_conversations':
            $users = loadMessagingUsers();
            $messages = loadMessages();
            $conversations = [];
            
            // Create user lookup
            $user_lookup = [];
            foreach ($users as $user) {
                $user_lookup[$user['id']] = $user;
            }
            
            // Group messages by conversation partner
            $partners = [];
            foreach ($messages as $msg) {
                if ($msg['sender_id'] == $current_user_id) {
                    $partner_id = $msg['receiver_id'];
                } elseif ($msg['receiver_id'] == $current_user_id) {
                    $partner_id = $msg['sender_id'];
                } else {
                    continue;
                }
                
                if (!isset($partners[$partner_id])) {
                    $partners[$partner_id] = [];
                }
                $partners[$partner_id][] = $msg;
            }
            
            // Enrich names from DB for partners
            $partner_ids = array_map('intval', array_keys($partners));
            $dbMap = dbUserMapByIds($partner_ids);
            foreach ($partners as $partner_id => $partner_messages) {
                $partner = $user_lookup[$partner_id] ?? null;
                $dbu = $dbMap[$partner_id] ?? null;
                $last_message = end($partner_messages);
                $conversations[] = [
                    'other_user_id' => $partner_id,
                    'user_name' => is_array($dbu) ? ($dbu['name'] ?? 'User') : ($partner['name'] ?? 'User'),
                    'user_role' => is_array($dbu) ? ($dbu['role'] ?? 'user') : ($partner['role'] ?? 'user'),
                    'message_count' => count($partner_messages),
                    'last_message_time' => $last_message['created_at'],
                    'last_message_text' => substr($last_message['message_text'], 0, 50) . (strlen($last_message['message_text']) > 50 ? '...' : '')
                ];
            }
            
            // Sort by last message time
            usort($conversations, function($a, $b) {
                return strtotime($b['last_message_time']) - strtotime($a['last_message_time']);
            });
            
            echo json_encode(['success' => true, 'conversations' => $conversations]);
            break;
            
        case 'get_messages':
            $other_user_id = (int)$_GET['user_id'];
            $users = loadMessagingUsers();
            $messages = loadMessages();
            
            // Create user lookup
            $user_lookup = [];
            foreach ($users as $user) {
                $user_lookup[$user['id']] = $user;
            }
            
            // Filter messages for this conversation
            $conversation_messages = array_filter($messages, function($msg) use ($current_user_id, $other_user_id) {
                return ($msg['sender_id'] == $current_user_id && $msg['receiver_id'] == $other_user_id) ||
                       ($msg['sender_id'] == $other_user_id && $msg['receiver_id'] == $current_user_id);
            });
            
            // Add sender names (prefer DB)
            $ids_for_names = [];
            foreach ($conversation_messages as $m){ $ids_for_names[] = (int)$m['sender_id']; $ids_for_names[] = (int)$m['receiver_id']; }
            $ids_for_names = array_values(array_unique($ids_for_names));
            $dbNames = dbUserMapByIds($ids_for_names);
            foreach ($conversation_messages as &$msg) {
                $sid = (int)$msg['sender_id'];
                $dbn = $dbNames[$sid] ?? null;
                $msg['sender_name'] = is_array($dbn) ? ($dbn['name'] ?? 'Unknown') : ($user_lookup[$sid]['name'] ?? 'Unknown');
            }
            
            // Sort by time
            usort($conversation_messages, function($a, $b) {
                return strtotime($a['created_at']) - strtotime($b['created_at']);
            });
            
            echo json_encode(['success' => true, 'messages' => array_values($conversation_messages)]);
            break;
            
        case 'send_message':
            $receiver_id = (int)$_POST['receiver_id'];
            $message_text = trim($_POST['message_text']);
            
            if ($receiver_id && $message_text) {
                $messages = loadMessages();
                
                // Generate new ID
                $max_id = 0;
                foreach ($messages as $msg) {
                    if ($msg['id'] > $max_id) $max_id = $msg['id'];
                }
                
                $new_message = [
                    'id' => $max_id + 1,
                    'sender_id' => $current_user_id,
                    'receiver_id' => $receiver_id,
                    'message_text' => $message_text,
                    'created_at' => date('Y-m-d H:i:s')
                ];
                
                $messages[] = $new_message;
                
                if (saveMessages($messages)) {
                    echo json_encode(['success' => true, 'message' => 'Message sent']);
                } else {
                    echo json_encode(['success' => false, 'error' => 'Failed to save message']);
                }
            } else {
                echo json_encode(['success' => false, 'error' => 'Missing data']);
            }
            break;
            
        case 'get_stats':
            $users = loadMessagingUsers();
            $messages = loadMessages();
            
            $my_messages = array_filter($messages, function($msg) use ($current_user_id) {
                return $msg['sender_id'] == $current_user_id || $msg['receiver_id'] == $current_user_id;
            });
            
            echo json_encode([
                'success' => true,
                'stats' => [
                    'total_users' => count($users),
                    'total_messages' => count($messages),
                    'my_messages' => count($my_messages)
                ]
            ]);
            break;
            
        default:
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
    }
    exit;
}

$pageTitle = 'Messages';
$currentPage = 'messages';
?>

<?php include_once __DIR__ . '/../layouts/header.php'; ?>

<style>
.messaging-container { 
    max-width: 1200px; 
    margin: 0 auto; 
}
.chat-container { 
    height: 500px; 
    overflow-y: auto; 
    border: 2px solid #e9ecef;
    border-radius: 12px;
    padding: 20px;
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
}
.message-bubble { 
    max-width: 75%; 
    margin-bottom: 15px; 
    padding: 12px 18px;
    border-radius: 20px;
    position: relative;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}
.message-sent { 
    background: linear-gradient(135deg, #007bff, #0056b3);
    color: white; 
    margin-left: auto; 
    text-align: right;
}
.message-received { 
    background: white;
    border: 1px solid #e9ecef;
}
.conversation-item { 
    cursor: pointer; 
    transition: all 0.3s ease;
    border-radius: 10px;
    margin-bottom: 8px;
    border: 1px solid #e9ecef;
}
.conversation-item:hover { 
    background-color: #e3f2fd; 
    transform: translateX(8px);
    box-shadow: 0 3px 10px rgba(0,0,0,0.1);
}
.conversation-item.active { 
    background: linear-gradient(135deg, #007bff, #0056b3);
    color: white; 
    transform: translateX(12px);
    box-shadow: 0 5px 15px rgba(0,123,255,0.3);
}
.chip { display:inline-flex; align-items:center; gap:.35rem; padding:.25rem .5rem; border-radius:999px; font-size:.75rem; font-weight:600; }
.chip-admin { background:#111827; color:#fff; }
.chip-client { background:#06b6d4; color:#05262e; }
.chip-project_manager { background:#7c3aed; color:#fff; }
.chip-site_manager { background:#f59e0b; color:#331e0a; }
.chip-supervisor { background:#22c55e; color:#052e18; }
.chip-worker { background:#9ca3af; color:#111827; }
.chip-site_engineer { background:#2dd4bf; color:#052e2a; }
.chip-logistic_officer { background:#3b82f6; color:#0b1e3a; }
.chip-sub_contractor { background:#ec4899; color:#3a061e; }
.chip-general_manager { background:#ef4444; color:#2d0a0a; }

/* Modern gradients for headers */
.card .card-header.bg-success { background: linear-gradient(90deg, #16a34a, #22c55e); }
.card .card-header.bg-info { background: linear-gradient(90deg, #06b6d4, #0ea5e9); }

/* Improve select look */
#userSelect { font-size: .95rem; }
.navigation-buttons {
    position: sticky;
    top: 0;
    z-index: 1000;
    background: white;
    border-bottom: 2px solid #e9ecef;
    margin-bottom: 20px;
}
.user-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: linear-gradient(135deg, #6c757d, #495057);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    box-shadow: 0 2px 5px rgba(0,0,0,0.2);
}
</style>

<div class="messaging-container">
    <!-- Navigation -->
    <div class="navigation-buttons p-3">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h3 class="mb-0">
                    <i class="fas fa-comments me-2 text-primary"></i>
                    Messages
                </h3>
                <small class="text-muted">
                    Logged in as: <strong><?php echo htmlspecialchars($current_user_name); ?></strong> 
                    (<?php echo htmlspecialchars($current_user_role); ?>)
                </small>
            </div>
            <div>
                <a href="<?php echo function_exists('url') ? url('/dashboard') : '/Ironroot/dashboard'; ?>" class="btn btn-outline-primary">
                    <i class="fas fa-tachometer-alt me-2"></i>Back to Dashboard
                </a>
            </div>
        </div>
    </div>

    <!-- Messaging Interface -->
    <div class="row">
        <!-- Users & Conversations -->
        <div class="col-md-4">
            <div class="card shadow">
                <div class="card-header bg-success text-white">
                    <h6 class="mb-0"><i class="fas fa-users me-2"></i>Start New Chat</h6>
                </div>
                <div class="card-body">
                    <div class="row g-2 align-items-center mb-2">
                        <div class="col-4">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text"><i class="fas fa-filter"></i></span>
                                <select id="roleFilter" class="form-select">
                                    <option value="">All roles</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text"><i class="fas fa-diagram-project"></i></span>
                                <select id="projectFilter" class="form-select">
                                    <option value="">All projects</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text"><i class="fas fa-search"></i></span>
                                <input id="userSearch" type="text" class="form-control" placeholder="Search users..." />
                            </div>
                        </div>
                    </div>
                    <select id="userSelect" class="form-select mb-3" size="10">
                        <option value="">Loading users...</option>
                    </select>
                    <button class="btn btn-success btn-sm w-100" onclick="refreshData()">
                        <i class="fas fa-sync me-2"></i>Refresh
                    </button>
                </div>
            </div>
            
            <div class="card mt-3 shadow">
                <div class="card-header bg-info text-white">
                    <h6 class="mb-0"><i class="fas fa-history me-2"></i>Your Conversations</h6>
                </div>
                <div class="card-body p-2">
                    <div id="conversationsList" style="max-height: 400px; overflow-y: auto;">
                        Loading conversations...
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Chat Area -->
        <div class="col-md-8">
            <div class="card shadow">
                <div id="chatHeader" class="card-header d-none">
                    <div class="d-flex align-items-center">
                        <div class="user-avatar me-3" id="chatAvatar">U</div>
                        <div>
                            <h6 class="mb-0" id="chatUserName">Select a user</h6>
                            <small class="text-muted" id="chatUserRole">Role</small>
                        </div>
                        <div class="ms-auto">
                            <span class="badge bg-success">
                                <i class="fas fa-circle me-1" style="font-size: 8px;"></i>Active
                            </span>
                        </div>
                    </div>
                </div>
                
                <div class="card-body p-0">
                    <div id="messagesContainer" class="chat-container">
                        <div class="text-center mt-5">
                            <i class="fas fa-comments fa-4x mb-3 text-primary opacity-75"></i>
                            <h4 class="text-primary">Welcome to Ironroot Messaging</h4>
                            <p class="text-muted">Select a user from the list to start a conversation</p>
                            <div class="badge bg-success fs-6">
                                <i class="fas fa-shield-alt me-2"></i>Secure • Private • Integrated
                            </div>
                        </div>
                    </div>
                </div>
                
                <div id="messageForm" class="card-footer d-none">
                    <div class="input-group">
                        <input type="text" id="messageInput" class="form-control" placeholder="Type your message..." maxlength="500">
                        <button class="btn btn-primary" onclick="sendMessage()">
                            <i class="fas fa-paper-plane me-2"></i>Send
                        </button>
                    </div>
                    <div class="d-flex justify-content-between mt-2">
                        <small class="text-muted">Press Enter to send</small>
                        <small class="text-muted"><span id="charCount">0</span>/500 characters</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats -->
    <div class="row mt-3">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-body">
                    <div id="statsDisplay" class="text-center">
                        <div class="d-flex justify-content-center gap-4">
                            <div class="badge bg-primary fs-6">
                                <i class="fas fa-users me-1"></i>Loading stats...
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let currentChatUserId = null;
// fast refresh interval (2s)
const refreshIntervalMs = 2000;
// request guards to avoid overlapping calls
let isLoadingUsers = false;
let isLoadingConversations = false;
let isLoadingMessages = false;
let isLoadingStats = false;
// user cache for client-side filtering and grouping
let ALL_USERS = [];
let PROJECTS = [];
let PROJECT_MEMBERS = {}; // pid -> Set(userId)

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    console.log('Initializing integrated messaging system...');
    refreshData();
    loadStats();
    
    // Auto-refresh every 2 seconds
    setInterval(() => {
        loadConversations();
        if (currentChatUserId) {
            loadMessages(currentChatUserId);
        }
        // Stats and users are lighter but don't need 2s frequency.
        // Keep stats fast; users list refresh is manual or on-demand.
        loadStats();
    }, refreshIntervalMs);
    
    // Character counter
    document.getElementById('messageInput').addEventListener('input', function() {
        document.getElementById('charCount').textContent = this.value.length;
    });

    // Filters wiring
    const rf = document.getElementById('roleFilter');
    const us = document.getElementById('userSearch');
    const pf = document.getElementById('projectFilter');
    if (rf) rf.addEventListener('change', renderUsers);
    if (us) us.addEventListener('input', ()=>{ /* debounce-lite */ clearTimeout(window.__us_to); window.__us_to=setTimeout(renderUsers,150); });
    if (pf) pf.addEventListener('change', onProjectChanged);
});

function refreshData() {
    const btn = document.querySelector('.card .btn.btn-success.btn-sm.w-100');
    if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Refreshing...'; }
    
    // Force users fetch once on manual refresh
    loadUsers(true).finally(() => {
        if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fas fa-sync me-2"></i>Refresh'; }
    });
    loadConversations();
    if (currentChatUserId) {
        loadMessages(currentChatUserId);
    }
}

function loadUsers(force = false) {
    if (isLoadingUsers && !force) return Promise.resolve();
    isLoadingUsers = true;
    const select = document.getElementById('userSelect');
    if (select) select.innerHTML = '<option value="">Loading users...</option>';
    
    return fetch(`?action=get_users&ts=${Date.now()}`)
        .then(res => res.ok ? res.json() : Promise.reject(new Error('HTTP '+res.status)))
        .then(data => {
            const s = document.getElementById('userSelect');
            const rf = document.getElementById('roleFilter');
            const pf = document.getElementById('projectFilter');
            if (!s) return;
            if (data && data.success) {
                ALL_USERS = Array.isArray(data.users) ? data.users.slice() : [];
                populateRoleFilter(rf, ALL_USERS);
                // load projects after users
                loadProjects().then(()=>renderUsers());
                console.log(`✓ Loaded ${ALL_USERS.length} users`);
            } else {
                s.innerHTML = '<option value="" disabled>Failed to load users — click Refresh</option>';
            }
        })
        .catch(err => {
            console.error('Error loading users:', err);
            const s = document.getElementById('userSelect');
            if (s) s.innerHTML = '<option value="" disabled>Error loading users — click Refresh</option>';
        })
        .finally(() => { isLoadingUsers = false; });
}

async function loadProjects(){
    const pf = document.getElementById('projectFilter'); if (!pf) return;
    try {
        const res = await fetch(`?action=get_projects&ts=${Date.now()}`);
        const j = await res.json();
        PROJECTS = (j && j.success && Array.isArray(j.projects)) ? j.projects : [];
        const current = pf.value;
        pf.innerHTML = '<option value="">All projects</option>' + PROJECTS.map(p=>`<option value="${p.id}">${p.name}</option>`).join('');
        if (current) pf.value = current;
        // If a project is selected, refresh members
        if (pf.value) await ensureProjectMembersLoaded(pf.value);
    } catch (e) { /* ignore */ }
}

async function ensureProjectMembersLoaded(projectId){
    if (PROJECT_MEMBERS[projectId]) return;
    try{
        const res = await fetch(`?action=get_project_members&project_id=${encodeURIComponent(projectId)}&ts=${Date.now()}`);
        const j = await res.json();
        const ids = (j && j.success && Array.isArray(j.user_ids)) ? j.user_ids : [];
        PROJECT_MEMBERS[projectId] = new Set(ids.map(n=>Number(n)));
    }catch(e){ PROJECT_MEMBERS[projectId] = new Set(); }
}

async function onProjectChanged(){
    const pf = document.getElementById('projectFilter');
    const pid = (pf?.value || '').trim();
    if (pid){ await ensureProjectMembersLoaded(pid); }
    renderUsers();
}

function normalizeRole(r){ return String(r||'').toLowerCase(); }
function roleLabel(r){
    const map = {
        admin:'Admin', client:'Client', project_manager:'Project Manager', site_manager:'Site Manager', supervisor:'Supervisor', worker:'Worker', site_engineer:'Site Engineer', logistic_officer:'Logistic Officer', sub_contractor:'Sub Contractor', general_manager:'General Manager'
    };
    const key = normalizeRole(r);
    return map[key] || r;
}

function populateRoleFilter(selectEl, users){
    if (!selectEl) return;
    const roles = Array.from(new Set(users.map(u=>normalizeRole(u.role)))).filter(Boolean).sort();
    const current = selectEl.value;
    selectEl.innerHTML = '<option value="">All roles</option>' + roles.map(r=>`<option value="${r}">${roleLabel(r)}</option>`).join('');
    if (roles.includes(current)) selectEl.value = current; // preserve when possible
}

function renderUsers(){
    const s = document.getElementById('userSelect'); if (!s) return;
    const role = normalizeRole(document.getElementById('roleFilter')?.value || '');
    const search = (document.getElementById('userSearch')?.value || '').toLowerCase().trim();
    const pid = (document.getElementById('projectFilter')?.value || '').trim();
    const members = pid ? (PROJECT_MEMBERS[pid] || null) : null;
    const filtered = ALL_USERS.filter(u=>{
        if (role && normalizeRole(u.role) !== role) return false;
        if (search && !String(u.name||'').toLowerCase().includes(search)) return false;
        if (pid && members && !members.has(Number(u.id))) return false;
        return true;
    });
    // group by role and sort
    const byRole = {};
    for (const u of filtered){ const k = normalizeRole(u.role); if(!byRole[k]) byRole[k]=[]; byRole[k].push(u); }
    Object.keys(byRole).forEach(k=> byRole[k].sort((a,b)=> String(a.name).localeCompare(String(b.name))));
    const roleOrder = ['admin','project_manager','client','general_manager','site_manager','supervisor','site_engineer','logistic_officer','sub_contractor','worker'];
    const roles = Object.keys(byRole).sort((a,b)=>{
        const ia = roleOrder.indexOf(a); const ib = roleOrder.indexOf(b);
        if (ia === -1 && ib === -1) return a.localeCompare(b); if (ia === -1) return 1; if (ib === -1) return -1; return ia-ib;
    });
    // build options with optgroups
    s.innerHTML = '';
    if (pid && members === null){ s.innerHTML = '<option value="" disabled>Loading project members...</option>'; return; }
    if (!filtered.length){ s.innerHTML = '<option value="" disabled>No users match your filter</option>'; return; }
    for (const r of roles){
        const og = document.createElement('optgroup');
        og.label = roleLabel(r);
        byRole[r].forEach(u=>{
            const opt = document.createElement('option');
            opt.value = u.id;
            opt.textContent = `${u.name} (${normalizeRole(u.role)})`;
            opt.dataset.role = normalizeRole(u.role);
            og.appendChild(opt);
        });
        s.appendChild(og);
    }
}

function loadConversations() {
    if (isLoadingConversations) return;
    isLoadingConversations = true;
    fetch(`?action=get_conversations&ts=${Date.now()}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const container = document.getElementById('conversationsList');
                
                if (!data.conversations || data.conversations.length === 0) {
                    container.innerHTML = `
                        <div class="text-center p-4">
                            <i class="fas fa-comment-dots fa-2x text-muted mb-2"></i>
                            <div class="text-muted">No conversations yet</div>
                            <small>Start messaging someone!</small>
                        </div>
                    `;
                } else {
                    let html = '';
                    data.conversations.forEach(conv => {
                        const isActive = conv.other_user_id == currentChatUserId ? 'active' : '';
                        const avatar = conv.user_name?.charAt(0)?.toUpperCase() || 'U';
                        
                        html += `
                            <div class="conversation-item p-3 ${isActive}" onclick="openChat(${conv.other_user_id}, '${conv.user_name}', '${conv.user_role}')">
                                <div class="d-flex align-items-center">
                                    <div class="user-avatar me-3" style="width: 35px; height: 35px; font-size: 14px;">${avatar}</div>
                                    <div class="flex-grow-1">
                                        <div class="fw-bold small">${conv.user_name}</div>
                                        <div class="small opacity-75">${conv.last_message_text}</div>
                                        <div class="text-muted small">${conv.message_count} messages • ${new Date(conv.last_message_time).toLocaleDateString()}</div>
                                    </div>
                                </div>
                            </div>
                        `;
                    });
                    container.innerHTML = html;
                }
            }
        })
        .catch(error => console.error('Error loading conversations:', error))
        .finally(() => { isLoadingConversations = false; });
}

function openChat(userId, userName, userRole) {
    currentChatUserId = userId;
    
    // Update UI
    document.getElementById('chatUserName').textContent = userName;
    document.getElementById('chatUserRole').textContent = userRole;
    document.getElementById('chatAvatar').textContent = userName.charAt(0).toUpperCase();
    document.getElementById('chatHeader').classList.remove('d-none');
    document.getElementById('messageForm').classList.remove('d-none');
    
    // Update conversations list
    loadConversations();
    loadMessages(userId);
}

function loadMessages(userId) {
    if (isLoadingMessages) return;
    isLoadingMessages = true;
    fetch(`?action=get_messages&user_id=${userId}&ts=${Date.now()}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const container = document.getElementById('messagesContainer');
                
                if (data.messages.length === 0) {
                    container.innerHTML = `
                        <div class="text-center mt-5">
                            <i class="fas fa-comment fa-3x text-muted mb-3"></i>
                            <h5>No messages yet</h5>
                            <p class="text-muted">Start the conversation!</p>
                        </div>
                    `;
                } else {
                    let html = '';
                    data.messages.forEach(msg => {
                        const isOwn = msg.sender_id == <?php echo $current_user_id; ?>;
                        const bubbleClass = isOwn ? 'message-sent ms-auto' : 'message-received';
                        const time = new Date(msg.created_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
                        
                        html += `
                            <div class="message-bubble ${bubbleClass}">
                                <div class="mb-1">${msg.message_text}</div>
                                <small class="opacity-75">
                                    ${msg.sender_name} • ${time}
                                </small>
                            </div>
                        `;
                    });
                    container.innerHTML = html;
                }
                
        container.scrollTop = container.scrollHeight;
            }
        })
    .catch(error => console.error('Error loading messages:', error))
    .finally(() => { isLoadingMessages = false; });
}

function sendMessage() {
    const messageInput = document.getElementById('messageInput');
    const messageText = messageInput.value.trim();
    
    if (!messageText || !currentChatUserId) return;
    
    const formData = new FormData();
    formData.append('action', 'send_message');
    formData.append('receiver_id', currentChatUserId);
    formData.append('message_text', messageText);
    
    messageInput.disabled = true;
    
    fetch('', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        messageInput.disabled = false;
        
        if (data.success) {
            messageInput.value = '';
            document.getElementById('charCount').textContent = '0';
            loadMessages(currentChatUserId);
            loadConversations();
            loadStats();
        } else {
            alert('Failed to send message: ' + (data.error || 'Unknown error'));
        }
    })
    .catch(error => {
        messageInput.disabled = false;
        console.error('Error sending message:', error);
    });
}

function loadStats() {
    if (isLoadingStats) return;
    isLoadingStats = true;
    fetch(`?action=get_stats&ts=${Date.now()}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const stats = data.stats;
                document.getElementById('statsDisplay').innerHTML = `
                    <div class="d-flex justify-content-center gap-4">
                        <div class="badge bg-primary fs-6">
                            <i class="fas fa-users me-1"></i>${stats.total_users} Users
                        </div>
                        <div class="badge bg-success fs-6">
                            <i class="fas fa-comments me-1"></i>${stats.total_messages} Total Messages
                        </div>
                        <div class="badge bg-info fs-6">
                            <i class="fas fa-user-check me-1"></i>${stats.my_messages} Your Messages
                        </div>
                    </div>
                `;
            }
    })
    .catch(error => console.error('Error loading stats:', error))
    .finally(() => { isLoadingStats = false; });
}

// Handle Enter key
document.addEventListener('keypress', function(e) {
    if (e.key === 'Enter' && e.target.id === 'messageInput' && !e.shiftKey) {
        e.preventDefault();
        sendMessage();
    }
});

// Handle user selection
document.getElementById('userSelect').addEventListener('change', function() {
    const userId = this.value;
    if (userId) {
        const userName = this.options[this.selectedIndex].text.split(' (')[0];
        const userRole = this.options[this.selectedIndex].text.match(/\(([^)]+)\)/)[1];
        openChat(parseInt(userId), userName, userRole);
        this.value = '';
    }
});
</script>

<?php include_once __DIR__ . '/../layouts/footer.php'; ?>