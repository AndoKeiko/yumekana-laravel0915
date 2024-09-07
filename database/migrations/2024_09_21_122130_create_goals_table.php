<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGoalsTable extends Migration
{
    public function up()
    {
      Schema::create('goals', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained()->onDelete('cascade');
        $table->string('name');
        $table->text('current_status')->nullable();
        $table->date('period_start');
        $table->date('period_end');
        $table->text('description')->nullable();
        $table->enum('status', ['not_started', 'in_progress', 'completed', 'cancelled'])->default('not_started');
        $table->integer('progress_percentage')->default(0);
        $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('goals');
    }
}