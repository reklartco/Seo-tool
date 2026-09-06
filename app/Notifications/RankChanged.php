<?php

namespace App\Notifications;

use App\Models\Keyword;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RankChanged extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Keyword $keyword, public ?int $previousRank = null) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $direction = $this->keyword->rank_delta > 0 ? 'yükseldi' : 'düştü';

        return (new MailMessage)
            ->subject("\"{$this->keyword->keyword}\" {$direction}: #{$this->keyword->current_rank}")
            ->line("{$this->keyword->project->domain} · {$this->keyword->keyword}")
            ->line("Önceki sıra: {$this->previousRank} → Şimdi: #{$this->keyword->current_rank}")
            ->action('Kelimeyi gör', url(route('keywords')));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'rank_changed',
            'project_id' => $this->keyword->project_id,
            'keyword_id' => $this->keyword->id,
            'keyword' => $this->keyword->keyword,
            'previous_rank' => $this->previousRank,
            'current_rank' => $this->keyword->current_rank,
            'delta' => $this->keyword->rank_delta,
            'message' => "\"{$this->keyword->keyword}\" {$this->previousRank} → #{$this->keyword->current_rank}",
        ];
    }
}
