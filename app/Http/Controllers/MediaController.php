<?php

namespace App\Http\Controllers;

use App\Models\Media;
use App\Models\Meeting;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MediaController extends Controller
{
    public function storeProject(Request $request, Project $project)
    {
        return $this->storeForOwner($request, $project);
    }

    public function storeTask(Request $request, Task $task)
    {
        return $this->storeForOwner($request, $task);
    }

    public function storeMeeting(Request $request, Meeting $meeting)
    {
        return $this->storeForOwner($request, $meeting);
    }

    private function storeForOwner(Request $request, Model $owner)
    {
        Gate::forUser($request->user())->authorize('create', [Media::class, $owner]);

        $validated = $request->validate([
            'file' => ['required', 'file', 'max:102400'],
        ]);

        $media = DB::transaction(function () use ($owner, $request, $validated): Media {
            $media = $owner->addMedia($validated['file'])->toMediaCollection();

            activity()
                ->useLog('file')
                ->event('uploaded')
                ->performedOn($media)
                ->causedBy($request->user())
                ->withProperties([
                    'attributes' => [
                        'uuid' => $media->uuid,
                        'owner_type' => $media->model_type,
                        'owner_id' => $media->model_id,
                        'size' => $media->size,
                    ],
                ])
                ->log('uploaded');

            return $media;
        });

        return back()->with('alert-success', "Arquivo {$media->display_name} enviado com sucesso.");
    }

    public function metadata(Request $request, string $uuid): JsonResponse
    {
        $media = $this->visibleMedia($request, $uuid);

        $payload = [
            'uuid' => $media->uuid,
            'name' => $media->display_name,
            'extension' => pathinfo($media->file_name, PATHINFO_EXTENSION),
            'size' => $media->size,
            'mime_type' => $media->mime_type,
            'uploaded_at' => $media->created_at?->toIso8601String(),
            'thumbnail_status' => $media->getCustomProperty('thumbnail_status'),
        ];

        if (Gate::forUser($request->user())->allows('viewOriginal', $media)) {
            $payload['original_name'] = $media->original_name;
        }

        return response()->json($payload);
    }

    public function download(Request $request, string $uuid)
    {
        $media = $this->visibleMedia($request, $uuid);

        return Storage::disk($media->disk)->download(
            $media->getPathRelativeToRoot(),
            $this->downloadName($media),
            [
                'Content-Type' => 'application/octet-stream',
                'X-Content-Type-Options' => 'nosniff',
            ],
        );
    }

    public function thumbnail(Request $request, string $uuid)
    {
        $media = $this->visibleMedia($request, $uuid);
        $path = $this->thumbnailPath($media);

        if ($media->getCustomProperty('thumbnail_status') !== 'ready'
            || ! Storage::disk($media->conversions_disk ?: $media->disk)->exists($path)) {
            abort(404);
        }

        return response(
            Storage::disk($media->conversions_disk ?: $media->disk)->get($path),
            200,
            [
                'Content-Type' => 'image/jpeg',
                'X-Content-Type-Options' => 'nosniff',
            ],
        );
    }

    public function update(Request $request, string $uuid)
    {
        $media = Media::query()->where('uuid', $uuid)->firstOrFail();
        Gate::forUser($request->user())->authorize('update', $media);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        DB::transaction(function () use ($media, $request, $validated): void {
            $oldName = $media->display_name;
            $media->display_name = $validated['name'];
            $media->save();

            activity()
                ->useLog('file')
                ->event('renamed')
                ->performedOn($media)
                ->causedBy($request->user())
                ->withProperties([
                    'attributes' => ['name' => $media->display_name],
                    'old' => ['name' => $oldName],
                ])
                ->log('renamed');
        });

        return back()->with('alert-success', 'Nome do Arquivo atualizado com sucesso.');
    }

    public function destroy(Request $request, string $uuid)
    {
        $media = Media::query()->where('uuid', $uuid)->firstOrFail();
        Gate::forUser($request->user())->authorize('delete', $media);

        DB::transaction(function () use ($media, $request): void {
            activity()
                ->useLog('file')
                ->event('deleted')
                ->performedOn($media)
                ->causedBy($request->user())
                ->withProperties([
                    'old' => [
                        'uuid' => $media->uuid,
                        'owner_type' => $media->model_type,
                        'owner_id' => $media->model_id,
                        'size' => $media->size,
                    ],
                ])
                ->log('deleted');

            $media->delete();
        });

        return back()->with('alert-success', 'Arquivo excluído definitivamente.');
    }

    private function visibleMedia(Request $request, string $uuid): Media
    {
        $media = Media::query()->where('uuid', $uuid)->first();

        if (! $media || ! Gate::forUser($request->user())->allows('view', $media)) {
            abort(404);
        }

        return $media;
    }

    private function downloadName(Media $media): string
    {
        $extension = strtolower((string) pathinfo($media->file_name, PATHINFO_EXTENSION));
        $name = Str::ascii((string) $media->display_name);
        $name = preg_replace('/[\\x00-\\x1F\\x7F"\\\\\\/;]+/', ' ', $name) ?? '';
        $name = trim(preg_replace('/\\s+/', ' ', $name) ?? '');
        $name = trim((string) pathinfo($name, PATHINFO_FILENAME));
        $name = $name !== '' ? $name : 'arquivo';

        return $extension !== '' ? "{$name}.{$extension}" : $name;
    }

    private function thumbnailPath(Media $media): string
    {
        return $media->id.'/conversions/'
            .pathinfo($media->file_name, PATHINFO_FILENAME)
            .'-thumbnail.jpg';
    }
}
