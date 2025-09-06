<?php

namespace Prasso\BedrockHtmlEditor\Models\Extensions;

use App\Models\User;
use Prasso\BedrockHtmlEditor\Traits\UserRoleHelpers;

/**
 * This class extends the User model with additional methods
 * needed by the Bedrock HTML Editor package
 */
class UserExtension
{
    /**
     * Register the trait with the User model
     */
    public static function registerExtensions()
    {
        // Add the UserRoleHelpers trait to the User model if it doesn't already have these methods
        if (!method_exists(User::class, 'isSuperAdmin')) {
            User::resolveRelationUsing('isSuperAdmin', function ($model) {
                return $model->hasRole(config('constants.SUPER_ADMIN'));
            });
        }
        
        if (!method_exists(User::class, 'isInstructor')) {
            User::resolveRelationUsing('isInstructor', function ($model) {
                return $model->hasRole(config('constants.INSTRUCTOR'));
            });
        }
        
        if (!method_exists(User::class, 'belongsToTeam')) {
            User::resolveRelationUsing('belongsToTeam', function ($model, $team) {
                if (!$team) {
                    return false;
                }
                
                // Check if the user is the team owner
                if ($model->ownsTeam($team)) {
                    return true;
                }
                
                // Check if the user is a member of the team
                return $model->teams->contains($team->id);
            });
        }
    }
}
