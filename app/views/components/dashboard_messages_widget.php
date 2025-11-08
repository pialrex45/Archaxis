<?php
// Lightweight dashboard widget showing recent DMs and quick links to messaging
?>
<div class="row mb-4">
  <div class="col-md-12">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Messages</h5>
        <div>
          <a class="btn btn-sm btn-outline-primary" href="<?= url('/messages') ?>">Open DMs</a>
          <a class="btn btn-sm btn-outline-secondary ms-2" href="<?= url('/messages/project') ?>">Project Messages</a>
        </div>
      </div>
      <div class="card-body">
        <div class="row">
          <div class="col-md-6 mb-3">
            <h6 class="text-muted">Recent Conversations</h6>
            <ul id="dashMsgList" class="list-group small"></ul>
          </div>
          <div class="col-md-6 mb-3">
            <h6 class="text-muted">My Projects</h6>
            <ul id="dashProjList" class="list-group small"></ul>
          </div>
        </div>
      </div>
    </div>
  </div>
  <script src="<?= url('/assets/js/dashboard_messages_widget.js') ?>" defer></script>
  <style>
    #dashMsgList .list-group-item, #dashProjList .list-group-item { display:flex; justify-content:space-between; align-items:center; }
    .truncate { max-width: 70%; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
  </style>
</div>
