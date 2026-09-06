<?php

namespace App\Notifications;

use App\Models\Crawl;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CrawlFinished extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Crawl $crawl) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $project = $this->crawl->project;

        return (new MailMessage)
            ->subject("{$project->domain} taraması bitti — skor {$this->crawl->health_score}")
            ->greeting('Merhaba!')
            ->line("{$project->domain} sitesinin taraması tamamlandı.")
            ->line("Taranan sayfa: {$this->crawl->pages_crawled}")
            ->line("Açık hata: {$this->crawl->issues_count} (yeni: {$this->crawl->new_issues})")
            ->line("Sağlık skoru: {$this->crawl->health_score}/100")
            ->action('Panele git', url(route('dashboard')));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'crawl_finished',
            'project_id' => $this->crawl->project_id,
            'crawl_id' => $this->crawl->id,
            'health_score' => $this->crawl->health_score,
            'issues_count' => $this->crawl->issues_count,
            'message' => "{$this->crawl->project->domain} taraması bitti · skor {$this->crawl->health_score}",
        ];
    }
}
