<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\DeploymentCompleted;
use App\Notifications\DeploymentFailed;
use App\Notifications\GenerationCompleted;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;

class NotificationService
{
    public function notifyAdmins(object $notification): void
    {
        $query = User::query();
        if (Schema::hasColumn('users', 'is_admin')) {
            $query->where('is_admin', true);
        }

        $admins = $query->get();
        if ($admins->isNotEmpty()) {
            Notification::send($admins, $notification);
        }
    }

    public function notifyUser(User $user, object $notification): void
    {
        Notification::send($user, $notification);
    }

    public function notifyDeploymentComplete(User $user, $site, int $filesCount, int $duration): void
    {
        $this->notifyUser($user, new DeploymentCompleted($site, $filesCount, $duration));
    }

    public function notifyDeploymentFailed(User $user, $site, string $errorMessage): void
    {
        $this->notifyUser($user, new DeploymentFailed($site, $errorMessage));
    }

    public function notifyGenerationComplete(User $user, $site, int $pagesCount, int $filesCount): void
    {
        $this->notifyUser($user, new GenerationCompleted($site, $pagesCount, $filesCount));
    }

    public function notifyAllAdminsDeploymentComplete($site, int $filesCount, int $duration): void
    {
        $this->notifyAdmins(new DeploymentCompleted($site, $filesCount, $duration));
    }

    public function notifyAllAdminsDeploymentFailed($site, string $errorMessage): void
    {
        $this->notifyAdmins(new DeploymentFailed($site, $errorMessage));
    }

    public function notifyAllAdminsGenerationComplete($site, int $pagesCount, int $filesCount): void
    {
        $this->notifyAdmins(new GenerationCompleted($site, $pagesCount, $filesCount));
    }
}
