<?php

namespace App\Notifications;

use App\Models\Site;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class GenerationCompleted extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Site $site,
        public int $pagesCount,
        public int $filesCount
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Generation Complete - {$this->site->name}")
            ->greeting("Hello {$notifiable->name}!")
            ->line("Site '{$this->site->name}' has been generated successfully.")
            ->line("Pages: {$this->pagesCount}")
            ->line("Total files: {$this->filesCount}")
            ->action('View in Admin', route('admin.sites.edit', $this->site->id))
            ->line('You can now deploy this site to production.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => "Generation Complete - {$this->site->name}",
            'message' => "Site '{$this->site->name}' generated with {$this->pagesCount} pages and {$this->filesCount} files.",
            'site_id' => $this->site->id,
            'type' => 'generation_completed',
        ];
    }
}
