<?php
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
require_once __DIR__ . '/../../core/auth.php';
require_once __DIR__ . '/../../core/helpers.php';
requireAuth();

$csrf = generate_csrf_token();
$pageTitle = isset($pageTitle) ? $pageTitle : 'Project Messages';
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
    .pm-layout { display:grid; grid-template-columns: 280px 1fr 300px; gap:12px; }
    .pm-panel { border:1px solid #ddd; border-radius:8px; background:#fff; overflow:hidden; }
    .pm-header { padding:10px; background:#f7f7f7; border-bottom:1px solid #eee; font-weight:600; }
    .pm-list { max-height:65vh; overflow:auto; }
    .pm-item { padding:10px; border-bottom:1px solid #f1f1f1; cursor:pointer; }
    .pm-item.active { background:#eef6ff; }
    .pm-chat { display:flex; flex-direction:column; height:100%; }
    .pm-chat-body { padding:12px; flex:1; overflow:auto; }
    .pm-msg { margin:8px 0; max-width:70%; padding:8px 12px; border-radius:12px; }
    .pm-msg.me { margin-left:auto; background:#e6f4ff; }
    .pm-msg.them { margin-right:auto; background:#f3f4f6; }
    .pm-chat-footer { border-top:1px solid #eee; padding:10px; background:#fafafa; }
    .pm-compose { display:flex; gap:8px; }
    .pm-compose textarea { width:100%; padding:8px; }
    .pm-tabs { display:flex; gap:8px; padding:8px; border-bottom:1px solid #eee; background:#fcfcfc; }
    .pm-tab { padding:6px 10px; border:1px solid #ddd; border-radius:16px; cursor:pointer; background:#fff; }
    .pm-tab.active { background:#e9f5ff; border-color:#b8dcff; }
  </style>
</head>
<body>
  <?php include_once __DIR__ . '/../layouts/header.php'; ?>
  <?php include_once __DIR__ . '/../layouts/sidebar.php'; ?>

  <div class="col-md-9 col-lg-10 ms-sm-auto px-md-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
      <h1 class="h2"><?= htmlspecialchars($pageTitle) ?></h1>
    </div>

    <div class="pm-layout">
      <aside class="pm-panel">
        <div class="pm-header">Projects</div>
        <div id="pmProjectList" class="pm-list"></div>
      </aside>

      <section class="pm-panel pm-chat">
        <div class="pm-header" id="pmProjectTitle">Select a project</div>
        <div class="pm-tabs" id="pmChannelTabs"></div>
        <div id="pmMessageList" class="pm-chat-body"></div>
        <div class="pm-chat-footer">
          <form id="pmSendForm" class="pm-compose">
            <input type="hidden" id="pmProjectId" value="">
            <input type="hidden" id="pmChannelKey" value="general">
            <textarea id="pmBody" rows="2" placeholder="Type a message..."></textarea>
            <input type="file" id="pmFile" multiple>
            <button type="submit" class="btn btn-primary">Send</button>
          </form>
        </div>
      </section>

      <aside class="pm-panel">
        <div class="pm-header">Project Details</div>
        <div id="pmProjectDetails" class="p-3 small text-muted">Select a project to view details.</div>
      </aside>
    </div>
  </div>

  <?php include_once __DIR__ . '/../layouts/footer.php'; ?>
  <script src="<?= url('/public/assets/js/project_messages.js') ?>" defer></script>
</body>
</html>
