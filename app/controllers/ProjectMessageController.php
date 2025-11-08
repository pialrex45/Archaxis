<?php
require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../core/helpers.php';
require_once __DIR__ . '/../core/policies/MessagingPolicy.php';
require_once __DIR__ . '/../models/ProjectMessage.php';

class ProjectMessageController {
    private $model;
    private $policy;
    public function __construct() {
        $this->model = new ProjectMessage();
        $this->policy = new MessagingPolicy();
    }

    public function list($projectId, $channel=null, $before=null, $limit=50) {
        if (!isAuthenticated()) return ['success'=>false,'message'=>'User not authenticated'];
        $uid = getCurrentUserId();
        if (!$this->policy->canPostProject($uid, $projectId)) return ['success'=>false,'message'=>'Forbidden'];
        $rows = $this->model->list($projectId, $channel, $before, $limit);
        // decode metadata json for convenience
        foreach ($rows as &$r){
            if (isset($r['metadata']) && $r['metadata']){
                $decoded = json_decode($r['metadata'], true);
                if ($decoded !== null) { $r['metadata'] = $decoded; }
            }
        }
        return ['success'=>true,'data'=>$rows];
    }

    public function send($projectId, $body, $channel=null, $metadata=null) {
        if (!isAuthenticated()) return ['success'=>false,'message'=>'User not authenticated'];
        $uid = getCurrentUserId();
        if (!$this->policy->canPostProject($uid, $projectId)) return ['success'=>false,'message'=>'Forbidden'];
        $hasAttachments = is_array($metadata) && !empty($metadata['attachments']);
        if (!$body && !$hasAttachments) return ['success'=>false,'message'=>'Message body or attachment required'];
        $row = $this->model->create($projectId, $uid, $body, $channel, $metadata);
        if (!$row) return ['success'=>false,'message'=>'Failed to send'];
        return ['success'=>true,'data'=>$row];
    }

    public function channels($projectId) {
        if (!isAuthenticated()) return ['success'=>false,'message'=>'User not authenticated'];
        $uid = getCurrentUserId();
        if (!$this->policy->canPostProject($uid, $projectId)) return ['success'=>false,'message'=>'Forbidden'];
        return ['success'=>true,'data'=>$this->model->listChannels($projectId)];
    }

    public function createChannel($projectId, $key, $title) {
        if (!isAuthenticated()) return ['success'=>false,'message'=>'User not authenticated'];
        $uid = getCurrentUserId();
        if (!$this->policy->canPostProject($uid, $projectId)) return ['success'=>false,'message'=>'Forbidden'];
        if (!$key || !$title) return ['success'=>false,'message'=>'key and title required'];
        $res = $this->model->createChannel($projectId, $key, $title, $uid);
        return ['success'=>true,'data'=>$res];
    }
}
