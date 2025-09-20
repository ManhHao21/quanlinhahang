<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ingredient_purchases', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ingredient_id');     // Nguyên liệu nào
            $table->decimal('price', 12, 2);                 // Giá tiền mua
            $table->date('purchase_date');                   // Ngày mua
            $table->timestamps();

            $table->foreign('ingredient_id')
                  ->references('id')
                  ->on('ingredients')
                  ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ingredient_purchases');
    }
};
