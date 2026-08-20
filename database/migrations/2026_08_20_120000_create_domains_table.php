<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('domains', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            // Panel customer who owns this domain; null = held by the company.
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('lifecycle_status')->nullable()->index();
            $table->string('verification_status')->nullable();
            $table->boolean('auto_renew')->default(false);
            $table->boolean('is_premium')->default(false);
            $table->string('privacy_level')->nullable();
            $table->string('nameserver_provider')->nullable();
            $table->json('nameservers')->nullable();
            $table->json('contact_ids')->nullable();
            $table->json('epp_statuses')->nullable();
            $table->timestamp('registered_at')->nullable();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamp('last_synced_at')->nullable();
            // Raw payload from the Spaceship API for fields we don't map yet.
            $table->json('meta')->nullable();
            $table->text('internal_notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('domains');
    }
};
