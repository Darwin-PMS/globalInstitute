<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('class_rooms', function (Blueprint $table) {
            $table->string('livekit_room_name')->nullable()->after('room_key')
                ->comment('LiveKit room name (class_{id}_{hash})');
            $table->text('livekit_room_sid')->nullable()->after('livekit_room_name')
                ->comment('LiveKit room SID returned by API');
            $table->boolean('livekit_room_active')->default(false)->after('livekit_room_sid')
                ->comment('Whether LiveKit room has been created');
            $table->integer('livekit_participant_count')->default(0)->after('livekit_room_active');
            $table->string('active_egress_id')->nullable()->after('livekit_participant_count')
                ->comment('Active recording egress ID');
            $table->text('livekit_room_metadata')->nullable()->after('active_egress_id')
                ->comment('JSON metadata synced to LiveKit room');

            $table->index('livekit_room_name');
            $table->index('livekit_room_active');
        });
    }

    public function down(): void
    {
        Schema::table('class_rooms', function (Blueprint $table) {
            $table->dropIndex(['livekit_room_name', 'livekit_room_active']);
            $table->dropColumn([
                'livekit_room_name', 'livekit_room_sid', 'livekit_room_active',
                'livekit_participant_count', 'active_egress_id', 'livekit_room_metadata',
            ]);
        });
    }
};
