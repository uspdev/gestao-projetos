<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\ProjectUser;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Tests\TestCase;

class OrganizationalProjectSettingsTest extends TestCase
{
    public function test_inheritance_card_explains_access_and_links_to_the_members_page(): void
    {
        $organizationalProject = $this->project([
            'id' => 1,
            'name' => 'Programa Institucional',
            'slug' => 'programa-institucional',
        ]);

        $html = view('projects.partials.show.subproject-permissions-card', [
            'project' => $organizationalProject,
        ])->render();

        $this->assertStringContainsString('Herança nos subprojetos', $html);
        $this->assertStringContainsString(
            'A herança não os adiciona automaticamente à equipe do subprojeto.',
            $html,
        );
        $this->assertStringContainsString('Sem Herança', $html);
        $this->assertStringContainsString('Apenas Leitura', $html);
        $this->assertStringContainsString('Herança Total', $html);
        $this->assertStringContainsString('Gerenciar membros dos subprojetos', $html);
        $this->assertStringContainsString(
            route('projects.subprojects.members', $organizationalProject),
            $html,
        );
    }

    public function test_subproject_members_card_lists_direct_members_roles_and_inheritance(): void
    {
        $organizationalProject = $this->project([
            'id' => 1,
            'name' => 'Programa Institucional',
            'slug' => 'programa-institucional',
        ]);

        $admin = $this->member(10, 'Ana Admin', 2, 'ADMIN');
        $viewer = $this->member(11, 'Vitor Viewer', 2, 'VIEWER');

        $subprojectWithMembers = $this->project([
            'id' => 2,
            'name' => 'Subprojeto com equipe',
            'slug' => 'subprojeto-com-equipe',
            'parent_id' => 1,
            'permission_inheritance' => 'FULL',
        ]);
        $subprojectWithMembers->setRelation('users', new Collection([$admin, $viewer]));

        $subprojectWithoutMembers = $this->project([
            'id' => 3,
            'name' => 'Subprojeto sem equipe',
            'slug' => 'subprojeto-sem-equipe',
            'parent_id' => 1,
            'permission_inheritance' => 'NONE',
        ]);
        $subprojectWithoutMembers->setRelation('users', new Collection());

        $organizationalProject->setRelation(
            'children',
            new Collection([$subprojectWithMembers, $subprojectWithoutMembers]),
        );

        $html = view('projects.partials.show.subproject-members-card', [
            'project' => $organizationalProject,
        ])->render();

        $this->assertStringContainsString('Membros dos subprojetos', $html);
        $this->assertStringContainsString('Voltar às configurações', $html);
        $this->assertStringNotContainsString(
            'A herança não os adiciona automaticamente à equipe do subprojeto.',
            $html,
        );
        $this->assertStringContainsString('Subprojeto com equipe', $html);
        $this->assertStringContainsString('2 membros diretos', $html);
        $this->assertStringContainsString('Herança Total', $html);
        $this->assertStringContainsString('Ana Admin', $html);
        $this->assertStringContainsString('Admin', $html);
        $this->assertStringContainsString('Vitor Viewer', $html);
        $this->assertStringContainsString('Visualizador', $html);
        $this->assertStringContainsString('Subprojeto sem equipe', $html);
        $this->assertStringContainsString('Sem Herança', $html);
        $this->assertStringContainsString(
            'Nenhum membro vinculado diretamente a este subprojeto.',
            $html,
        );
    }

    public function test_subproject_members_card_explains_when_there_are_no_subprojects(): void
    {
        $organizationalProject = $this->project([
            'id' => 1,
            'name' => 'Programa Institucional',
            'slug' => 'programa-institucional',
        ]);
        $organizationalProject->setRelation('children', new Collection());

        $html = view('projects.partials.show.subproject-members-card', [
            'project' => $organizationalProject,
        ])->render();

        $this->assertStringContainsString(
            'Este projeto organizacional ainda não possui subprojetos.',
            $html,
        );
    }

    private function project(array $attributes): Project
    {
        $project = new Project();
        $project->forceFill($attributes);

        return $project;
    }

    private function member(int $id, string $name, int $projectId, string $role): User
    {
        $member = new User();
        $member->forceFill([
            'id' => $id,
            'name' => $name,
        ]);

        $membership = new ProjectUser();
        $membership->forceFill([
            'project_id' => $projectId,
            'user_id' => $id,
            'role' => $role,
        ]);

        $member->setRelation('pivot', $membership);

        return $member;
    }
}
