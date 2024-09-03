<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::unprepared("
            CREATE FUNCTION chat_next_transition(w_current_state INT, w_text VARCHAR(100), w_chatbot_log_id VARCHAR(250)) RETURNS int(11) READS SQL DATA
            BEGIN

                DECLARE a_result INTEGER DEFAULT 0;
                DECLARE a_valid_response INTEGER DEFAULT 0;

                IF w_current_state = 0 THEN
                    SELECT c.state
                    INTO a_result
                    FROM conversations c
                    WHERE c.chatbot_log_id = w_chatbot_log_id
                    LIMIT 1;

                    RETURN a_result;
                END IF;

                SELECT COUNT(*)
                INTO a_valid_response
                FROM nodes cp
                    JOIN nodes_transitions nt ON nt.origin = 1
                WHERE cp.node = 1 and cp.deleted = 0 AND nt.deleted = 0
                    AND \"yes\" != \"\" AND (nt.transition IN ('*', \"yes\") OR nt.transition = 'bc2d8396-2dc2-4090-9268-d0c62ef54ad9');

                IF a_valid_response = 0 THEN
                    RETURN -1;
                END IF;

                SELECT destination
                INTO a_result
                FROM nodes_transitions nt
                WHERE nt.origin = w_current_state
                  AND (nt.transition COLLATE utf8mb4_unicode_ci IN ('*', w_text COLLATE utf8mb4_unicode_ci))
                  AND nt.chatbot_log_id = w_chatbot_log_id AND deleted = 0;

                RETURN a_result;
            END;
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::unprepared('DROP FUNCTION IF EXISTS chat_next_transition');
    }
};
