<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('packages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('category')->default('website')->index();
            $table->foreignId('service_id')->nullable()->constrained()->nullOnDelete();
            $table->text('excerpt')->nullable();
            $table->decimal('price', 12, 2)->nullable();
            $table->decimal('discounted_price', 12, 2)->nullable();
            $table->string('price_suffix')->nullable();
            $table->boolean('is_starting_from')->default(false);
            $table->json('features')->nullable();
            $table->json('excluded_features')->nullable();
            $table->string('delivery_time')->nullable();
            $table->string('support_period')->nullable();
            $table->boolean('is_recommended')->default(false);
            $table->boolean('is_featured')->default(false)->index();
            $table->string('cta_label')->nullable();
            $table->string('cta_url')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->json('translations')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('hosting_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type')->default('shared')->index();
            $table->decimal('monthly_price', 12, 2)->nullable();
            $table->decimal('yearly_price', 12, 2)->nullable();
            $table->decimal('discounted_price', 12, 2)->nullable();
            $table->string('storage')->nullable();
            $table->string('bandwidth')->nullable();
            $table->string('websites')->nullable();
            $table->string('email_accounts')->nullable();
            $table->string('databases')->nullable();
            $table->string('backup_frequency')->nullable();
            $table->boolean('has_ssl')->default(true);
            $table->string('support_level')->nullable();
            $table->json('features')->nullable();
            $table->boolean('is_recommended')->default(false);
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->json('translations')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hosting_plans');
        Schema::dropIfExists('packages');
    }
};
