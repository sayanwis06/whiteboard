<?php

namespace Modules\InteractiveWhiteboard\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\View;
use Modules\InteractiveWhiteboard\Services\WhiteboardService;
use App\Events\WhiteboardDrawEvent;
use App\Events\WhiteboardCollabToggled;

class WhiteboardController extends Controller
{
    protected $service;

    public function __construct()
    {
        $this->service = new WhiteboardService();

        // Register module views directory so we can use view('whiteboard::...')
        View::addNamespace('whiteboard', base_path('modules/interactive-whiteboard/views'));
    }

    /**
     * Inject module-specific Pusher configuration into Laravel's broadcasting system.
     */
    protected function injectPusherConfig()
    {
        $appService = new \App\Services\ExternalApps\ExternalAppService();
        $slug = 'interactive-whiteboard';
        
        if (!$appService->staticGetModuleEnv($slug, 'PUSHER_APP_KEY')) {
            return;
        }

        $autoloadPath = base_path('modules/interactive-whiteboard/vendor/autoload.php');
        if (file_exists($autoloadPath) && !class_exists('\Pusher\Pusher')) {
            require $autoloadPath;
        }

        config(['broadcasting.default' => 'pusher']);
        config(['broadcasting.connections.pusher' => [
            'driver'  => 'pusher',
            'key'     => $appService->staticGetModuleEnv($slug, 'PUSHER_APP_KEY'),
            'secret'  => $appService->staticGetModuleEnv($slug, 'PUSHER_APP_SECRET'),
            'app_id'  => $appService->staticGetModuleEnv($slug, 'PUSHER_APP_ID'),
            'options' => [
                'cluster'   => $appService->staticGetModuleEnv($slug, 'PUSHER_APP_CLUSTER', 'mt1'),
                'useTLS'    => true,
            ],
        ]]);
    }

    /**
     * Handle presence channel authentication for Laravel Echo
     */
    public function authenticate(Request $request)
    {
        $user = auth()->user();
        $channelName = $request->channel_name;
        $socketId = $request->socket_id;

        \Log::info('Whiteboard Auth Request', ['channel' => $channelName, 'user' => $user->id]);

        if (!$this->service->isModuleEnabled()) {
            \Log::warning('Whiteboard Auth Failed: Module disabled');
            return response()->json(['message' => 'Module disabled'], 403);
        }

        // Extract courseId from channel name (presence-whiteboard.{courseId})
        $courseId = str_replace('presence-whiteboard.', '', $channelName);
        
        // Fix for when the prefix is missing or different
        if ($courseId === $channelName) {
            $courseId = str_replace('whiteboard.', '', $channelName);
        }

        // Authorization Logic
        $isAuthorized = false;
        $isInstructor = false;

        if ((int) $courseId === 0) {
            // Sandbox Mode
            $isAuthorized = true;
            $isInstructor = true;
        } else {
            // Real Course Session
            $isAuthorized = $this->service->canAccessWhiteboard($user, $courseId);
            $isInstructor = $this->service->isInstructor($user, $courseId);
        }

        if (!$isAuthorized) {
            \Log::warning('Whiteboard Auth Failed: User not authorized', ['user' => $user->id, 'course' => $courseId]);
            return response()->json(['message' => 'Forbidden'], 403);
        }

        // Load Pusher SDK
        $autoloadPath = base_path('modules/interactive-whiteboard/vendor/autoload.php');
        if (file_exists($autoloadPath) && !class_exists('\Pusher\Pusher')) {
            require $autoloadPath;
        }

        // Initialize Pusher directly with module credentials
        $appService = new \App\Services\ExternalApps\ExternalAppService();
        $slug = 'interactive-whiteboard';
        
        $pusher = new \Pusher\Pusher(
            $appService->staticGetModuleEnv($slug, 'PUSHER_APP_KEY'),
            $appService->staticGetModuleEnv($slug, 'PUSHER_APP_SECRET'),
            $appService->staticGetModuleEnv($slug, 'PUSHER_APP_ID'),
            [
                'cluster' => $appService->staticGetModuleEnv($slug, 'PUSHER_APP_CLUSTER', 'mt1'),
                'useTLS' => true,
            ]
        );

        $presenceData = [
            'id' => $user->id,
            'name' => $user->full_name,
            'is_instructor' => $isInstructor
        ];

        // Generate and return auth response
        $auth = $pusher->presence_auth($channelName, $socketId, $user->id, $presenceData);
        
        return response()->json(json_decode($auth, true));
    }

