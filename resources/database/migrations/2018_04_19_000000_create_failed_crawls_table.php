<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateFailedCrawlsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('failed_crawls', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('source_id');
            $table->string('url')->nullable();
            $table->string('validation_error')->nullable();
            $table->longText('raw_data')->nullable();
            $table->longText('exception');
            $table->timestamp('failed_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('failed_crawls');
    }
}
