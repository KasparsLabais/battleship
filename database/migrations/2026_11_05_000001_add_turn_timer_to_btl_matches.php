<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Turn timer for VS Player: each turn has a time limit; running out skips the
// turn, and too many missed turns in a row forfeits the match.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('btl_matches', function (Blueprint $table) {
            $table->timestamp('turn_started_at')->nullable()->after('turn_user_id');
            $table->unsignedTinyInteger('host_missed')->default(0)->after('turn_started_at');  // consecutive missed turns
            $table->unsignedTinyInteger('guest_missed')->default(0)->after('host_missed');
            $table->string('ended_reason', 16)->nullable()->after('winner_user_id');            // sunk | forfeit | surrender
        });
    }

    public function down(): void
    {
        Schema::table('btl_matches', function (Blueprint $table) {
            $table->dropColumn(['turn_started_at', 'host_missed', 'guest_missed', 'ended_reason']);
        });
    }
};
