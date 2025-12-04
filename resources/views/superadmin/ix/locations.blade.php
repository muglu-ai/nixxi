@extends('superadmin.layout')

@section('title', 'IX Locations')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1" style="color:#2c3e50;font-weight:600;">IX Location Directory</h2>
            <p class="text-muted mb-0">Manage available NIXI IX nodes and visibility.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('superadmin.dashboard') }}" class="btn btn-outline-secondary">
                Back to Dashboard
            </a>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createLocationModal">
                + Add Location
            </button>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Name</th>
                            <th>State</th>
                            <th>Node Type</th>
                            <th>Switch Details</th>
                            <th>Ports</th>
                            <th>Nodal Officer</th>
                            <th>Zone</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($locations as $location)
                        <tr>
                            <td>{{ $location->name }}</td>
                            <td>{{ $location->state }}</td>
                            <td>
                                <span class="badge {{ $location->node_type === 'metro' ? 'bg-primary' : 'bg-warning text-dark' }}">
                                    {{ strtoupper($location->node_type) }}
                                </span>
                            </td>
                            <td>{{ $location->switch_details ?? '—' }}</td>
                            <td>{{ $location->ports ?? '—' }}</td>
                            <td>{{ $location->nodal_officer ?? '—' }}</td>
                            <td>{{ $location->zone ?? '—' }}</td>
                            <td>
                                <span class="badge {{ $location->is_active ? 'bg-success' : 'bg-secondary' }}">
                                    {{ $location->is_active ? 'Active' : 'Hidden' }}
                                </span>
                            </td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editLocationModal{{ $location->id }}">
                                        Edit
                                    </button>
                                    <form method="POST" action="{{ route('superadmin.ix-locations.toggle', $location) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="btn {{ $location->is_active ? 'btn-outline-warning' : 'btn-outline-success' }}">
                                            {{ $location->is_active ? 'Deactivate' : 'Activate' }}
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('superadmin.ix-locations.destroy', $location) }}" onsubmit="return confirm('Delete this location?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-outline-danger">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>

                        <div class="modal fade" id="editLocationModal{{ $location->id }}" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-lg">
                                <div class="modal-content">
                                    <div class="modal-header bg-light">
                                        <h5 class="modal-title">Update {{ $location->name }}</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <form method="POST" action="{{ route('superadmin.ix-locations.update', $location) }}">
                                        @csrf
                                        @method('PUT')
                                        <div class="modal-body">
                                            <div class="row g-3">
                                                <div class="col-md-6">
                                                    <label class="form-label">Name</label>
                                                    <input type="text" class="form-control" name="name" value="{{ old('name', $location->name) }}" required>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label">State</label>
                                                    <input type="text" class="form-control" name="state" value="{{ old('state', $location->state) }}" required>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label">City</label>
                                                    <input type="text" class="form-control" name="city" value="{{ old('city', $location->city) }}">
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label">Node Type</label>
                                                    <select name="node_type" class="form-select" required>
                                                        <option value="metro" @selected($location->node_type === 'metro')>Metro</option>
                                                        <option value="edge" @selected($location->node_type === 'edge')>Edge</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label">Switch Details</label>
                                                    <input type="text" class="form-control" name="switch_details" value="{{ old('switch_details', $location->switch_details) }}">
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label">Ports</label>
                                                    <input type="number" class="form-control" name="ports" min="1" value="{{ old('ports', $location->ports) }}">
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label">Nodal Officer</label>
                                                    <input type="text" class="form-control" name="nodal_officer" value="{{ old('nodal_officer', $location->nodal_officer) }}">
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label">Zone</label>
                                                    <input type="text" class="form-control" name="zone" value="{{ old('zone', $location->zone) }}">
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-check mt-4">
                                                        <input class="form-check-input" type="checkbox" name="is_active" value="1" id="locationActive{{ $location->id }}" @checked($location->is_active)>
                                                        <label class="form-check-label" for="locationActive{{ $location->id }}">Active</label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                                            <button type="submit" class="btn btn-primary">Save Changes</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        @empty
                        <tr>
                            <td colspan="9" class="text-center py-5 text-muted">
                                No IX locations available. Create the first entry.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="createLocationModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <h5 class="modal-title">Add IX Location</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('superadmin.ix-locations.store') }}">
                @csrf
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Name</label>
                            <input type="text" class="form-control" name="name" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">State</label>
                            <input type="text" class="form-control" name="state" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">City</label>
                            <input type="text" class="form-control" name="city">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Node Type</label>
                            <select name="node_type" class="form-select" required>
                                <option value="metro">Metro</option>
                                <option value="edge">Edge</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Switch Details</label>
                            <input type="text" class="form-control" name="switch_details">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Ports</label>
                            <input type="number" class="form-control" name="ports" min="1">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Nodal Officer</label>
                            <input type="text" class="form-control" name="nodal_officer">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Zone</label>
                            <input type="text" class="form-control" name="zone">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Location</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

