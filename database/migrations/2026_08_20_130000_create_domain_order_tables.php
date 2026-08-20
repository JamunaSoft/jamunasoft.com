<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tlds', function (Blueprint $table) {
            $table->id();
            // Stored without the leading dot, e.g. "com", "com.bd".
            $table->string('tld')->unique();
            $table->decimal('register_price', 10, 2)->default(0);
            $table->decimal('renew_price', 10, 2)->default(0);
            $table->decimal('transfer_price', 10, 2)->default(0);
            $table->boolean('is_active')->default(false);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('domain_orders', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('customer_name');
            $table->string('customer_email');
            $table->string('customer_phone')->nullable();
            $table->string('domain_name')->index();
            $table->string('type')->index();
            $table->unsignedTinyInteger('years')->default(1);
            $table->decimal('amount', 10, 2)->default(0);
            $table->string('currency', 3)->default('BDT');
            $table->string('status')->default('pending_payment')->index();
            $table->string('payment_method')->nullable();
            $table->string('payment_reference')->nullable();
            $table->string('spaceship_operation_id')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('domain_orders');
        Schema::dropIfExists('tlds');
    }
};
