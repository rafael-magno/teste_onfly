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
        Schema::create('travel_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->constrained()
                ->restrictOnDelete()
                ->cascadeOnUpdate();
            $table->string('destination_country');
            $table->string('destination_state')->nullable();
            $table->string('destination_city');
            $table->date('departure_date');
            $table->date('return_date')->nullable();
            $table->string('status')->default('requested');
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('destination_country');
            $table->index('destination_state');
            $table->index('destination_city');
            $table->index(['departure_date', 'return_date']);
            $table->index(['user_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('travel_orders');
    }
};
