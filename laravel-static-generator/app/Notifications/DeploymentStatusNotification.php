<?php

namespace App\Notifications;

use App\Models\Site;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DeploymentStatusNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Site $site,
        public bool $success,
        public ?string $error = null
    ) {}

    public function via(object $notifiable): array
    {
        return ['database']; // Can add 'mail' if configured
    }

    public function toMail(object $notifiable): MailMessage
    {
        $status = $this->success ? 'Successful' : 'Failed';
        
        $message = (new MailMessage)
                    ->subject("Deployment {$status}: {$this->site->domain}")
                    ->line("The deployment for {$this->site->domain} to remote server has completed.");
                    
        if (!$this->success && $this->error) {
            $message->line("It failed with the following error:");
            $message->line($this->error);
        }

        return $message->action('View Deployments', url('/admin/sites/' . $this->site->id));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'site_id' => $this->site->id,
            'domain' => $this->site->domain,
            'success' => $this->success,
            'error' => $this->error,
            'type' => 'site_deployment'
        ];
    }
}
