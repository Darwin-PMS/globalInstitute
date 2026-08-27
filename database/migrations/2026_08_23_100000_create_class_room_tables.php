<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Live Class Room tables.
 *
 * A room is a first-class entity with its own session state, bound to a
 * polymorphic "subject" — the thing that was scheduled. Today the subject is an
 * online_classes row; a future standalone product can point rooms at
 * class_sessions or an external LMS entity without touching these tables.
 *
 * Everything a room owns hangs off class_room_id, so the room layer can be
 * lifted out as a package.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('class_rooms', function (Blueprint $table) {
            $table->id();

            // The scheduled thing this room belongs to (OnlineClass today).
            $table->morphs('subject');

            // Opaque, non-sequential handle for URLs so room links are not guessable.
            $table->uuid('room_key')->unique();

            $table->string('state', 32)->default('scheduled')->index();

            // Room session lifecycle — independent of the subject's own status.
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();

            // Who is hosting, and whether a host is currently present.
            $table->foreignId('host_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('host_last_seen_at')->nullable();

            /*
             * Per-room settings. Each room can be configured independently:
             * chat_enabled, announcements_enabled, materials_visible,
             * recordings_visible, attendance_tracking, allow_late_join,
             * lobby_opens_minutes_before.
             */
            $table->json('settings')->nullable();

            // Denormalised counters so the room header does not need aggregates.
            $table->unsignedInteger('participant_count')->default(0);
            $table->unsignedInteger('peak_participant_count')->default(0);
            $table->unsignedInteger('message_count')->default(0);

            $table->unsignedBigInteger('tenant_id')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // One room per scheduled subject.
            $table->unique(['subject_type', 'subject_id'], 'class_rooms_subject_unique');
            $table->index(['tenant_id', 'state']);
        });

        /*
         * Presence, not attendance.
         *
         * This records who is/was inside the room and for how long. The graded
         * record stays in class_attendance; presence is what feeds it and what
         * powers the live roster.
         */
        Schema::create('class_room_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_room_id')->constrained('class_rooms')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();

            $table->string('role', 32)->default('participant');
            $table->string('display_name')->nullable();

            $table->timestamp('first_joined_at')->nullable();
            $table->timestamp('last_joined_at')->nullable();
            $table->timestamp('left_at')->nullable();
            $table->timestamp('last_seen_at')->nullable()->index();

            $table->unsignedInteger('join_count')->default(0);
            // Accumulated across re-joins, so a flaky connection is not punished.
            $table->unsignedInteger('total_seconds')->default(0);

            $table->timestamps();

            $table->unique(['class_room_id', 'user_id'], 'class_room_participant_unique');
            $table->index(['class_room_id', 'role']);
        });

        Schema::create('class_room_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_room_id')->constrained('class_rooms')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('type', 32)->default('message');
            $table->text('body');
            $table->boolean('is_pinned')->default(false);
            $table->timestamp('deleted_by_host_at')->nullable();

            $table->timestamps();

            // Supports the incremental "?after=<id>" fetch the client polls with.
            $table->index(['class_room_id', 'id']);
            $table->index(['class_room_id', 'type']);
        });

        Schema::create('class_room_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_room_id')->constrained('class_rooms')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('type', 48);
            $table->string('description')->nullable();
            $table->json('meta')->nullable();

            $table->timestamps();

            $table->index(['class_room_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_room_events');
        Schema::dropIfExists('class_room_messages');
        Schema::dropIfExists('class_room_participants');
        Schema::dropIfExists('class_rooms');
    }
};
