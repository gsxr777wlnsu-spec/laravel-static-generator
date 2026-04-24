<?php

namespace Tests\Unit;

use App\Contracts\MediaRepositoryInterface;
use App\Models\Media;
use App\Models\Site;
use App\Services\MediaManagerService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class MediaManagerServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $sitesRoot = '/tmp/laravel-static-generator-tests/sites-' . Str::uuid();
        File::ensureDirectoryExists($sitesRoot);

        config()->set('filesystems.disks.sites.root', $sitesRoot);
        Storage::forgetDisk('sites');
    }

    public function test_upload_normalizes_x_webp_mime_and_generates_webp_extension_when_filename_has_no_extension(): void
    {
        $captured = null;
        $service = $this->makeService(function (array $data) use (&$captured): Media {
            $captured = $data;

            $media = new Media($data);
            $media->id = 1;
            return $media;
        });

        $file = UploadedFile::fake()->create('blob', 16, 'image/x-webp');
        $site = new Site();
        $site->id = 75;

        $service->upload($file, $site, 'Alt');

        $this->assertNotNull($captured);
        $this->assertSame('image/webp', $captured['mime_type']);
        $this->assertMatchesRegularExpression('/^75\/assets\/images\/upload\/.+\.webp$/', $captured['path']);
        Storage::disk('sites')->assertExists($captured['path']);
    }

    public function test_upload_rewrites_incompatible_extension_to_match_mime_type(): void
    {
        $captured = null;
        $service = $this->makeService(function (array $data) use (&$captured): Media {
            $captured = $data;

            $media = new Media($data);
            $media->id = 2;
            return $media;
        });

        $file = UploadedFile::fake()->create('mismatch.jpg', 16, 'image/webp');
        $site = new Site();
        $site->id = 7;

        $service->upload($file, $site, 'Alt');

        $this->assertNotNull($captured);
        $this->assertSame('image/webp', $captured['mime_type']);
        $this->assertMatchesRegularExpression('/^7\/assets\/images\/upload\/.+\.webp$/', $captured['path']);
        $this->assertStringEndsWith('.webp', $captured['path']);
        Storage::disk('sites')->assertExists($captured['path']);
    }

    private function makeService(callable $onCreate): MediaManagerService
    {
        $repository = new class($onCreate) implements MediaRepositoryInterface
        {
            public function __construct(private $onCreate) {}

            public function create(array $data): Media
            {
                return ($this->onCreate)($data);
            }

            public function update(Media $media, array $data): Media
            {
                throw new \RuntimeException('Not needed in this test');
            }

            public function delete(Media $media): bool
            {
                throw new \RuntimeException('Not needed in this test');
            }

            public function getBySite(Site $site): Collection
            {
                return new Collection();
            }
        };

        return new MediaManagerService($repository);
    }
}
