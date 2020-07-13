<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCronskiPendingTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cronski_processes', function (Blueprint $table) {
            $table->bigIncrements('id');
            // Reference to the "start" record in here.
            $table->unsignedBigInteger('parent_id')->nullable();
            // This will be added to "complete/fail" records once it's retrieved from the "start" records.
            $table->uuid('process_uuid')->nullable();
            // Eg. "start", "finish", "failed".
            $table->string('endpoint', 6);
            $table->json('data');
            $table->tinyInteger('status')->nullable();
            $table->timestamps();

            $table->foreign('parent_id')->references('id')->on('cronski_processes')
                ->onUpdate('cascade')->onDelete('set null');

            $table->index(['endpoint', 'status']);
            $table->index(['parent_id']);
            $table->index(['status', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('cronski_processes');
    }
}
