<?php

namespace App\Http\Controllers;

use App\Models\Message;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    // [เพิ่มใหม่] ดึงรายชื่อคู่สนทนาทั้งหมด พร้อมข้อความล่าสุด (กล่องข้อความ/Inbox)
    public function conversations(Request $request)
    {
        $myId = $request->user()->id;

        // ดึงข้อความทุกเส้นที่เกี่ยวข้องกับเรา เรียงใหม่สุดไว้ก่อน
        $messages = Message::with(['sender:id,name,avatar', 'receiver:id,name,avatar'])
            ->where('sender_id', $myId)
            ->orWhere('receiver_id', $myId)
            ->orderByDesc('created_at')
            ->get();

        $conversations = [];

        foreach ($messages as $message) {
            // หา id ของอีกฝ่าย (ไม่ใช่ตัวเราเอง)
            $otherId = $message->sender_id === $myId ? $message->receiver_id : $message->sender_id;

            // เก็บเฉพาะข้อความล่าสุดของแต่ละคู่สนทนา (ตัวแรกที่เจอ เพราะเรียงจากใหม่สุดไว้แล้ว)
            if (!isset($conversations[$otherId])) {
                $otherUser = $message->sender_id === $myId ? $message->receiver : $message->sender;

                $conversations[$otherId] = [
                    'user' => $otherUser,
                    'last_message' => $message->message,
                    'last_message_at' => $message->created_at,
                    'unread_count' => Message::where('sender_id', $otherId)
                        ->where('receiver_id', $myId)
                        ->where('is_read', 0)
                        ->count(),
                ];
            }
        }

        return response()->json(array_values($conversations));
    }

    public function index(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $myId = $request->user()->id;
        $otherUserId = $request->user_id;

        $messages = Message::with(['sender:id,name', 'receiver:id,name', 'exchangePost:id,title'])
            ->where(function ($q) use ($myId, $otherUserId) {
                $q->where('sender_id', $myId)->where('receiver_id', $otherUserId);
            })
            ->orWhere(function ($q) use ($myId, $otherUserId) {
                $q->where('sender_id', $otherUserId)->where('receiver_id', $myId);
            })
            ->orderBy('created_at', 'asc')
            ->get();

        Message::where('sender_id', $otherUserId)
            ->where('receiver_id', $myId)
            ->where('is_read', 0)
            ->update(['is_read' => 1]);

        return response()->json($messages);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'receiver_id' => 'required|exists:users,id',
            'exchange_post_id' => 'nullable|exists:exchange_posts,id',
            'message' => 'required|string',
        ], [
            'receiver_id.required' => 'กรุณาระบุผู้รับข้อความ',
            'message.required' => 'กรุณากรอกข้อความ',
        ]);

        if ($validated['receiver_id'] == $request->user()->id) {
            return response()->json(['message' => 'ไม่สามารถส่งข้อความหาตัวเองได้'], 422);
        }

        $message = new Message($validated);
        $message->sender_id = $request->user()->id;
        $message->is_read = 0;
        $message->save();

        return response()->json([
            'message' => 'ส่งข้อความสำเร็จ',
            'data' => $message->load(['sender:id,name', 'receiver:id,name'])
        ], 201);
    }
}
