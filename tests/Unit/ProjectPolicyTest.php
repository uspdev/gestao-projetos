<?php

namespace Tests\Unit;

use App\Models\Project;
use App\Models\ProjectUser;
use App\Models\User;
use App\Policies\ProjectPolicy;
use Illuminate\Database\Eloquent\Collection;
use Tests\TestCase;

class ProjectPolicyTest extends TestCase
{
    public function test_only_a_local_project_admin_can_view_its_activity(): void
    {
        $project = new Project();
        $project->forceFill(['id' => 10]);

        $admin = $this->member(1, 'Admin do projeto', 'ADMIN', 10);
        $viewer = $this->member(2, 'Visualizador do projeto', 'VIEWER', 10);
        $project->setRelation('users', new Collection([$admin, $viewer]));

        $policy = new ProjectPolicy();

        $this->assertTrue($policy->viewActivity($admin, $project));
        $this->assertFalse($policy->viewActivity($viewer, $project));
    }

    private function member(int $id, string $name, string $role, int $projectId): User
    {
        $user = new User();
        $user->forceFill(['id' => $id, 'name' => $name]);

        $membership = new ProjectUser();
        $membership->forceFill([
            'project_id' => $projectId,
            'user_id' => $id,
            'role' => $role,
        ]);

        $user->setRelation('pivot', $membership);

        return $user;
    }
}
