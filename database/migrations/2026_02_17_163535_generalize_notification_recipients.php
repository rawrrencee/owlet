<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::rename('stocktake_notification_recipients', 'notification_recipients');

        Schema::table('notification_recipients', function (Blueprint $table) {
            $table->string('event_type', 50)->default('stocktake')->after('id');
            $table->index('event_type');
        });
    }

    public function down(): void
    {
        Schema::table('notification_recipients', function (Blueprint $table) {
            $table->dropIndex(['event_type']);
            $table->dropColumn('event_type');
        });

        Schema::rename('notification_recipients', 'stocktake_notification_recipients');
    }
};
