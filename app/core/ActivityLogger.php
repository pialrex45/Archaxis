<?php
require_once __DIR__.'/auth.php';
require_once __DIR__.'/helpers.php';
require_once __DIR__.'/../../config/database.php';
require_once __DIR__.'/../models/ProjectActivity.php';
class ActivityLogger {
    public static function log($projectId,$entityType,$entityId,$action,$summary,$old=null,$new=null){
        try {
            if(!$projectId || !$entityType || !$action || !$summary) return false;
            $actor = function_exists('getCurrentUserId') ? getCurrentUserId() : null;
            $pa = new ProjectActivity();
            return $pa->add((int)$projectId,(string)$entityType,(int)$entityId,$action,$summary,$old,$new,$actor);
        } catch (Throwable $e){ return false; }
    }
}
