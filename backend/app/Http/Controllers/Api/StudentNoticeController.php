<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notice;
use Illuminate\Http\Request;

class StudentNoticeController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        if (!$user || $user->role !== 'student') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.'
            ], 401);
        }

        $today = now()->toDateString();

        $notices = Notice::query()
            ->where('status', 'Active')
            ->whereDate('published_date', '<=', $today)
            ->where(function ($query) use ($today) {
                $query
                    ->whereNull('expiry_date')
                    ->orWhereDate('expiry_date', '>=', $today);
            })
            ->orderByDesc('published_date')
            ->orderByDesc('id')
            ->get()
            ->map(function ($notice) {
                return [
                    'id' => $notice->id,
                    'title' => $notice->title,
                    'description' => $notice->description,
                    'published_date' => $notice->published_date,
                    'expiry_date' => $notice->expiry_date,
                    'image_path' => $notice->image_path,
                    'image_url' => $notice->image_path
                        ? asset('storage/' . $notice->image_path)
                        : null,
                    'status' => $notice->status,
                ];
            });

        return response()->json([
            'success' => true,
            'notices' => $notices,
        ]);
    }
}