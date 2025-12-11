<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->index(['channel_id', 'created_at'], 'messages_channel_created_idx');
            $table->index(['parent_id', 'created_at'], 'messages_parent_created_idx');
        });

        Schema::table('reactions', function (Blueprint $table) {
            // unique(message_id, user_id, emoji) already exists; add supporting index for lookups by message
            $table->index('message_id', 'reactions_message_idx');
        });

        Schema::table('message_reads', function (Blueprint $table) {
            // unique(message_id, user_id) already exists; add supporting index for lookups by message
            $table->index('message_id', 'message_reads_message_idx');
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropIndex('messages_channel_created_idx');
            $table->dropIndex('messages_parent_created_idx');
        });

        Schema::table('reactions', function (Blueprint $table) {
            $table->dropIndex('reactions_message_idx');
        });

        Schema::table('message_reads', function (Blueprint $table) {
            $table->dropIndex('message_reads_message_idx');
        });
    }
};

