<?php
// Messaging navigation button component
// Add this to any page to include messaging access

// Hide globally by default. Set $GLOBALS['IR_SHOW_MESSAGING_FAB'] = true; before including
// this file if you want to display it on a specific page.
$__ir_show_fab = isset($GLOBALS['IR_SHOW_MESSAGING_FAB']) ? (bool)$GLOBALS['IR_SHOW_MESSAGING_FAB'] : false;
if (!$__ir_show_fab) { return; }

$current_user_id = getCurrentUserId();
$current_user_role = getCurrentUserRole();
$current_user_name = $_SESSION['user_name'] ?? $_SESSION['name'] ?? 'User';

// Function to get unread message count (placeholder for future enhancement)
function getUnreadMessageCount() {
    // For now, return 0 as we'll implement this later
    return 0;
}

$unread_count = getUnreadMessageCount();
?>

<!-- Messaging Navigation Button (Modern FAB) -->
<div class="ir-msg-fab" aria-live="polite">
    <a href="<?= function_exists('url') ? url('/messages') : '/Ironroot/messages' ?>" class="msg-fab" title="Open Messages" aria-label="Open Messages" data-bs-toggle="tooltip">
        <span class="fab-icon">
            <i class="fas fa-comments"></i>
            <?php if ($unread_count > 0): ?>
                <span class="fab-badge" aria-label="<?= (int)$unread_count ?> unread messages">
                    <?= $unread_count > 9 ? '9+' : (int)$unread_count; ?>
                </span>
                <span class="fab-ping" aria-hidden="true"></span>
            <?php endif; ?>
        </span>
        <span class="fab-label">Messages</span>
    </a>
</div>

<!-- Additional messaging button in navbar/header (optional) -->
<style>
:root {
    --ir-fab-size: 58px;
    --ir-fab-radius: 999px;
    --ir-fab-bg: rgba(255,255,255,0.18);
    --ir-fab-border: rgba(255,255,255,0.35);
    --ir-fab-shadow: 0 10px 30px rgba(0,0,0,0.15), 0 2px 8px rgba(0,0,0,0.08);
    --ir-fab-gradient: linear-gradient(135deg, #4f46e5, #06b6d4 60%, #22c55e);
    --ir-fab-text: #0f172a;
    --ir-fab-top-offset: 84px; /* place below header/nav */
    --ir-fab-left: auto; /* computed via JS for container alignment */
}

@media (prefers-color-scheme: dark) {
    :root {
        --ir-fab-bg: rgba(17,24,39,0.55);
        --ir-fab-border: rgba(255,255,255,0.08);
        --ir-fab-text: #e5e7eb;
    }
}

.ir-msg-fab {
    position: fixed;
    left: var(--ir-fab-left);
    top: var(--ir-fab-top-offset);
    z-index: 1050;
}

.ir-msg-fab .msg-fab {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    height: var(--ir-fab-size);
    min-width: var(--ir-fab-size);
    padding: 0 14px 0 12px;
    border-radius: var(--ir-fab-radius);
    position: relative;
    text-decoration: none;
    color: var(--ir-fab-text);
    background: var(--ir-fab-bg);
    backdrop-filter: saturate(160%) blur(10px);
    -webkit-backdrop-filter: saturate(160%) blur(10px);
    border: 1px solid var(--ir-fab-border);
    box-shadow: var(--ir-fab-shadow);
    overflow: hidden;
    transition: all .25s ease;
}

/* Gradient ring */
.ir-msg-fab .msg-fab::before {
    content: '';
    position: absolute;
    inset: -2px;
    z-index: -1;
    border-radius: var(--ir-fab-radius);
    background: var(--ir-fab-gradient);
    opacity: .8;
    filter: blur(12px);
    transform: scale(.96);
    transition: .3s ease;
}

.ir-msg-fab .msg-fab:hover::before { filter: blur(18px); opacity: 1; transform: scale(1); }

.ir-msg-fab .fab-icon {
    width: calc(var(--ir-fab-size) - 12px);
    height: calc(var(--ir-fab-size) - 12px);
    border-radius: 50%;
    display: grid; place-items: center;
    background: radial-gradient(120% 120% at 20% 20%, rgba(255,255,255,0.9) 0%, rgba(255,255,255,0.7) 40%, rgba(255,255,255,0.25) 100%);
    position: relative;
    color: #1f2937;
}

.ir-msg-fab .fab-icon i { font-size: 1.15rem; }

.ir-msg-fab .fab-label {
    font-weight: 600;
    letter-spacing: .2px;
    white-space: nowrap;
    opacity: .95;
    max-width: 160px;
}

/* Unread badge */
.ir-msg-fab .fab-badge {
    position: absolute;
    top: -6px; right: -6px;
    min-width: 22px; height: 22px;
    padding: 0 6px;
    border-radius: 999px;
    background: #ef4444;
    color: #fff;
    font-size: 12px; font-weight: 700;
    display: inline-flex; align-items: center; justify-content: center;
    box-shadow: 0 2px 8px rgba(239,68,68,.5);
}

/* Soft ping for attention */
.ir-msg-fab .fab-ping {
    position: absolute; top: -2px; right: -2px;
    width: 12px; height: 12px; border-radius: 50%;
    background: rgba(239,68,68,.6);
    animation: ping 1.9s cubic-bezier(0, 0, 0.2, 1) infinite;
}

@keyframes ping {
    0% { transform: scale(.6); opacity: .9; }
    80% { transform: scale(2.2); opacity: 0; }
    100% { opacity: 0; }
}

/* Hover lift */
.ir-msg-fab .msg-fab:hover { transform: translateY(-2px) scale(1.02); }
.ir-msg-fab .msg-fab:active { transform: translateY(0) scale(0.99); }

/* Small screens */
@media (max-width: 576px) {
    :root { --ir-fab-size: 54px; --ir-fab-top-offset: 72px; }
    .ir-msg-fab { right: 12px; }
}
</style>

<script>
// Initialize tooltips for messaging button
document.addEventListener('DOMContentLoaded', function() {
    try {
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (el) { return new bootstrap.Tooltip(el); });
    } catch(e) { /* Bootstrap may not be loaded on some pages */ }
    // Align to the right edge of the main container by computing absolute left
    function computeFabLeft(){
        var root = document.documentElement;
        var fab = document.querySelector('.ir-msg-fab .msg-fab');
        if (!fab) return;
        // Find the widest centered container
        var containers = document.querySelectorAll('.container-xxl, .container-xl, .container-lg, .container-md, main .container, .container');
        var bestRect = null;
        var viewportW = window.innerWidth || document.documentElement.clientWidth;
        containers.forEach(function(c){
            var r = c.getBoundingClientRect();
            // discard full-width containers
            if (r.width >= viewportW - 2) return;
            if (!bestRect || r.width > bestRect.width) bestRect = r;
        });
        var pad = 20; // gap from container edge
        var left = viewportW - pad - (fab.offsetWidth || 0); // default to viewport right
        if (bestRect) {
            left = Math.round(bestRect.left + bestRect.width - (fab.offsetWidth || 0) - pad);
        }
        root.style.setProperty('--ir-fab-left', left + 'px');
    }
    // compute after layout
    setTimeout(computeFabLeft, 0);
    window.addEventListener('resize', function(){
        clearTimeout(window.__irFabLeftTO);
        window.__irFabLeftTO = setTimeout(computeFabLeft, 100);
    });
});
</script>