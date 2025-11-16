<?php

namespace Modules\ApiAuth\Actions;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Modules\ApiAuth\Enums\RoleEnum;
use Modules\ApiAuth\Models\Team;

class SaveUser
{
    public function execute(array $data, User $user = new User) {
        return DB::transaction(function () use ($data, $user) {
            $roles = $data['roles'] ?? ['customer']; // fallback to 'customer'
            unset($data['roles']);

            // DO NOT include social_ keys here
            unset(
                $data['social_id'],
                $data['social_token'],
                $data['social_refresh_token'],
                $data['social_expires_in']
            );

            // Determine the team type
//            $teamType = RoleEnum::getTeamType($roles);

//            $data['user_type'] = $teamType;
            $user->fill($data);
            $user->save();

            // Create team if it's a personal/unique one, else use shared one
//            if (in_array($teamType, ['agency', 'dealer'])) {
//                if((in_array(RoleEnum::agent_admin->name, $roles) || in_array(RoleEnum::agent_admin->value, $roles) || in_array(RoleEnum::dealer->name, $roles) || in_array(RoleEnum::dealer->value, $roles))){
//                    $team = Team::create([
//                        'name' => $data['team_name'] ?? $user->name . "'s Team",
//                        'owner_id' => $user->id,
//                        'team_type' => $teamType,
//                    ]);
//                }else{
//                    $team = Team::where('owner_id', auth()->user()->getAuthIdentifier())->first();
//                }
//
//            } elseif ($teamType === 'customer') {
//                // Get or create the shared "Customers" team (no owner filter)
//                $team = Team::firstOrCreate([
//                    'name' => 'Customers',
//                    'team_type' => $teamType,
//                ], [
//                    'owner_id' => null, // do not include in where clause
//                ]);
//            } else {
//                // Default fallback to shared "System Admins" team
//                $team = Team::firstOrCreate([
//                    'name' => 'System Admins',
//                    'team_type' => 'system',
//                ], [
//                    'owner_id' => null,
//                ]);
//            }
//
//            // Attach team to user
//            $user->attachTeam($team);
//            $user->switchTeam($team);
//            $user->team_id = $team->id;
            $user->save();

            // Assign roles under the right team context
//            setPermissionsTeamId($team->id);
            $user->syncRoles($roles);

            return $user;
        });
    }
}
