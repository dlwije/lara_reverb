<?php

namespace Modules\ApiAuth\Enums;

enum RoleEnum: int
{
    case sys_admin = 1;
    case sys_subadmin = 2;
    case agent_admin = 3;
    case agent_subadmin = 4;
    case dealer = 5;
    case customer = 6;

    public static function getTeamType($roles): ?string
    {
        $teamType = null;
        foreach ($roles as $role) {
            if (in_array($role, [self::agent_admin->name, self::agent_admin->value, self::agent_subadmin->name, RoleEnum::agent_subadmin->value])) {
                $teamType = 'agency';
                break;
            } elseif (in_array($role, [RoleEnum::dealer->name, RoleEnum::dealer->value])) {
                $teamType = 'dealer';
                break;
            } elseif (in_array($role, [RoleEnum::customer->name, RoleEnum::customer->value])) {
                $teamType = 'customer';
                break;
            } elseif (in_array($role, ['sys_admin', 1, 'sys_subadmin', 2])) {
                $teamType = 'system';
                break;
            }
        }
        return $teamType;
    }
}
