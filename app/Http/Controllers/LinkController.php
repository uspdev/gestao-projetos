<?php

namespace App\Http\Controllers;

use App\Models\Link;
use App\Models\Meeting;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\MessageBag;

class LinkController extends Controller
{
    public function storeProject(Request $request, Project $project): RedirectResponse
    {
        return $this->storeForOwner($request, $project);
    }

    public function storeTask(Request $request, Task $task): RedirectResponse
    {
        return $this->storeForOwner($request, $task);
    }

    public function storeMeeting(Request $request, Meeting $meeting): RedirectResponse
    {
        return $this->storeForOwner($request, $meeting);
    }

    public function update(Request $request, string $uuid): RedirectResponse
    {
        $link = $this->visibleLink($request, $uuid);
        Gate::forUser($request->user())->authorize('update', $link);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'url' => ['required', 'string', 'max:2048'],
        ]);

        if (! $this->isExternalHttpUrl($validated['url'])) {
            return back()
                ->withFragment(deep_link_fragment($link))
                ->withErrors(['url' => 'Informe uma URL externa válida com http:// ou https://.']);
        }

        DB::transaction(function () use ($link, $validated, $request): void {
            $old = $link->only(['name', 'url']);
            $link->update($validated);

            // Geração de logs
            activity()
                ->useLog('link')
                ->event('updated')
                ->performedOn($link)
                ->causedBy($request->user())
                ->withProperties(['old' => $old, 'attributes' => $link->only(['uuid', 'name', 'url'])])
                ->log('updated');
        });

        return back()
            ->withFragment(deep_link_fragment($link))
            ->with('alert-success', 'Link atualizado com sucesso.');
    }

    public function destroy(Request $request, string $uuid): RedirectResponse
    {
        $link = $this->visibleLink($request, $uuid);
        Gate::forUser($request->user())->authorize('delete', $link);

        $browserFragment = $this->browserFragment($link->linkable);

        DB::transaction(function () use ($link, $request): void {
            activity()
                ->useLog('link')
                ->event('deleted')
                ->performedOn($link)
                ->causedBy($request->user())
                ->withProperties(['old' => $link->only(['uuid', 'name', 'url', 'linkable_type', 'linkable_id'])])
                ->log('deleted');

            $link->delete();
        });

        return back()
            ->withFragment($browserFragment)
            ->with('alert-success', 'Link excluído definitivamente.');
    }

    private function storeForOwner(Request $request, Model $owner): RedirectResponse
    {
        Gate::forUser($request->user())->authorize('create', [Link::class, $owner]);
        $ownerPageUrl = $this->ownerPageUrl($request, $owner);

        $validated = $request->validate([
            'urls' => ['required', 'string', 'max:20000'],
        ]);
        // URLs separadas em um array, sem repetições.
        $urls = collect(preg_split('/\R/u', $validated['urls']) ?: [])
            ->map(fn(string $url): string => trim($url))
            ->filter()
            ->unique()
            ->values();

        $errors = new MessageBag();
        if ($urls->isEmpty()) {
            $errors->add('urls', 'Informe ao menos uma URL.');
        }

        $urls->each(function (string $url, int $index) use ($errors): void {
            if (! $this->isExternalHttpUrl($url)) {
                $errors->add('urls', 'Linha ' . ($index + 1) . ': informe uma URL externa válida com http:// ou https://.');
            }
        });

        if ($errors->isNotEmpty()) {
            return redirect()->to($ownerPageUrl)
                ->withFragment($this->browserFragment($owner))
                ->withErrors($errors)
                ->withInput();
        }
        // Executa a criação dos links e seus registros de atividade em uma transação,
        // garantindo que todas as operações sejam desfeitas caso ocorra algum erro.
        $createdLinks = DB::transaction(function () use ($owner, $urls, $request) {
            return $urls->map(function (string $url) use ($owner, $request): Link {
                $link = $owner->links()->create([
                    'name' => $url,
                    'url' => $url,
                    'created_by' => $request->user()->id,
                ]);

                activity()
                    ->useLog('link')
                    ->event('created')
                    ->performedOn($link)
                    ->causedBy($request->user())
                    ->withProperties(['attributes' => $link->only(['uuid', 'name', 'url', 'linkable_type', 'linkable_id'])])
                    ->log('created');

                return $link;
            });
        });

        return redirect()->to($ownerPageUrl)
            ->withFragment(deep_link_fragment($createdLinks->last()))
            ->with('alert-success', $urls->count() . ' Link(s) adicionado(s) com sucesso.');
    }

    private function ownerPageUrl(Request $request, Model $owner): string
    {
        if ($owner instanceof Project) {
            return route('projects.show', $owner);
        }

        if ($owner instanceof Task) {
            return route('tasks.show', $owner);
        }

        if ($owner instanceof Meeting) {
            $owner->loadMissing('projects');
            $project = $owner->projects
                ->sortBy('name')
                ->first(fn (Project $project): bool => $request->user()->isViewerOfProject($project));

            abort_unless($project, 404);

            return route('projects.meetings.show', [$project, $owner]);
        }

        abort(404);
    }

    private function visibleLink(Request $request, string $uuid): Link
    {
        $link = Link::query()->where('uuid', $uuid)->first();

        if (! $link || ! Gate::forUser($request->user())->allows('view', $link)) {
            abort(404);
        }

        return $link;
    }

    private function isExternalHttpUrl(string $url): bool
    {
        $parts = parse_url($url);

        return filter_var($url, FILTER_VALIDATE_URL) !== false
            && is_array($parts)
            && isset($parts['host'])
            && in_array(strtolower((string) ($parts['scheme'] ?? '')), ['http', 'https'], true);
    }

    private function browserFragment(Model $owner): string
    {
        return 'files-'.$owner->getMorphClass().'-'.$owner->getKey();
    }
}
