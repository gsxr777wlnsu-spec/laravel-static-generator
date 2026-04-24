<?php

namespace App\Http\Controllers\Api;

use App\Contracts\AuditLogServiceInterface;
use App\Contracts\ImageProcessorInterface;
use App\Contracts\MediaManagerInterface;
use App\Contracts\MediaRepositoryInterface;
use App\Contracts\SiteRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Models\Media;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MediaController extends Controller
{
    public function __construct(
        private MediaManagerInterface $manager,
        private MediaRepositoryInterface $media,
        private SiteRepositoryInterface $sites,
        private ImageProcessorInterface $processor,
        private AuditLogServiceInterface $audit
    ) {}

    public function index(Request $request): JsonResponse
    {
        $siteId = $request->query('site_id');
        
        if ($siteId) {
            $site = $this->sites->findById($siteId);
            if (!$site) {
                 return response()->json(['error' => 'Site not found'], 404);
             }
             
             // Trigger discovery to sync database with filesystem
             $this->manager->discoverExistingMedia($site);
             
             $media = $this->media->getBySite($site);
        } else {
            $media = Media::all();
        }

        return response()->json($media);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'site_id' => 'required|exists:sites,id',
            // Do not use `image|mimes` here: some TinyMCE WebP blobs fail extension guessing.
            // MediaManagerService performs strict MIME validation.
            'file' => 'required|file|max:10240',
            'alt' => 'required|string|max:255',
            'title' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $site = $this->sites->findById($request->site_id);
        
        try {
            $media = $this->manager->upload(
                $request->file('file'),
                $site,
                $request->alt,
                $request->title
            );
            
            $this->audit->log('media.uploaded', Media::class, $media->id, null, $media->toArray());

            return response()->json($media, 201);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function show(int $id): JsonResponse
    {
        $media = Media::find($id);

        if (!$media) {
            return response()->json(['error' => 'Media not found'], 404);
        }

        return response()->json($media);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $media = Media::find($id);

        if (!$media) {
            return response()->json(['error' => 'Media not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'alt' => 'sometimes|string|max:255',
            'title' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $oldValues = $media->toArray();
        $media = $this->media->update($media, $validator->validated());
        
        $this->audit->log('media.updated', Media::class, $media->id, $oldValues, $media->toArray());

        return response()->json($media);
    }

    public function destroy(int $id): JsonResponse
    {
        $media = Media::find($id);

        if (!$media) {
            return response()->json(['error' => 'Media not found'], 404);
        }

        $this->audit->log('media.deleted', Media::class, $media->id, $media->toArray(), null);
        
        $this->manager->delete($media);

        return response()->json(['message' => 'Media deleted successfully']);
    }

    public function resize(Request $request, int $id): JsonResponse
    {
        $media = Media::find($id);

        if (!$media) {
            return response()->json(['error' => 'Media not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'width' => 'required|integer|min:1',
            'height' => 'required|integer|min:1',
            'preserve_aspect_ratio' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $newMedia = $this->processor->resize(
                $media,
                $request->width,
                $request->height,
                $request->preserve_aspect_ratio ?? true
            );

            return response()->json($newMedia, 201);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
