<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\IxLocation;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class IxLocationController extends Controller
{
    /**
     * Display IX location management screen.
     */
    public function index()
    {
        try {
            $locations = IxLocation::orderBy('node_type')
                ->orderBy('state')
                ->orderBy('name')
                ->get();

            return view('superadmin.ix.locations', compact('locations'));
        } catch (Exception $e) {
            Log::error('Failed to load IX locations: '.$e->getMessage());

            return redirect()->route('superadmin.dashboard')
                ->with('error', 'Unable to load IX locations right now.');
        }
    }

    /**
     * Store a new IX location.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:ix_locations,name',
            'state' => 'required|string|max:255',
            'city' => 'nullable|string|max:255',
            'node_type' => ['required', Rule::in(['metro', 'edge'])],
            'switch_details' => 'nullable|string|max:255',
            'ports' => 'nullable|integer|min:1|max:1000',
            'nodal_officer' => 'nullable|string|max:255',
            'zone' => 'nullable|string|max:255',
        ]);

        try {
            IxLocation::create($validated);

            return redirect()->route('superadmin.ix-locations.index')
                ->with('success', 'IX location created successfully.');
        } catch (Exception $e) {
            Log::error('Failed to create IX location: '.$e->getMessage());

            return back()->with('error', 'Unable to create location. Please try again.')
                ->withInput();
        }
    }

    /**
     * Update an IX location.
     */
    public function update(Request $request, IxLocation $ixLocation)
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('ix_locations', 'name')->ignore($ixLocation->id),
            ],
            'state' => 'required|string|max:255',
            'city' => 'nullable|string|max:255',
            'node_type' => ['required', Rule::in(['metro', 'edge'])],
            'switch_details' => 'nullable|string|max:255',
            'ports' => 'nullable|integer|min:1|max:1000',
            'nodal_officer' => 'nullable|string|max:255',
            'zone' => 'nullable|string|max:255',
            'is_active' => 'nullable|boolean',
        ]);

        try {
            $ixLocation->update($validated);

            return redirect()->route('superadmin.ix-locations.index')
                ->with('success', 'IX location updated successfully.');
        } catch (Exception $e) {
            Log::error('Failed to update IX location: '.$e->getMessage());

            return back()->with('error', 'Unable to update location. Please try again.')
                ->withInput();
        }
    }

    /**
     * Toggle active flag.
     */
    public function toggleStatus(IxLocation $ixLocation)
    {
        try {
            $ixLocation->update(['is_active' => ! $ixLocation->is_active]);

            return redirect()->route('superadmin.ix-locations.index')
                ->with('success', 'Location status updated.');
        } catch (Exception $e) {
            Log::error('Failed to toggle IX location status: '.$e->getMessage());

            return back()->with('error', 'Unable to update status. Please try again.');
        }
    }

    /**
     * Delete a location.
     */
    public function destroy(IxLocation $ixLocation)
    {
        try {
            $ixLocation->delete();

            return redirect()->route('superadmin.ix-locations.index')
                ->with('success', 'Location removed successfully.');
        } catch (Exception $e) {
            Log::error('Failed to delete IX location: '.$e->getMessage());

            return back()->with('error', 'Unable to delete location. Please try again.');
        }
    }
}
