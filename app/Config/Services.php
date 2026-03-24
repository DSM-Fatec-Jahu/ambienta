<?php

namespace Config;

use App\Services\AuditService;
use App\Services\NotificationService;
use CodeIgniter\Config\BaseService;

class Services extends BaseService
{
    public static function audit(bool $getShared = true): AuditService
    {
        if ($getShared) {
            return static::getSharedInstance('audit');
        }

        return new AuditService();
    }

    public static function notification(bool $getShared = true): NotificationService
    {
        if ($getShared) {
            return static::getSharedInstance('notification');
        }

        return new NotificationService();
    }
}
