<?php

namespace App\Listeners;

use App\Events\AttachmentEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class AttachmentEventListener implements ShouldQueue
{
    use InteractsWithQueue;

    public function failed(AttachmentEvent $event, $exception)
    {
        logger()->error('AttachmentEventListener failed!', [
            'Error' => $exception,
            'model' => $event->model,
        ]);
    }

    public function handle(object $event): void
    {
        $event->model->moveAttachments($event->attachments);
    }
}
