<?php

namespace Prasso\BedrockHtmlEditor\Traits;

/**
 * Trait UserRoleHelpers
 * 
 * Provides helper methods for checking user roles and permissions
 */
trait UserRoleHelpers
{
    /**
     * Check if the user is a super admin
     *
     * @return bool
     */
    public function isSuperAdmin(): bool
    {
        // Check if the user has the super admin role
        return $this->hasRole(config('constants.SUPER_ADMIN'));
    }

    /**
     * Check if the user is an instructor
     *
     * @return bool
     */
    public function isInstructor(): bool
    {
        // Check if the user has the instructor role
        return $this->hasRole(config('constants.INSTRUCTOR'));
    }

    /**
     * Check if the user belongs to a team
     *
     * @param mixed $team
     * @return bool
     */
    public function belongsToTeam($team): bool
    {
        // If the team is null, return false
        if (!$team) {
            return false;
        }

        // Check if the user is the team owner
        if ($this->ownsTeam($team)) {
            return true;
        }

        // Check if the user is a member of the team
        return $this->teams->contains($team->id);
    }
}