    /**
     * Test connection to Pusher for the configuration script.
     */
    public function testConnection(Request $request)
    {
        try {
            $request->validate([
                'PUSHER_APP_ID' => 'required',
                'PUSHER_APP_KEY' => 'required',
                'PUSHER_APP_SECRET' => 'required',
                'PUSHER_APP_CLUSTER' => 'required',
            ]);

            $autoloadPath = base_path('modules/interactive-whiteboard/vendor/autoload.php');
            if (file_exists($autoloadPath) && !class_exists('\Pusher\Pusher')) {
                require $autoloadPath;
            }

            // Set up Pusher instance directly
            $pusher = new \Pusher\Pusher(
                $request->PUSHER_APP_KEY,
                $request->PUSHER_APP_SECRET,
                $request->PUSHER_APP_ID,
                [
                    'cluster' => $request->PUSHER_APP_CLUSTER,
                    'useTLS' => true,
                ]
            );

            // Make a simple API call to test credentials
            $result = $pusher->getChannels();

            if ($result) {
                return response()->json([
                    'success' => true,
                    'message' => 'Successfully connected to Pusher API. Your credentials are valid.'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Connected to Pusher, but failed to retrieve channels.'
                ], 400);
            }
        } catch (\Pusher\ApiErrorException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Pusher API Error: ' . $e->getMessage()
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Connection Error: ' . $e->getMessage()
            ], 400);
        }
    }

    /**
     * Load the whiteboard session page for a course.
     */
    public function session($courseId)
    {
        $user = auth()->user();

        if (!$this->service->isModuleEnabled()) {
            abort(403, 'Whiteboard module is not enabled.');
        }

        if (!$this->service->canAccessWhiteboard($user, $courseId)) {
            abort(403, 'You are not enrolled in this course.');
        }

        $course = \App\Models\Course::findOrFail($courseId);
        $isInstructor = $this->service->isInstructor($user, $courseId);
        $canDraw = $this->service->canUserDraw($user, $courseId);
        $isCollabMode = $this->service->isCollabMode($courseId);

        return view('whiteboard::whiteboard', compact(
            'course',
            'user',
            'isInstructor',
            'canDraw',
            'isCollabMode',
            'courseId'
        ));
    }

    /**
     * Broadcast a whiteboard draw event.
     */
    public function broadcast(Request $request)
    {
        $user = auth()->user();
        $courseId = $request->input('course_id');

        $this->injectPusherConfig();

        if (!$this->service->isModuleEnabled()) {
            return response()->json(['error' => 'Module not available'], 403);
        }

        if (!$this->service->canAccessWhiteboard($user, $courseId)) {
            return response()->json(['error' => 'Access denied'], 403);
        }

        if (!$this->service->canUserDraw($user, $courseId)) {
            return response()->json(['error' => 'Drawing not permitted'], 403);
        }

        // Broadcast the draw event
        broadcast(new WhiteboardDrawEvent(
            $courseId,
            $request->input('type', 'draw'),
            $request->input('data', []),
            $user->id,
            $user->full_name
        ))->toOthers();

        return response()->json(['status' => 'ok']);
    }

    /**
     * Toggle collaborative mode for a whiteboard session.
     */
    public function toggleCollab(Request $request)
    {
        $user = auth()->user();
        $courseId = $request->input('course_id');

        $this->injectPusherConfig();

        if (!$this->service->isInstructor($user, $courseId)) {
            return response()->json(['error' => 'Only instructors can toggle collaborative mode'], 403);
        }

        $newState = $this->service->toggleCollabMode($courseId);

        // Broadcast the change to all participants
        broadcast(new WhiteboardCollabToggled($courseId, $newState))->toOthers();

        return response()->json([
            'status' => 'ok',
            'collab_mode' => $newState
        ]);
    }

    /**
     * Check if a user has whiteboard access and their permission level.
     */
    public function checkAccess($courseId)
    {
        $user = auth()->user();

        if (!$this->service->isModuleEnabled()) {
            return response()->json(['error' => 'Module not available'], 403);
        }

        $canAccess = $this->service->canAccessWhiteboard($user, $courseId);

        if (!$canAccess) {
            return response()->json(['error' => 'Access denied'], 403);
        }

        return response()->json([
            'can_access' => true,
            'can_draw' => $this->service->canUserDraw($user, $courseId),
            'is_instructor' => $this->service->isInstructor($user, $courseId),
            'is_collab_mode' => $this->service->isCollabMode($courseId),
        ]);
    }

    /**
     * Test whiteboard page — accessible from the admin sidebar.
     * Opens a standalone whiteboard for testing (no course required).
     */
    public function test()
    {
        $user = auth()->user();

        if (!$this->service->isModuleEnabled()) {
            abort(403, 'Whiteboard module is not enabled.');
        }

        // Use a test courseId of 0 for the sandbox session
        $courseId = 0;
        $course = (object) ['id' => 0, 'title' => 'Whiteboard Test Session'];
        $isInstructor = true;
        $canDraw = true;
        $isCollabMode = false;

        return view('whiteboard::whiteboard', compact(
            'course',
            'user',
            'isInstructor',
            'canDraw',
            'isCollabMode',
            'courseId'
        ));
    }

    /**
     * Save a whiteboard snapshot as an image.
     * Called when the user saves or closes the whiteboard.
     */
    public function saveSnapshot(Request $request)
    {
        $user = auth()->user();
        $courseId = $request->input('course_id');
        $imageData = $request->input('image');

        if (!$imageData) {
            return response()->json(['error' => 'No image data provided'], 400);
        }

        // Decode base64 image (strip data:image/png;base64, prefix)
        $imageData = preg_replace('/^data:image\/\w+;base64,/', '', $imageData);
        $imageData = base64_decode($imageData);

        if (!$imageData) {
            return response()->json(['error' => 'Invalid image data'], 400);
        }

        // Build file name: user_<id>_course_<id>_<timestamp>.png
        $courseLabel = ($courseId && $courseId != 0) ? $courseId : 'test';
        $fileName = "wb_user_{$user->id}_course_{$courseLabel}_" . date('Ymd_His') . '.png';
        $relativePath = 'whiteboard_snapshots/' . $fileName;

        // Ensure directory exists
        $storagePath = storage_path('app/public/whiteboard_snapshots');
        if (!file_exists($storagePath)) {
            mkdir($storagePath, 0755, true);
        }

        // Save the image file
        $fullPath = $storagePath . '/' . $fileName;
        file_put_contents($fullPath, $imageData);

        $fileSize = filesize($fullPath);

        // Store record in database
        $snapshot = \App\Models\WhiteboardSnapshot::create([
            'user_id'    => $user->id,
            'course_id'  => ($courseId && $courseId != 0) ? $courseId : null,
            'image_path' => $relativePath,
            'file_name'  => $fileName,
            'file_size'  => $fileSize,
        ]);

        return response()->json([
            'status'  => 'saved',
            'id'      => $snapshot->id,
            'file'    => $fileName,
            'url'     => asset('storage/' . $relativePath),
        ]);
    }

    /**
     * Whiteboard Module dashboard — shows test button + snapshots table.
     */
    public function dashboard()
    {
        if (!$this->service->isModuleEnabled()) {
            abort(403, 'Whiteboard module is not enabled.');
        }

        $snapshots = \App\Models\WhiteboardSnapshot::with('user')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        View::addNamespace('whiteboard', base_path('modules/interactive-whiteboard/views'));

        return view('whiteboard::dashboard', compact('snapshots'));
    }

    /**
     * Delete a whiteboard snapshot.
     */
    public function deleteSnapshot($id)
    {
        $snapshot = \App\Models\WhiteboardSnapshot::findOrFail($id);

        // Delete file from disk
        $fullPath = storage_path('app/public/' . $snapshot->image_path);
        if (file_exists($fullPath)) {
            unlink($fullPath);
        }

        $snapshot->delete();

        return redirect()->back()->with('success', 'Snapshot deleted successfully.');
    }

    /**
     * Download a whiteboard snapshot file.
     */
    public function downloadSnapshot($id)
    {
        $snapshot = \App\Models\WhiteboardSnapshot::findOrFail($id);
        $fullPath = storage_path('app/public/' . $snapshot->image_path);

        if (!file_exists($fullPath)) {
            abort(404, 'Snapshot file not found.');
        }

        $fileContents = file_get_contents($fullPath);

        return response($fileContents, 200)
            ->header('Content-Type', 'image/png')
            ->header('Content-Disposition', 'attachment; filename="' . $snapshot->file_name . '"')
            ->header('Content-Length', strlen($fileContents));
    }
}
