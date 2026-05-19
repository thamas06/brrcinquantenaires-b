<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('employee_id')->nullable();
            $table->integer('qty')->default(1);
            $table->decimal('unit_price', 12, 2)->default(0);
            $table->decimal('total_sale', 14, 2)->default(0);
            $table->decimal('total_profit', 14, 2)->default(0);
            $table->timestamps();

            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};
