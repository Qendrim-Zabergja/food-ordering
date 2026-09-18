<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->uuid('uuid')->unique();
            $table->foreignId('product_category_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();

            // Money is stored in minor units (cents) as an integer. Storing a
            // price as a float would round, and rounding a price is a bug that
            // only shows up once real orders are being totalled.
            $table->unsignedInteger('price_cents');

            $table->string('image_url')->nullable();
            $table->boolean('is_available')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['product_category_id', 'is_available']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
