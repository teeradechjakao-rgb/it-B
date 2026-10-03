<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('reports', function (Blueprint $table) {
            $table->id();

            // ตารางนี้อ้างอิง users 2 เส้น จึงต้องระบุชื่อตารางเอง constrained('users')
            $table->foreignId('reporter_id')->constrained('users')->onDelete('restrict');       // คนแจ้ง
            $table->foreignId('reported_user_id')->constrained('users')->onDelete('restrict');  // คนถูกแจ้ง

            // โพสต์ที่ถูกแจ้ง (ไม่บังคับ) ลบโพสต์แล้วคำร้องยังอยู่
            $table->foreignId('exchange_post_id')->nullable()->constrained()->onDelete('set null');

            $table->text('reason');                                                              // เหตุผล
            $table->enum('status', ['pending', 'resolved', 'dismissed'])->default('pending');   // รอตรวจ / จัดการแล้ว / ปฏิเสธคำร้อง
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
