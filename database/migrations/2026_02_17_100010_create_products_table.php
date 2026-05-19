<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->decimal('purchase_price', 12, 2)->default(0);
            $table->decimal('cost_price', 12, 2)->default(0);
            $table->decimal('sale_price', 12, 2)->default(0);
            $table->decimal('profit', 12, 2)->default(0);
            $table->unsignedBigInteger('declared_by_user_id')->nullable();
            $table->unsignedBigInteger('declared_for_user_id')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
