<?php

namespace Modules\InteractiveWhiteboard\Services;

use App\Models\ExternalApp;
use App\Services\ExternalApps\ExternalAppService;
use Illuminate\Support\Facades\Cache;

class WhiteboardService
{
    /**
     * Check if the whiteboard module is enabled.
     */
    public function isModuleEnabled(): bool
    {
        $app = ExternalApp::where('slug', 'interactive-whiteboard')->first();
        return $app && $app->is_enabled;
    }

    /**
     * Check if a user can draw on the whiteboard for a given course.
     * Instructors/admins can always draw. Students only if collab mode is on.
     */
    public function canUserDraw($user, $courseId): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        // Check if user is an instructor (teacher) for this course
        if ($user->courses()->where('courses.id', $courseId)->exists()) {
            return true;
        }

        // Check if collaborative mode is active
        return $this->isCollabMode($courseId);
    }

    /**
     * Check if the user is enrolled in (or teaches) the course.
     */
    public function canAccessWhiteboard($user, $courseId): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        // Instructor for this course
        if ($user->courses()->where('courses.id', $courseId)->exists()) {
            return true;
        }

        // Student enrolled in this course
        if ($user->purchasedCourses()->contains('id', $courseId)) {
            return true;
        }

        return false;
    }

    /**
     * Check if the user is an instructor/admin for this course.
     */
    public function isInstructor($user, $courseId): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->courses()->where('courses.id', $courseId)->exists();
    }

    /**
     * Check if collaborative mode is active for a session.
     * Stored in cache, keyed by course ID.
     */
    public function isCollabMode($courseId): bool
    {
        // Check cache first (set during live session)
        $cached = Cache::get("whiteboard_collab_{$courseId}");
        if ($cached !== null) {
            return (bool) $cached;
        }

        // Fall back to module default setting
        $default = ExternalAppService::staticGetModuleEnv('interactive-whiteboard', 'WHITEBOARD_COLLAB_DEFAULT', '0');
        return (bool) $default;
    }

    /**
     * Toggle collaborative mode for a session.
     */
    public function toggleCollabMode($courseId): bool
    {
        $current = $this->isCollabMode($courseId);
        $new = !$current;

        // Cache for 12 hours (covers most sessions)
        Cache::put("whiteboard_collab_{$courseId}", $new, 43200);

        return $new;
    }
}
