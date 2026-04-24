<?php

namespace App\Notifications;

use App\Models\Site;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SiteGeneratedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Site $site,
        public bool $success,
        public int $filesGenerated = 0,
        public ?string $error = null
    ) {}

    public function via(object $notifiable): array
    {
        return ['database']; // Can add 'mail' if configured
    }

    public function toMail(object $notifiable): MailMessage
    {
        $status = $this->success ? 'Successfully Generated' : 'Generation Failed';
        
        $message = (new MailMessage)
                    ->subject("Site {$status}: {$this->site->domain}")
                    ->line("The static HTML generation for {$this->site->domain} has completed.");
                    
        if ($this->success) {
            $message->line("Generated {$this->filesGenerated} files.");
        } else {
            $message->line("It failed with the following error:");
            $message->line($this->error);
        }

        return $message->action('View Site', url('/admin/sites/' . $this->site->id));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'site_id' => $this->site->id,
            'domain' => $this->site->domain,
            'success' => $this->success,
            'files_generated' => $this->filesGenerated,
            'error' => $this->error,
            'type' => 'site_generation'
        ];
    }
}
