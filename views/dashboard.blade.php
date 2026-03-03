@extends('backend.layouts.app')

@section('title', 'Whiteboard Module')

@push('after-styles')
<style>
    .wb-dashboard-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 12px;
        margin-bottom: 20px;
    }
    .table td { vertical-align: middle; }
    .badge-course { background: #e3f2fd; color: #1565c0; }
    .badge-test { background: #fff3e0; color: #e65100; }
</style>
@endpush

@section('content')
<div class="container-fluid">
    {{-- Header --}}
    <div class="wb-dashboard-header">
        <div>
            <h4 class="mb-1"><i class="fas fa-chalkboard-teacher mr-2"></i>Whiteboard Module</h4>
            <p class="text-muted mb-0" style="font-size:13px">Manage whiteboard sessions and saved snapshots</p>
        </div>
        <a href="{{ url('external-apps/whiteboard/test') }}" target="_blank"
           class="btn btn-primary btn-sm">
            <i class="fas fa-play-circle mr-1"></i>Test Whiteboard
        </a>
    </div>

    {{-- Success Message --}}
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
            <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
        </div>
    @endif

    {{-- Snapshots Table --}}
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fas fa-images mr-2"></i>Saved Snapshots</h5>
            <span class="badge badge-secondary">{{ $snapshots->total() }} records</span>
        </div>
        <div class="card-body p-0">
            @if($snapshots->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th style="width:40px">#</th>
                            <th>User</th>
                            <th>Course</th>
                            <th>File Name</th>
                            <th>Size</th>
                            <th>Saved At</th>
                            <th style="width:120px" class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($snapshots as $snapshot)
                        <tr>
                            <td class="text-muted">{{ $snapshot->id }}</td>
                            <td>
                                <strong>{{ $snapshot->user->full_name ?? 'Unknown' }}</strong>
                            </td>
                            <td>
                                @if($snapshot->course_id)
                                    <span class="badge badge-course px-2 py-1">
                                        Course #{{ $snapshot->course_id }}
                                    </span>
                                @else
                                    <span class="badge badge-test px-2 py-1">
                                        <i class="fas fa-flask mr-1"></i>Test Session
                                    </span>
                                @endif
                            </td>
                            <td><small class="text-monospace">{{ $snapshot->file_name }}</small></td>
                            <td>{{ number_format($snapshot->file_size / 1024, 1) }} KB</td>
                            <td>
                                {{ $snapshot->created_at->format('d M Y') }}
                                <br><small class="text-muted">{{ $snapshot->created_at->format('h:i A') }}</small>
                            </td>
                            <td class="text-center">
                                {{-- Download --}}
                                <a href="{{ url('external-apps/whiteboard/snapshot/' . $snapshot->id . '/download') }}"
                                   class="btn btn-sm btn-outline-primary mr-1"
                                   title="Download">
                                    <i class="fas fa-download"></i>
                                </a>
                                {{-- Delete --}}
                                <form action="{{ url('external-apps/whiteboard/snapshot/' . $snapshot->id) }}"
                                      method="POST"
                                      style="display:inline"
                                      onsubmit="return confirm('Are you sure you want to delete this snapshot?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            <div class="card-footer d-flex justify-content-center">
                {{ $snapshots->links() }}
            </div>
            @else
            <div class="text-center py-5">
                <i class="fas fa-images fa-3x text-muted mb-3" style="opacity:0.3"></i>
                <p class="text-muted mb-0">No snapshots saved yet.</p>
                <p class="text-muted"><small>Snapshots are automatically saved when users close or save the whiteboard.</small></p>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
