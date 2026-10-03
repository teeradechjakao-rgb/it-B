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
        Schema::create('exchange_posts', function (Blueprint $table) {
            $table->id();

            // Foreign Key อ้างอิงเจ้าของโพสต์ (users) และหมวดหมู่ (categories)
            $table->foreignId('user_id')->constrained()->onDelete('restrict');
            $table->foreignId('category_id')->constrained()->onDelete('restrict');

            $table->string('title');                           // ชื่อโพสต์
            $table->text('description');                       // รายละเอียดอุปกรณ์
            $table->unsignedTinyInteger('condition_percent');  // สภาพเครื่อง 0-100 (ตรวจช่วงค่าใน Controller)
            $table->text('looking_for')->nullable();           // สิ่งที่อยากได้ (ไม่บังคับ)
            $table->enum('status', ['open', 'closed', 'hidden'])->default('open');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exchange_posts');
    }
};
