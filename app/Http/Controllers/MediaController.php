<?php

namespace App\Http\Controllers;

use App\Models\Media;
use App\Models\Meeting;
use App\Models\Project;
use App\Models\Task;
use App\Services\FileReferenceNavigator;
use App\Services\FileReferenceSelector;
use App\Support\Files\FileReferenceContext;
use App\Support\Files\FileReferenceDestination;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MediaController extends Controller
{
    public function selectable(Request $request, FileReferenceSelector $selector): JsonResponse
    {
        $validated = $request->validate([
            'context_type' => ['required', 'in:project,task,meeting,meeting_item,comment'],
            'context_id' => ['required_unless:context_type,comment', 'integer'],
            'commentable_type' => ['required_if:context_type,comment', 'string'],
            'commentable_id' => ['required_if:context_type,comment', 'integer'],
        ]);

        return response()->json(
            $selector->select($request->user(), FileReferenceContext::fromValidated($validated))
        );
    }

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

    public function show(
        Request $request,
        string $uuid,
        FileReferenceNavigator $navigator,
    ): RedirectResponse {
        $destination = $this->referenceDestination($request, $uuid, $navigator);

        return redirect()->to($destination->url);
    }

    public function navigation(
        Request $request,
        string $uuid,
        FileReferenceNavigator $navigator,
    ): JsonResponse {
        $destination = $this->referenceDestination($request, $uuid, $navigator);

        return response()->json([
            'url' => $destination->url,
            'opens_new_tab' => $destination->opensInNewTab,
        ]);
    }

    public function thumbnail(Request $request, string $uuid)
    {
        $media = $this->visibleMedia($request, $uuid);
        $path = $this->thumbnailPath($media);

        if (
            $media->getCustomProperty('thumbnail_status') !== 'ready'
            || ! Storage::disk($media->conversions_disk ?: $media->disk)->exists($path)
        ) {
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

    private function referenceDestination(
        Request $request,
        string $uuid,
        FileReferenceNavigator $navigator,
    ): FileReferenceDestination {
        $validated = $request->validate([
            'context_type' => ['sometimes', 'string', 'in:project,task,meeting'],
            'context_id' => ['required_with:context_type', 'integer'],
            'context_project_id' => ['sometimes', 'integer'],
        ]);
        $destination = $navigator->destination(
            $request->user(),
            $this->visibleMedia($request, $uuid),
            [
                'type' => $validated['context_type'] ?? null,
                'id' => isset($validated['context_id']) ? (int) $validated['context_id'] : null,
                'project_id' => isset($validated['context_project_id'])
                    ? (int) $validated['context_project_id']
                    : null,
            ],
        );

        abort_unless($destination, 404);

        return $destination;
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
        return $media->id . '/conversions/'
            . pathinfo($media->file_name, PATHINFO_FILENAME)
            . '-thumbnail.jpg';
    }
}
