<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('bill_code')->unique(); // Mã hóa đơn, đảm bảo không trùng lặp
            $table->string('table_number');       // Số bàn
            $table->decimal('total', 10, 2)->default(0); // Tổng tiền
            $table->dateTime('date_ordered'); // Ngày đặt hàng
            $table->dateTime('start_time')->nullable(); // Thời gian bắt đầu
            $table->dateTime('end_time')->nullable();
            $table->string('status')->default('pending'); // Trạng thái: pending, success, canceled
            $table->timestamps();
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
