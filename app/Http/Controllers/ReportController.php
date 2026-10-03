<?php

namespace App\Http\Controllers;

use App\Models\Report;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    // สร้างรายงานความผิดใหม่
    public function store(Request $request)
    {
        $validated = $request->validate([
            'reported_user_id' => 'required|exists:users,id',
            'exchange_post_id' => 'nullable|exists:exchange_posts,id',
            'reason' => 'required|string',
        ], [
            'reported_user_id.required' => 'กรุณาระบุผู้ใช้งานที่ต้องการรายงาน',
            'reason.required' => 'กรุณาระบุเหตุผลในการรายงาน',
        ]);

        // ป้องกันการรายงานตัวเอง
        if ($validated['reported_user_id'] == $request->user()->id) {
            return response()->json(['message' => 'ไม่สามารถรายงานตัวเองได้'], 422);
        }

        // กำหนด reporter_id และ status จากฝั่งเซิร์ฟเวอร์
        $report = new Report($validated);
        $report->reporter_id = $request->user()->id;
        $report->status = 'pending'; // สถานะเริ่มต้นคือ รอดำเนินการ
        $report->save();

        return response()->json([
            'message' => 'ส่งรายงานเรียบร้อยแล้ว ทีมงานจะดำเนินการตรวจสอบ',
            'report' => $report
        ], 201);
    }
}
