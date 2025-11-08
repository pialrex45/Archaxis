<?php
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
require_once __DIR__ . '/../../core/auth.php';
require_once __DIR__ . '/../../core/helpers.php';
requireAuth();

$csrf = generate_csrf_token();
$pageTitle = 'Messages';
$currentPage = 'messages';
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pageTitle) ?></title>
  <meta name="csrf-token" content="<?= htmlspecialchars($csrf) ?>">
  <link rel="stylesheet" href="<?= url('/public/assets/css/style.css') ?>">
  <style>
    .messages-layout { display: grid; grid-template-columns: 300px 1fr; gap: 16px; }
    .threads { border: 1px solid #ddd; border-radius: 8px; overflow: hidden; }
    .threads-header { padding: 10px; font-weight: 600; background:#f7f7f7; border-bottom:1px solid #eee; }
    .thread-list { max-height: 65vh; overflow: auto; }
    .thread-item { padding: 10px; cursor: pointer; border-bottom: 1px solid #f1f1f1; }
    .thread-item.active { background: #eef6ff; }
    .thread-item .name { font-weight: 600; }
    .conversation { border: 1px solid #ddd; border-radius: 8px; display:flex; flex-direction:column; overflow:hidden; }
    .conversation-header { padding: 10px; font-weight: 600; background:#f7f7f7; border-bottom:1px solid #eee; display:flex; justify-content:space-between; align-items:center; }
    .conversation-body { padding: 12px; flex:1; overflow:auto; background:#fff; }
    .msg { margin: 8px 0; max-width: 70%; padding: 8px 12px; border-radius: 12px; }
    .msg.me { margin-left: auto; background: #e6f4ff; }
    .msg.them { margin-right: auto; background: #f3f4f6; }
    .conversation-footer { border-top:1px solid #eee; padding: 10px; background:#fafafa; }
    .compose { display:flex; gap:8px; }
    .compose input, .compose textarea { width:100%; padding:8px; }
    .compose button { padding:8px 12px; }
    .toolbar { display:flex; gap:8px; }
  </style>
</head>
<body>
  <?php include_once __DIR__ . '/../layouts/header.php'; ?>
  <?php include_once __DIR__ . '/../layouts/sidebar.php'; ?>

  <div class="col-md-9 col-lg-10 ms-sm-auto px-md-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
      <h1 class="h2"><?= htmlspecialchars($pageTitle) ?></h1>
      <div class="btn-toolbar mb-2 mb-md-0">
        <a href="<?php echo url('/messages/compose'); ?>" class="btn btn-sm btn-outline-primary">
          <i class="fas fa-plus"></i> Compose
        </a>
      </div>
    </div>

    <div class="container">
      <div class="messages-layout">
        <aside class="threads">
          <div class="threads-header">Conversations</div>
          <div class="thread-list" id="threadList"></div>
        </aside>

        <section class="conversation">
          <div class="conversation-header">
            <div id="convTitle">Select a conversation</div>
            <div class="toolbar">
              <button id="btnNew">New Message</button>
              <button id="btnRefresh">Refresh</button>
            </div>
          </div>
          <div class="conversation-body" id="messageList"></div>
          <div class="conversation-footer">
            <form id="replyForm" class="compose">
              <input type="hidden" id="threadUserId" name="to_user_id" value="">
              <textarea id="replyBody" rows="2" placeholder="Type a message..."></textarea>
              <button type="submit">Send</button>
            </form>
          </div>
        </section>
      </div>
    </div>

    <dialog id="composeDialog">
      <form method="dialog" id="composeForm">
        <h3>New Message</h3>
        <label>To (User ID)
          <input type="number" id="composeToUserId" required>
        </label>
        <label>Message
          <textarea id="composeBody" rows="3" required></textarea>
        </label>
        <menu>
          <button value="cancel">Cancel</button>
          <button id="composeSend" value="default">Send</button>
        </menu>
      </form>
    </dialog>

    <?php include_once __DIR__ . '/../layouts/footer.php'; ?>

    <script src="<?= url('/public/assets/js/messages.js') ?>" defer></script>
  </body>
</html>