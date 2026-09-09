<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class NoticeController extends Controller
{
    /*
      Display a listing of notices.
     */
    public function index(Request $request)
    {
        $query = Notice::with('creator:id,name')
            ->latest('published_date')
            ->latest('id');

        // Search
        if ($request->filled('search')) {
            $search = $request->search;

            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $notices = $query->get()->map(function ($notice) {
            $notice->image_url = $notice->image_path
                ? asset('storage/' . $notice->image_path)
                : null;

            return $notice;
        });

        return response()->json([
            'success' => true,
            'notices' => $notices,
        ]);
    }

    /*
      Store a newly created notice.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => [
                'required',
                'string',
                'max:255',
            ],

            'description' => [
                'required',
                'string',
            ],

            'published_date' => [
                'required',
                'date',
            ],

            'expiry_date' => [
                'nullable',
                'date',
                'after_or_equal:published_date',
            ],

            'status' => [
                'required',
                'in:Active,Inactive',
            ],

            'image' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:5120',
            ],
        ]);

        // Store image
        if ($request->hasFile('image')) {
            $validated['image_path'] =
                $request->file('image')->store('notices', 'public');
        }

        // Remove uploaded file field from validated data
        unset($validated['image']);

        // Set creator
        $validated['created_by'] = $request->user()?->id;

        // Create notice
        $notice = Notice::create($validated);

        // Load creator
        $notice->load('creator:id,name');

        // Generate image URL
        $notice->image_url = $notice->image_path
            ? asset('storage/' . $notice->image_path)
            : null;

        return response()->json([
            'success' => true,
            'message' => 'Notice created successfully.',
            'notice' => $notice,
        ], 201);
    }

    /*
      Display the specified notice.
     */
    public function show(Notice $notice)
    {
        $notice->load('creator:id,name');

        // Generate image URL
        $notice->image_url = $notice->image_path
            ? asset('storage/' . $notice->image_path)
            : null;

        return response()->json([
            'success' => true,
            'notice' => $notice,
        ]);
    }

    /*
      Update the specified notice.
     */
    public function update(Request $request, Notice $notice)
    {
        $validated = $request->validate([
            'title' => [
                'required',
                'string',
                'max:255',
            ],

            'description' => [
                'required',
                'string',
            ],

            'published_date' => [
                'required',
                'date',
            ],

            'expiry_date' => [
                'nullable',
                'date',
                'after_or_equal:published_date',
            ],

            'status' => [
                'required',
                'in:Active,Inactive',
            ],

            'image' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:5120',
            ],
        ]);

        /*
        
         IMPORTANT: Allow expiry date to be cleared
        
        
         If the user removes the expiry date from the edit form,
         save NULL to the database instead of keeping the old date.
        
        */
        $validated['expiry_date'] =
            $request->input('expiry_date') ?: null;

        /*
        
         Handle Image
        
        */
        if ($request->hasFile('image')) {

            // Delete old image
            if ($notice->image_path) {
                Storage::disk('public')->delete(
                    $notice->image_path
                );
            }

            // Store new image
            $validated['image_path'] =
                $request->file('image')->store('notices', 'public');
        }

        // Remove uploaded file field
        unset($validated['image']);

        /*
        
         Update Notice
        
        */
        $notice->update($validated);

        // Reload updated notice
        $notice->refresh();

        // Load creator
        $notice->load('creator:id,name');

        // Generate image URL
        $notice->image_url = $notice->image_path
            ? asset('storage/' . $notice->image_path)
            : null;

        return response()->json([
            'success' => true,
            'message' => 'Notice updated successfully.',
            'notice' => $notice,
        ]);
    }

    /*
      Remove the specified notice.
     */
    public function destroy(Notice $notice)
    {
        // Delete image
        if ($notice->image_path) {
            Storage::disk('public')->delete(
                $notice->image_path
            );
        }

        // Delete notice
        $notice->delete();

        return response()->json([
            'success' => true,
            'message' => 'Notice deleted successfully.',
        ]);
    }
}