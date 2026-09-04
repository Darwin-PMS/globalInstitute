<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Roles ─────────────────────────────────────────────
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->boolean('is_system')->default(false);       // system roles can't be deleted
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // ── Permissions ───────────────────────────────────────
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();                   // e.g. students.view
            $table->string('module')->index();                  // e.g. students
            $table->string('action');                           // e.g. view
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // ── Role ↔ Permission pivot ───────────────────────────
        Schema::create('role_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['role_id', 'permission_id']);
        });

        // ── User ↔ Role pivot ─────────────────────────────────
        Schema::create('user_roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'role_id']);
        });

        // ── User ↔ Permission direct overrides ────────────────
        Schema::create('user_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['allow', 'deny'])->default('allow');
            $table->text('note')->nullable();                   // why this override exists
            $table->timestamps();

            $table->unique(['user_id', 'permission_id']);
        });

        // ── Admin Sidebar Menus ───────────────────────────────
        Schema::create('admin_menus', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('admin_menus')->cascadeOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('route_name')->nullable();           // Laravel route name
            $table->string('url')->nullable();                  // direct URL (non-SPA)
            $table->string('icon')->nullable();                 // Material icon name
            $table->string('permission_key')->nullable();       // required permission slug
            $table->enum('menu_type', ['sidebar', 'header', 'both'])->default('sidebar');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_visible')->default(true);
            $table->string('target')->default('_self');         // _self or _blank
            $table->boolean('is_spa')->default(true);           // SPA hash route or full page
            $table->timestamps();
        });

        // ── Role ↔ Menu pivot ─────────────────────────────────
        Schema::create('role_menus', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->foreignId('menu_id')->constrained('admin_menus')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['role_id', 'menu_id']);
        });

        // ── Audit Logs ────────────────────────────────────────
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('user_name')->nullable();
            $table->string('user_email')->nullable();
            $table->string('action');                           // created, updated, deleted, login, etc.
            $table->string('module')->nullable();               // students, roles, permissions, etc.
            $table->string('permission_slug')->nullable();
            $table->string('subject_type')->nullable();         // polymorphic model class
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->enum('result', ['success', 'denied', 'failed'])->default('success');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['module', 'action']);
            $table->index(['subject_type', 'subject_id']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('role_menus');
        Schema::dropIfExists('admin_menus');
        Schema::dropIfExists('user_permissions');
        Schema::dropIfExists('user_roles');
        Schema::dropIfExists('role_permissions');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
    }
};
