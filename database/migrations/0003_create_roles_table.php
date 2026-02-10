<?php
/**
 * Creator: Eric Larrea
 * E-mail: eric@latinex.us
 * From: www.latinex.us
 * Date: 10/02/26
 * Time: 16:21
 * Proyecto: cp_lx_auth
 */


use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Capsule\Manager as Capsule;

return new class extends Migration {
    public function up()
    {
        Capsule::schema()->create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id');
            $table->string('slug')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->integer('level')->default(0);
            $table->boolean('is_system')->default(false);
            $table->timestamps();

            $table->index(['tenant_id', 'slug']);
            $table->index(['tenant_id', 'level']);
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('parent_id')->references('id')->on('roles')->onDelete('set null');
        });
    }

    public function down()
    {
        Capsule::schema()->dropIfExists('roles');
    }
};

