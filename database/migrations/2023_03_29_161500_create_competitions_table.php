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
        Schema::create('competitions', function (Blueprint $table) {
            $table->bigIncrements('id')->nullable(false)->unsigned();
            $table->timestamp('changed')->useCurrentOnUpdate()->useCurrent()->nullable(false);
            $table->foreignId('user_id')->nullable()->constrained('users')
                ->onDelete('set null')->onUpdate('cascade');
            $table->text('name')->nullable();
            $table->text('location')->nullable();
            $table->date('date')->nullable()->default(NULL);
            $table->tinyInteger('feature_set')->default(0);
            $table->tinyInteger('areas')->default(0);
            $table->tinyInteger('live')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('competitions');
    }
};