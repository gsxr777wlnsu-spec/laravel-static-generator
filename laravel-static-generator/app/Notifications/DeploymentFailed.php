<?php

namespace App\Notifications;

use App\Models\Site;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DeploymentFailed extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Site $site,
        public string $errorMessage
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Deployment Failed - {$this->site->name}")
            ->greeting("Hello {$notifiable->name}!")
            ->line("Site '{$this->site->name}' deployment has failed.")
            ->line("Error: {$this->errorMessage}")
            ->action('View Site', "https://{$this->site->domain}")
            ->line('Please check the logs for more details.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => "Deployment Failed - {$this->site->name}",
            'message' => "Site '{$this->site->name}' deployment failed: {$this->errorMessage}",
            'site_id' => $this->site->id,
            'type' => 'deployment_failed',
        ];
    }
}