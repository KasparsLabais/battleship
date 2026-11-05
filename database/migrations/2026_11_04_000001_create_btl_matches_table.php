<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// VS Player matches: two friends, server-authoritative. Each fleet is stored
// here and never sent to the opponent; shots are resolved on the server.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('btl_matches', function (Blueprint $table) {
            $table->id();
            $table->string('code', 12)->unique();
            $table->unsignedBigInteger('host_user_id');
            $table->unsignedBigInteger('guest_user_id')->nullable();
            $table->string('status', 16)->default('lobby'); // lobby | placing | playing | finished
            $table->json('settings');                         // size, ships, mines, touch
            $table->json('host_fleet')->nullable();           // [[ [r,c], ... ], ...] one list of cells per ship
            $table->json('guest_fleet')->nullable();
            $table->json('mines')->nullable();                // { host: ["r,c"], guest: ["r,c"] } mines on each board
            $table->json('shots')->nullable();                // ordered log, see MatchController::fire
            $table->unsignedBigInteger('turn_user_id')->nullable();
            $table->unsignedBigInteger('winner_user_id')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('btl_matches');
    }
};
