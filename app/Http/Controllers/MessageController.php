<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\Registration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Exception;

class MessageController extends Controller
{
    /**
     * Display user's inbox.
     */
    public function index()
    {
        try {
            $userId = session('user_id');
            $user = Registration::find($userId);

            if (!$user) {
                return redirect()->route('login.index')
                    ->with('error', 'User not found. Please login again.');
            }

            $messages = $user->messages()->orderBy('created_at', 'desc')->paginate(10);

            return response()->view('user.messages.index', compact('messages', 'user'))
                ->header('Cache-Control', 'no-cache, no-store, max-age=0, must-revalidate')
                ->header('Pragma', 'no-cache')
                ->header('Expires', 'Fri, 01 Jan 1990 00:00:00 GMT');
        } catch (Exception $e) {
            Log::error('Error loading messages: ' . $e->getMessage());
            return redirect()->route('user.dashboard')
                ->with('error', 'Unable to load messages. Please try again.');
        }
    }

    /**
     * Display a specific message.
     */
    public function show($id)
    {
        try {
            $userId = session('user_id');
            $user = Registration::find($userId);

            if (!$user) {
                return redirect()->route('login.index')
                    ->with('error', 'User not found. Please login again.');
            }

            $message = Message::where('id', $id)
                ->where('user_id', $userId)
                ->firstOrFail();

            // Mark message as read
            if (!$message->is_read) {
                $message->markAsRead();
            }

            return response()->view('user.messages.show', compact('message', 'user'))
                ->header('Cache-Control', 'no-cache, no-store, max-age=0, must-revalidate')
                ->header('Pragma', 'no-cache')
                ->header('Expires', 'Fri, 01 Jan 1990 00:00:00 GMT');
        } catch (Exception $e) {
            Log::error('Error loading message: ' . $e->getMessage());
            return redirect()->route('user.messages.index')
                ->with('error', 'Message not found.');
        }
    }

    /**
     * Mark message as read (AJAX).
     */
    public function markAsRead($id)
    {
        try {
            $userId = session('user_id');
            $message = Message::where('id', $id)
                ->where('user_id', $userId)
                ->firstOrFail();

            $message->markAsRead();

            return response()->json([
                'success' => true,
                'unread_count' => Registration::find($userId)->unreadMessagesCount()
            ]);
        } catch (Exception $e) {
            Log::error('Error marking message as read: ' . $e->getMessage());
            return response()->json(['success' => false], 500);
        }
    }

    /**
     * Get unread messages count (AJAX).
     */
    public function unreadCount()
    {
        try {
            $userId = session('user_id');
            $user = Registration::find($userId);

            if (!$user) {
                return response()->json(['count' => 0]);
            }

            return response()->json([
                'count' => $user->unreadMessagesCount()
            ]);
        } catch (Exception $e) {
            Log::error('Error getting unread count: ' . $e->getMessage());
            return response()->json(['count' => 0]);
        }
    }

    /**
     * Reply to a message.
     */
    public function reply(Request $request, $id)
    {
        try {
            $userId = session('user_id');
            $user = Registration::find($userId);

            if (!$user) {
                return redirect()->route('login.index')
                    ->with('error', 'User not found. Please login again.');
            }

            $message = Message::where('id', $id)
                ->where('user_id', $userId)
                ->firstOrFail();

            // Check if user has already replied
            if ($message->user_reply) {
                return back()->with('error', 'You have already replied to this message.');
            }

            // Check if user has received any message from admin (can only reply to admin messages)
            if ($message->sent_by !== 'admin') {
                return back()->with('error', 'You can only reply to admin messages.');
            }

            $validated = $request->validate([
                'user_reply' => 'required|string|min:10|max:2000',
            ], [
                'user_reply.required' => 'Please enter your reply.',
                'user_reply.min' => 'Reply must be at least 10 characters.',
                'user_reply.max' => 'Reply cannot exceed 2000 characters.',
            ]);

            $message->update([
                'user_reply' => $validated['user_reply'],
                'user_replied_at' => now('Asia/Kolkata'),
            ]);

            return back()->with('success', 'Your reply has been sent successfully.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (Exception $e) {
            Log::error('Error replying to message: ' . $e->getMessage());
            return back()->with('error', 'An error occurred while sending your reply. Please try again.');
        }
    }
}
