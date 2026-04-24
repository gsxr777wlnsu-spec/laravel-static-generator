<?php

namespace App\Jobs;

use App\Contracts\ImageProcessorInterface;
use App\Models\Media;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class GenerateWebPJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Media $media
    ) {}

    public function handle(ImageProcessorInterface $processor): void
    {
        try {
            $processor->generateWebP($this->media);
            Log::info('WebP generated successfully', ['media_id' => $this->media->id]);
        } catch (\Exception $e) {
            Log::error('WebP generation failed', [
                'media_id' => $this->media->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}
