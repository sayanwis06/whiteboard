<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Whiteboard — {{ $course->title }}</title>

    <!-- Bootstrap 4 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <!-- Google Font -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        /* ===== Reset & Base ===== */
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        html, body {
            height: 100%; overflow: hidden;
            font-family: 'Inter', sans-serif;
            background: #1a1a2e;
            color: #e0e0e0;
        }

        /* ===== Layout ===== */
        .wb-app { display: flex; flex-direction: column; height: 100vh; }

        /* ===== Top Bar ===== */
        .wb-topbar {
            display: flex; align-items: center; justify-content: space-between;
            padding: 6px 16px;
            background: linear-gradient(135deg, #16213e 0%, #0f3460 100%);
            border-bottom: 1px solid rgba(255,255,255,0.08);
            min-height: 48px; z-index: 100;
        }
        .wb-topbar .wb-title {
            font-weight: 600; font-size: 14px; color: #e0e0e0;
            white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
            max-width: 300px;
        }
        .wb-topbar .wb-title i { color: #e94560; margin-right: 8px; }
        .wb-topbar-right { display: flex; align-items: center; gap: 12px; }

        .wb-status {
            display: flex; align-items: center; gap: 6px;
            font-size: 12px; color: #a0a0a0;
        }
        .wb-status-dot {
            width: 8px; height: 8px; border-radius: 50%;
            background: #4caf50; animation: pulse 2s infinite;
        }
        .wb-status-dot.offline { background: #f44336; animation: none; }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.4; }
        }

        .wb-users-badge {
            display: flex; align-items: center; gap: 4px;
            background: rgba(255,255,255,0.1); border-radius: 12px;
            padding: 4px 10px; font-size: 12px; cursor: pointer;
            transition: background 0.2s;
        }
        .wb-users-badge:hover { background: rgba(255,255,255,0.2); }
        .wb-users-badge i { color: #53c9f0; }

        .wb-collab-toggle {
            display: flex; align-items: center; gap: 6px;
            font-size: 12px; cursor: pointer;
            background: rgba(255,255,255,0.05); border-radius: 6px;
            padding: 4px 10px; border: 1px solid rgba(255,255,255,0.1);
            transition: all 0.2s;
        }
        .wb-collab-toggle:hover { background: rgba(255,255,255,0.1); }
        .wb-collab-toggle.active { background: rgba(76,175,80,0.2); border-color: #4caf50; }
        .wb-collab-toggle .custom-switch { margin-bottom: 0; }

        /* ===== Toolbar ===== */
        .wb-toolbar {
            display: flex; align-items: center; gap: 4px;
            padding: 6px 12px;
            background: #16213e;
            border-bottom: 1px solid rgba(255,255,255,0.06);
            flex-wrap: wrap;
        }
        .wb-toolbar .wb-divider {
            width: 1px; height: 24px; background: rgba(255,255,255,0.12);
            margin: 0 6px;
        }

        .wb-tool-btn {
            display: flex; align-items: center; justify-content: center;
            width: 36px; height: 36px; border-radius: 8px;
            background: transparent; border: 1px solid transparent;
            color: #b0b0b0; cursor: pointer; font-size: 14px;
            transition: all 0.15s;
            position: relative;
        }
        .wb-tool-btn:hover { background: rgba(255,255,255,0.08); color: #fff; }
        .wb-tool-btn.active {
            background: rgba(233,69,96,0.15); color: #e94560;
            border-color: rgba(233,69,96,0.3);
        }
        .wb-tool-btn[disabled] { opacity: 0.3; pointer-events: none; }
        .wb-tool-btn .wb-tool-label {
            display: none; position: absolute; bottom: -22px; left: 50%;
            transform: translateX(-50%); font-size: 10px; white-space: nowrap;
            background: #333; color: #fff; padding: 2px 6px; border-radius: 3px;
        }
        .wb-tool-btn:hover .wb-tool-label { display: block; }

        /* Color & width inputs */
        .wb-color-input {
            width: 32px; height: 32px; border: 2px solid rgba(255,255,255,0.2);
            border-radius: 8px; cursor: pointer; background: none; padding: 0;
        }
        .wb-color-input::-webkit-color-swatch-wrapper { padding: 2px; }
        .wb-color-input::-webkit-color-swatch { border: none; border-radius: 4px; }

        .wb-width-select {
            background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.15);
            color: #e0e0e0; border-radius: 6px; padding: 4px 8px; font-size: 12px;
            cursor: pointer; outline: none;
        }
        .wb-width-select option { background: #16213e; }

        /* ===== Canvas Area ===== */
        .wb-canvas-container {
            flex: 1; position: relative; overflow: hidden;
            background: #f5f5f5;
        }
        .wb-canvas-container canvas { display: block; }

        /* View-only overlay */
        .wb-viewonly-banner {
            position: absolute; top: 10px; left: 50%;
            transform: translateX(-50%); z-index: 50;
            background: rgba(255,152,0,0.9); color: #fff;
            padding: 6px 20px; border-radius: 20px;
            font-size: 12px; font-weight: 600;
            box-shadow: 0 2px 12px rgba(0,0,0,0.2);
            display: none;
        }
        .wb-viewonly-banner.show { display: block; }

        /* Users panel popover */
        .wb-users-panel {
            display: none; position: absolute; top: 52px; right: 16px;
            background: #1a1a2e; border: 1px solid rgba(255,255,255,0.1);
            border-radius: 10px; min-width: 220px; z-index: 200;
            box-shadow: 0 8px 32px rgba(0,0,0,0.4);
            padding: 12px;
        }
        .wb-users-panel.show { display: block; }
        .wb-users-panel h6 { font-size: 12px; color: #888; text-transform: uppercase; margin-bottom: 8px; }
        .wb-user-item {
            display: flex; align-items: center; gap: 8px;
            padding: 4px 0; font-size: 13px;
        }
        .wb-user-avatar {
            width: 28px; height: 28px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 11px; font-weight: 700; color: #fff;
        }
        .wb-user-role {
            font-size: 10px; color: #888; background: rgba(255,255,255,0.06);
            padding: 1px 6px; border-radius: 8px;
        }

        /* ===== Responsive ===== */
        @media (max-width: 768px) {
            .wb-topbar .wb-title { max-width: 140px; font-size: 12px; }
            .wb-tool-btn { width: 32px; height: 32px; font-size: 12px; }
            .wb-toolbar { padding: 4px 8px; gap: 2px; }
        }

        /* ===== Toolbar Hidden for View-Only ===== */
        .wb-toolbar.readonly { pointer-events: none; opacity: 0.4; }
    </style>
</head>
<body>
    <div class="wb-app" id="whiteboardApp">
        <!-- ===== TOP BAR ===== -->
        <div class="wb-topbar">
            <div class="wb-title">
                <i class="fas fa-chalkboard-teacher"></i>
                <span>{{ $course->title }}</span>
            </div>
            <div class="wb-topbar-right">
                <div class="wb-status">
                    <span class="wb-status-dot" id="wbStatusDot"></span>
                    <span id="wbStatusText">Connecting…</span>
                </div>
                @if($isInstructor)
                <div class="wb-collab-toggle {{ $isCollabMode ? 'active' : '' }}" id="wbCollabToggle"
                     title="Allow students to draw">
                    <i class="fas fa-users"></i>
                    <span>Collab</span>
                    <div class="custom-control custom-switch" style="margin-left:4px">
                        <input type="checkbox" class="custom-control-input" id="collabSwitch"
                               {{ $isCollabMode ? 'checked' : '' }}>
                        <label class="custom-control-label" for="collabSwitch"></label>
                    </div>
                </div>
                @endif
                <div class="wb-users-badge" id="wbUsersBadge" title="Online users">
                    <i class="fas fa-users"></i>
                    <span id="wbUsersCount">1</span>
                </div>
            </div>
        </div>

        <!-- ===== TOOLBAR ===== -->
        <div class="wb-toolbar {{ !$canDraw ? 'readonly' : '' }}" id="wbToolbar">
            <!-- Selection -->
            <button class="wb-tool-btn" data-tool="select" title="Select (V)">
                <i class="fas fa-mouse-pointer"></i>
                <span class="wb-tool-label">Select</span>
            </button>
            <div class="wb-divider"></div>

            <!-- Drawing Tools -->
            <button class="wb-tool-btn active" data-tool="pencil" title="Pencil (P)">
                <i class="fas fa-pencil-alt"></i>
                <span class="wb-tool-label">Pencil</span>
            </button>
            <button class="wb-tool-btn" data-tool="line" title="Line (L)">
                <i class="fas fa-minus"></i>
                <span class="wb-tool-label">Line</span>
            </button>
            <button class="wb-tool-btn" data-tool="rect" title="Rectangle (R)">
                <i class="far fa-square"></i>
                <span class="wb-tool-label">Rectangle</span>
            </button>
            <button class="wb-tool-btn" data-tool="circle" title="Circle (C)">
                <i class="far fa-circle"></i>
                <span class="wb-tool-label">Circle</span>
            </button>
            <button class="wb-tool-btn" data-tool="text" title="Text (T)">
                <i class="fas fa-font"></i>
                <span class="wb-tool-label">Text</span>
            </button>
            <div class="wb-divider"></div>

            <!-- Eraser -->
            <button class="wb-tool-btn" data-tool="eraser" title="Eraser (E)">
                <i class="fas fa-eraser"></i>
                <span class="wb-tool-label">Eraser</span>
            </button>
            <div class="wb-divider"></div>

            <!-- Color & Stroke -->
            <input type="color" class="wb-color-input" id="wbStrokeColor" value="#e94560" title="Stroke Color">
            <input type="color" class="wb-color-input" id="wbFillColor" value="#ffffff" title="Fill Color" style="margin-left:4px">
            <select class="wb-width-select" id="wbStrokeWidth" title="Stroke Width">
                <option value="1">1px</option>
                <option value="2" selected>2px</option>
                <option value="4">4px</option>
                <option value="6">6px</option>
                <option value="10">10px</option>
                <option value="16">16px</option>
            </select>
            <div class="wb-divider"></div>

            <!-- Actions -->
            <button class="wb-tool-btn" id="wbUndo" title="Undo (Ctrl+Z)">
                <i class="fas fa-undo"></i>
                <span class="wb-tool-label">Undo</span>
            </button>
            <button class="wb-tool-btn" id="wbRedo" title="Redo (Ctrl+Y)">
                <i class="fas fa-redo"></i>
                <span class="wb-tool-label">Redo</span>
            </button>
            <button class="wb-tool-btn" id="wbClear" title="Clear All">
                <i class="fas fa-trash-alt"></i>
                <span class="wb-tool-label">Clear</span>
            </button>
            <div class="wb-divider"></div>

            <!-- Export -->
            <button class="wb-tool-btn" id="wbSave" title="Save as PNG">
                <i class="fas fa-download"></i>
                <span class="wb-tool-label">Save PNG</span>
            </button>
        </div>

        <!-- ===== CANVAS ===== -->
        <div class="wb-canvas-container" id="wbCanvasContainer">
            <div class="wb-viewonly-banner {{ !$canDraw ? 'show' : '' }}" id="wbViewOnlyBanner">
                <i class="fas fa-eye mr-1"></i> View Only — Instructor is drawing
            </div>
            <canvas id="wbCanvas"></canvas>
        </div>

        <!-- ===== USERS PANEL ===== -->
        <div class="wb-users-panel" id="wbUsersPanel">
            <h6>Online Users</h6>
            <div id="wbUsersList"></div>
        </div>
    </div>

    <!-- ===== Scripts ===== -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/fabric.js/5.3.1/fabric.min.js"></script>
    <script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/laravel-echo/1.15.3/echo.iife.js"></script>

    <script>
    (function() {
        'use strict';

        // ===== Configuration =====
        const CONFIG = {
            userId: {{ $user->id ?? 0 }},
            userName: '{{ addslashes($user->full_name ?? "Guest") }}',
            courseId: '{{ $courseId }}',
            isInstructor: {{ $isInstructor ? 'true' : 'false' }},
            canDraw: {{ $canDraw ? 'true' : 'false' }},
            isCollabMode: {{ $isCollabMode ? 'true' : 'false' }},
            toggleCollabUrl: '{{ url("external-apps/whiteboard/toggle-collab") }}',
            broadcastUrl: '{{ url("external-apps/whiteboard/broadcast") }}',
            saveSnapshotUrl: '{{ url("external-apps/whiteboard/save-snapshot") }}',
            pusherKey: '{{ \App\Services\ExternalApps\ExternalAppService::staticGetModuleEnv("interactive-whiteboard", "PUSHER_APP_KEY", "") }}',
            pusherCluster: '{{ \App\Services\ExternalApps\ExternalAppService::staticGetModuleEnv("interactive-whiteboard", "PUSHER_APP_CLUSTER", "mt1") }}',
            authEndpoint: '{{ url("external-apps/whiteboard/broadcasting/auth") }}',
            csrfToken: '{{ csrf_token() }}'
        };

        // ===== State =====
        let canvas, currentTool = 'pencil', isDrawingShape = false;
        let shapeStart = null, activeShape = null;
        let undoStack = [], redoStack = [];
        let onlineUsers = {};
        let broadcastThrottle = null;
        const THROTTLE_MS = 33; // ~30fps max broadcast rate
        let snapshotSaved = false; // prevent duplicate saves on close

        // ===== Canvas Init =====
        function initCanvas() {
            const container = document.getElementById('wbCanvasContainer');
            canvas = new fabric.Canvas('wbCanvas', {
                width: container.offsetWidth,
                height: container.offsetHeight,
                backgroundColor: '#ffffff',
                isDrawingMode: true,
                selection: true
            });

            canvas.freeDrawingBrush.color = document.getElementById('wbStrokeColor').value;
            canvas.freeDrawingBrush.width = parseInt(document.getElementById('wbStrokeWidth').value);

            if (!CONFIG.canDraw) {
                canvas.isDrawingMode = false;
                canvas.selection = false;
                canvas.forEachObject(o => o.selectable = false);
            }

            // Resize handler
            window.addEventListener('resize', () => {
                canvas.setWidth(container.offsetWidth);
                canvas.setHeight(container.offsetHeight);
                canvas.renderAll();
            });

            // Canvas events for broadcasting
            canvas.on('path:created', (e) => {
                if (!CONFIG.canDraw) return;
                const obj = e.path;
                obj.set('wbId', generateId());
                saveState();
                broadcastObject('object-added', obj);
            });

            canvas.on('object:modified', (e) => {
                if (!CONFIG.canDraw) return;
                broadcastObject('object-modified', e.target);
            });

            canvas.on('object:removed', (e) => {
                if (!CONFIG.canDraw) return;
                if (e.target.wbId) {
                    broadcastEvent('object-removed', { wbId: e.target.wbId });
                }
            });

            setTool('pencil');
        }

        // ===== Tool Management =====
        function setTool(tool) {
            currentTool = tool;
            document.querySelectorAll('.wb-tool-btn[data-tool]').forEach(b => {
                b.classList.toggle('active', b.dataset.tool === tool);
            });

            // Reset canvas modes
            canvas.isDrawingMode = false;
            canvas.selection = false;
            canvas.defaultCursor = 'default';
            canvas.hoverCursor = 'default';

            // Remove shape drawing listeners
            canvas.off('mouse:down', onShapeMouseDown);
            canvas.off('mouse:move', onShapeMouseMove);
            canvas.off('mouse:up', onShapeMouseUp);

            switch(tool) {
                case 'select':
                    canvas.selection = true;
                    canvas.forEachObject(o => o.selectable = true);
                    canvas.defaultCursor = 'default';
                    break;
                case 'pencil':
                    canvas.isDrawingMode = true;
                    canvas.freeDrawingBrush.color = document.getElementById('wbStrokeColor').value;
                    canvas.freeDrawingBrush.width = parseInt(document.getElementById('wbStrokeWidth').value);
                    break;
                case 'eraser':
                    canvas.isDrawingMode = true;
                    canvas.freeDrawingBrush.color = '#ffffff';
                    canvas.freeDrawingBrush.width = 20;
                    break;
                case 'line':
                case 'rect':
                case 'circle':
                    canvas.defaultCursor = 'crosshair';
                    canvas.forEachObject(o => o.selectable = false);
                    canvas.on('mouse:down', onShapeMouseDown);
                    canvas.on('mouse:move', onShapeMouseMove);
                    canvas.on('mouse:up', onShapeMouseUp);
                    break;
                case 'text':
                    canvas.defaultCursor = 'text';
                    canvas.on('mouse:down', onTextClick);
                    break;
            }
        }

        // ===== Shape Drawing =====
        function onShapeMouseDown(o) {
            if (!CONFIG.canDraw) return;
            isDrawingShape = true;
            const pointer = canvas.getPointer(o.e);
            shapeStart = { x: pointer.x, y: pointer.y };
            const stroke = document.getElementById('wbStrokeColor').value;
            const fill = 'transparent';
            const strokeWidth = parseInt(document.getElementById('wbStrokeWidth').value);

            switch(currentTool) {
                case 'line':
                    activeShape = new fabric.Line([shapeStart.x, shapeStart.y, shapeStart.x, shapeStart.y], {
                        stroke, strokeWidth, selectable: false, wbId: generateId()
                    });
                    break;
                case 'rect':
                    activeShape = new fabric.Rect({
                        left: shapeStart.x, top: shapeStart.y, width: 0, height: 0,
                        stroke, fill, strokeWidth, selectable: false, wbId: generateId()
                    });
                    break;
                case 'circle':
                    activeShape = new fabric.Ellipse({
                        left: shapeStart.x, top: shapeStart.y, rx: 0, ry: 0,
                        stroke, fill, strokeWidth, selectable: false, wbId: generateId()
                    });
                    break;
            }
            if (activeShape) canvas.add(activeShape);
        }

        function onShapeMouseMove(o) {
            if (!isDrawingShape || !activeShape) return;
            const pointer = canvas.getPointer(o.e);

            switch(currentTool) {
                case 'line':
                    activeShape.set({ x2: pointer.x, y2: pointer.y });
                    break;
                case 'rect':
                    const rw = Math.abs(pointer.x - shapeStart.x);
                    const rh = Math.abs(pointer.y - shapeStart.y);
                    activeShape.set({
                        left: Math.min(pointer.x, shapeStart.x),
                        top: Math.min(pointer.y, shapeStart.y),
                        width: rw, height: rh
                    });
                    break;
                case 'circle':
                    const rx = Math.abs(pointer.x - shapeStart.x) / 2;
                    const ry = Math.abs(pointer.y - shapeStart.y) / 2;
                    activeShape.set({
                        left: Math.min(pointer.x, shapeStart.x),
                        top: Math.min(pointer.y, shapeStart.y),
                        rx, ry
                    });
                    break;
            }
            canvas.renderAll();
        }

        function onShapeMouseUp(o) {
            if (!isDrawingShape || !activeShape) return;
            isDrawingShape = false;
            activeShape.setCoords();
            saveState();
            broadcastObject('object-added', activeShape);
            activeShape = null;
        }

        // ===== Text Tool =====
        function onTextClick(o) {
            if (!CONFIG.canDraw) return;
            canvas.off('mouse:down', onTextClick);
            const pointer = canvas.getPointer(o.e);
            const text = new fabric.IText('Type here', {
                left: pointer.x, top: pointer.y,
                fontSize: 18, fill: document.getElementById('wbStrokeColor').value,
                fontFamily: 'Inter, sans-serif',
                wbId: generateId()
            });
            canvas.add(text);
            canvas.setActiveObject(text);
            text.enterEditing();
            saveState();

            text.on('editing:exited', () => {
                broadcastObject('object-added', text);
                setTool('text');
            });
        }

        // ===== Undo / Redo =====
        function saveState() {
            undoStack.push(JSON.stringify(canvas.toJSON(['wbId'])));
            if (undoStack.length > 50) undoStack.shift();
            redoStack = [];
        }

        function undo() {
            if (undoStack.length === 0) return;
            redoStack.push(JSON.stringify(canvas.toJSON(['wbId'])));
            const state = undoStack.pop();
            canvas.loadFromJSON(state, () => {
                canvas.renderAll();
                if (!CONFIG.canDraw) {
                    canvas.forEachObject(o => o.selectable = false);
                }
            });
        }

        function redo() {
            if (redoStack.length === 0) return;
            undoStack.push(JSON.stringify(canvas.toJSON(['wbId'])));
            const state = redoStack.pop();
            canvas.loadFromJSON(state, () => {
                canvas.renderAll();
                if (!CONFIG.canDraw) {
                    canvas.forEachObject(o => o.selectable = false);
                }
            });
        }

        // ===== Broadcasting =====
        function broadcastObject(type, obj) {
            if (!CONFIG.canDraw) return;
            const data = obj.toJSON(['wbId']);
            broadcastEvent(type, data);
        }

        function broadcastEvent(type, data) {
            // Throttle broadcasts
            if (broadcastThrottle) return;
            broadcastThrottle = setTimeout(() => { broadcastThrottle = null; }, THROTTLE_MS);

            $.ajax({
                url: CONFIG.broadcastUrl,
                method: 'POST',
                data: {
                    _token: CONFIG.csrfToken,
                    course_id: CONFIG.courseId,
                    type: type,
                    data: data
                },
                error: function(xhr) {
                    console.warn('Broadcast error:', xhr.status);
                }
            });
        }

        // ===== Receive Remote Events =====
        function handleRemoteEvent(eventData) {
            switch(eventData.type) {
                case 'object-added':
                    addRemoteObject(eventData.data);
                    break;
                case 'object-modified':
                    modifyRemoteObject(eventData.data);
                    break;
                case 'object-removed':
                    removeRemoteObject(eventData.data.wbId);
                    break;
                case 'clear':
                    canvas.clear();
                    canvas.backgroundColor = '#ffffff';
                    canvas.renderAll();
                    break;
            }
        }

        function addRemoteObject(objData) {
            // Remove existing object with same wbId if re-sent
            if (objData.wbId) {
                const existing = canvas.getObjects().find(o => o.wbId === objData.wbId);
                if (existing) canvas.remove(existing);
            }

            fabric.util.enlivenObjects([objData], function(objects) {
                objects.forEach(obj => {
                    if (!CONFIG.canDraw) obj.selectable = false;
                    canvas.add(obj);
                });
                canvas.renderAll();
            });
        }

        function modifyRemoteObject(objData) {
            if (!objData.wbId) return;
            const target = canvas.getObjects().find(o => o.wbId === objData.wbId);
            if (target) {
                target.set(objData);
                target.setCoords();
                canvas.renderAll();
            }
        }

        function removeRemoteObject(wbId) {
            if (!wbId) return;
            const target = canvas.getObjects().find(o => o.wbId === wbId);
            if (target) {
                canvas.remove(target);
                canvas.renderAll();
            }
        }

        // ===== Laravel Echo / Pusher =====
        function initEcho() {
            if (!CONFIG.pusherKey) {
                updateStatus('offline', 'No Pusher key');
                return;
            }

            try {
                window.Echo = new Echo({
                    broadcaster: 'pusher',
                    key: CONFIG.pusherKey,
                    cluster: CONFIG.pusherCluster,
                    forceTLS: true,
                    authEndpoint: CONFIG.authEndpoint,
                    auth: {
                        headers: {
                            'X-CSRF-TOKEN': CONFIG.csrfToken
                        }
                    }
                });

                const channel = window.Echo.join('whiteboard.' + CONFIG.courseId)
                    .here((users) => {
                        onlineUsers = {};
                        users.forEach(u => onlineUsers[u.id] = u);
                        updateUsersUI();
                        updateStatus('online', 'Connected');
                    })
                    .joining((user) => {
                        onlineUsers[user.id] = user;
                        updateUsersUI();
                    })
                    .leaving((user) => {
                        delete onlineUsers[user.id];
                        updateUsersUI();
                    })
                    .listen('.whiteboard.draw', (e) => {
                        handleRemoteEvent(e);
                    })
                    .listen('.whiteboard.collab-toggled', (e) => {
                        CONFIG.isCollabMode = e.collab_mode;
                        CONFIG.canDraw = CONFIG.isInstructor || e.collab_mode;
                        updateDrawPermissions();
                        updateCollabUI();
                    })
                    .error((error) => {
                        console.error('Echo error:', error);
                        updateStatus('offline', 'Connection error');
                    });
            } catch (e) {
                console.error('Echo init failed:', e);
                updateStatus('offline', 'Setup error');
            }
        }

        // ===== UI Updates =====
        function updateStatus(state, text) {
            const dot = document.getElementById('wbStatusDot');
            const txt = document.getElementById('wbStatusText');
            dot.className = 'wb-status-dot' + (state === 'offline' ? ' offline' : '');
            txt.textContent = text;
        }

        function updateUsersUI() {
            const count = Object.keys(onlineUsers).length;
            document.getElementById('wbUsersCount').textContent = count;

            const list = document.getElementById('wbUsersList');
            const colors = ['#e94560','#53c9f0','#4caf50','#ff9800','#9c27b0','#00bcd4'];
            list.innerHTML = Object.values(onlineUsers).map((u, i) =>
                `<div class="wb-user-item">
                    <div class="wb-user-avatar" style="background:${colors[i % colors.length]}">${u.name.charAt(0).toUpperCase()}</div>
                    <span>${u.name}</span>
                    ${u.is_instructor ? '<span class="wb-user-role">Instructor</span>' : '<span class="wb-user-role">Student</span>'}
                </div>`
            ).join('');
        }

        function updateDrawPermissions() {
            const toolbar = document.getElementById('wbToolbar');
            const banner = document.getElementById('wbViewOnlyBanner');

            if (CONFIG.canDraw) {
                toolbar.classList.remove('readonly');
                banner.classList.remove('show');
                canvas.isDrawingMode = (currentTool === 'pencil' || currentTool === 'eraser');
                if (currentTool === 'select') canvas.selection = true;
            } else {
                toolbar.classList.add('readonly');
                banner.classList.add('show');
                canvas.isDrawingMode = false;
                canvas.selection = false;
                canvas.forEachObject(o => o.selectable = false);
            }
        }

        function updateCollabUI() {
            const toggle = document.getElementById('wbCollabToggle');
            const switchInput = document.getElementById('collabSwitch');
            if (toggle) {
                toggle.classList.toggle('active', CONFIG.isCollabMode);
            }
            if (switchInput) {
                switchInput.checked = CONFIG.isCollabMode;
            }
        }

        // ===== Utility =====
        function generateId() {
            return CONFIG.userId + '_' + Date.now() + '_' + Math.random().toString(36).substr(2, 5);
        }

        // ===== Snapshot Save =====
        function saveSnapshotToServer(useBeacon) {
            if (!canvas || canvas.getObjects().length === 0) return; // nothing drawn
            const dataURL = canvas.toDataURL({ format: 'png', quality: 1 });

            if (useBeacon && navigator.sendBeacon) {
                // Use sendBeacon for reliable save on tab close
                const formData = new FormData();
                formData.append('_token', CONFIG.csrfToken);
                formData.append('course_id', CONFIG.courseId);
                formData.append('image', dataURL);
                navigator.sendBeacon(CONFIG.saveSnapshotUrl, formData);
                snapshotSaved = true;
                return;
            }

            // Normal AJAX save
            $.ajax({
                url: CONFIG.saveSnapshotUrl,
                method: 'POST',
                data: {
                    _token: CONFIG.csrfToken,
                    course_id: CONFIG.courseId,
                    image: dataURL
                },
                success: function(res) {
                    snapshotSaved = true;
                    showSaveToast('Whiteboard saved successfully!');
                },
                error: function(xhr) {
                    console.warn('Snapshot save error:', xhr.status);
                    showSaveToast('Failed to save whiteboard', true);
                }
            });
        }

        function showSaveToast(message, isError) {
            // Create a temporary toast notification
            const toast = document.createElement('div');
            toast.style.cssText = 'position:fixed;bottom:20px;right:20px;padding:12px 24px;border-radius:8px;color:#fff;font-size:13px;font-weight:600;z-index:9999;transition:opacity 0.3s;box-shadow:0 4px 16px rgba(0,0,0,0.3);'
                + (isError ? 'background:#f44336;' : 'background:#4caf50;');
            toast.innerHTML = '<i class="fas ' + (isError ? 'fa-exclamation-circle' : 'fa-check-circle') + ' mr-2"></i>' + message;
            document.body.appendChild(toast);
            setTimeout(() => { toast.style.opacity = '0'; setTimeout(() => toast.remove(), 300); }, 3000);
        }

        // ===== Event Bindings =====
        function bindEvents() {
            // Tool buttons
            document.querySelectorAll('.wb-tool-btn[data-tool]').forEach(btn => {
                btn.addEventListener('click', () => setTool(btn.dataset.tool));
            });

            // Color & Width
            document.getElementById('wbStrokeColor').addEventListener('input', (e) => {
                if (canvas.isDrawingMode && currentTool === 'pencil') {
                    canvas.freeDrawingBrush.color = e.target.value;
                }
            });
            document.getElementById('wbStrokeWidth').addEventListener('change', (e) => {
                if (canvas.isDrawingMode) {
                    canvas.freeDrawingBrush.width = parseInt(e.target.value);
                }
            });

            // Undo / Redo / Clear
            document.getElementById('wbUndo').addEventListener('click', undo);
            document.getElementById('wbRedo').addEventListener('click', redo);
            document.getElementById('wbClear').addEventListener('click', () => {
                if (!CONFIG.canDraw) return;
                if (confirm('Clear the entire whiteboard?')) {
                    canvas.clear();
                    canvas.backgroundColor = '#ffffff';
                    canvas.renderAll();
                    undoStack = [];
                    redoStack = [];
                    broadcastEvent('clear', {});
                }
            });

            // Save as PNG (download locally + save to server)
            document.getElementById('wbSave').addEventListener('click', () => {
                const dataURL = canvas.toDataURL({ format: 'png', quality: 1 });
                // Download locally
                const link = document.createElement('a');
                link.download = 'whiteboard_{{ $course->id }}_' + Date.now() + '.png';
                link.href = dataURL;
                link.click();
                // Also save snapshot to server
                snapshotSaved = false;
                saveSnapshotToServer(false);
            });

            // Users panel toggle
            document.getElementById('wbUsersBadge').addEventListener('click', () => {
                document.getElementById('wbUsersPanel').classList.toggle('show');
            });
            document.addEventListener('click', (e) => {
                if (!e.target.closest('#wbUsersPanel') && !e.target.closest('#wbUsersBadge')) {
                    document.getElementById('wbUsersPanel').classList.remove('show');
                }
            });

            // Collab toggle (instructor only)
            const collabSwitch = document.getElementById('collabSwitch');
            if (collabSwitch) {
                collabSwitch.addEventListener('change', () => {
                    $.ajax({
                        url: CONFIG.toggleCollabUrl,
                        method: 'POST',
                        data: {
                            _token: CONFIG.csrfToken,
                            course_id: CONFIG.courseId
                        },
                        success: function(res) {
                            CONFIG.isCollabMode = res.collab_mode;
                            CONFIG.canDraw = true; // Instructor can always draw
                            updateCollabUI();
                        },
                        error: function() {
                            collabSwitch.checked = !collabSwitch.checked;
                        }
                    });
                });
            }

            // Keyboard shortcuts
            document.addEventListener('keydown', (e) => {
                if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') return;
                if (e.ctrlKey && e.key === 'z') { e.preventDefault(); undo(); }
                if (e.ctrlKey && e.key === 'y') { e.preventDefault(); redo(); }
                if (!e.ctrlKey && !e.altKey) {
                    switch(e.key.toLowerCase()) {
                        case 'v': setTool('select'); break;
                        case 'p': setTool('pencil'); break;
                        case 'l': setTool('line'); break;
                        case 'r': setTool('rect'); break;
                        case 'c': setTool('circle'); break;
                        case 't': setTool('text'); break;
                        case 'e': setTool('eraser'); break;
                    }
                }
            });
        }

        // ===== Init =====
        $(document).ready(function() {
            initCanvas();
            bindEvents();
            saveState(); // Save initial blank state
            initEcho();

            // Auto-save snapshot when closing/navigating away
            window.addEventListener('beforeunload', function(e) {
                if (!snapshotSaved && canvas && canvas.getObjects().length > 0) {
                    saveSnapshotToServer(true); // use sendBeacon
                }
            });

            // Backup: save when tab becomes hidden (mobile/tablet)
            document.addEventListener('visibilitychange', function() {
                if (document.visibilityState === 'hidden' && !snapshotSaved && canvas && canvas.getObjects().length > 0) {
                    saveSnapshotToServer(true);
                }
            });
        });

    })();
    </script>
</body>
</html>
