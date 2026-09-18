<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->uuid('uuid')->unique();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();

            // nullOnDelete, not cascade: deleting a product from the menu must
            // never delete the history of it having been sold.
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();

            // The product's name and price as they were at checkout. Renaming or
            // repricing a product later does not rewrite what this order says,
            // which is the whole reason these columns are copies and not joins.
            $table->string('product_name');
            $table->unsignedInteger('unit_price_cents');

            $table->unsignedSmallInteger('quantity');
            $table->unsignedInteger('line_total_cents');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
