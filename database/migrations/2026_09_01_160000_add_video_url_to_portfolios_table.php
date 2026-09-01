<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('portfolios', function (Blueprint $table) {
            // YouTube/Vimeo link for motion-graphics work, embedded on the
            // case-study page instead of hosting heavy video files locally.
            $table->string('video_url')->nullable()->after('project_url');
        });
    }

    public function down(): void
    {
        Schema::table('portfolios', function (Blueprint $table) {
            $table->dropColumn('video_url');
        });
    }
};
