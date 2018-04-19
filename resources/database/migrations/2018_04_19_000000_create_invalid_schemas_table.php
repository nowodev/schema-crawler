<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateInvalidSchemasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('invalid_schemas', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('source_id');
            $table->string('url')->nullable();
            $table->string('validation_error')->nullable();
            $table->json('raw_data')->nullable();
            $table->json('extracted_data')->nullable();
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
        Schema::dropIfExists('invalid_schemas');
    }
}
