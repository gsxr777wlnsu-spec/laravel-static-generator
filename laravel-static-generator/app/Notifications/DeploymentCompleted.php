<?php

namespace App\Notifications;

use App\Models\Site;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DeploymentCompleted extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Site $site,
        public int $filesCount,
        public int $duration
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Deployment Successful - {$this->site->name}")
            ->greeting("Hello {$notifiable->name}!")
            ->line("Site '{$this->site->name}' has been deployed successfully.")
            ->line("Files transferred: {$this->filesCount}")
            ->line("Duration: {$this->duration} seconds")
            ->action('View Site', "https://{$this->site->domain}")
            ->line('Thank you for using our application!');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => "Deployment Successful - {$this->site->name}",
            'message' => "Site '{$this->site->name}' has been deployed with {$this->filesCount} files.",
            'site_id' => $this->site->id,
            'type' => 'deployment_completed',
        ];
    }
}