<?php

namespace Modules\ApiAuth\Actions;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\DB;
use Modules\ApiAuth\Models\Team;

class SaveTeam
{
    public function execute(array $data, Authenticatable $user,Team $team = null): Team
    {
        return DB::transaction(function () use ($data, $user, $team) {
            $team = $team ?? new Team(); // Create a new team if not provided
//            print_r($data);exit('sd');
            $team->fill($data);
            $team->save();

            $user->attachTeam($team);

            // Set the permission team context before assigning roles
            setPermissionsTeamId($team->getKey());

            // Optional: assign default role to user in this team
            $user->assignRole('agent_admin'); // or 'admin', or any role you define

            return $team;
        });
    }
}
