<?php

use App\Enums\OrderStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->uuid('uuid')->unique();

            // Human-readable reference, the one a customer reads out on the
            // phone. The uuid stays the API identifier.
            $table->string('order_number')->unique();

            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default(OrderStatus::PENDING->value)->index();

            // Captured at checkout from the cart, never recomputed afterwards.
            // An order is a record of what was agreed, so a later price change
            // must not alter it.
            $table->unsignedInteger('total_cents');

            $table->string('delivery_address');
            $table->string('phone');
            $table->text('notes')->nullable();
            $table->timestamp('placed_at');

            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
