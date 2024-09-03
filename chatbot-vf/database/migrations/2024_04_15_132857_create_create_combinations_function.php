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
            CREATE FUNCTION createCombinations(w_chatbot_id VARCHAR(500))
            RETURNS varchar(2000) CHARSET latin1 COLLATE latin1_swedish_ci DETERMINISTIC
            BEGIN
                DECLARE i INTEGER DEFAULT 1;
                DECLARE joinClause VARCHAR(2000);
                DECLARE selectClause VARCHAR(2000) DEFAULT '';
                DECLARE var_concepto_id INTEGER DEFAULT 0;
                DECLARE var_chatbot_id VARCHAR(100) DEFAULT '';

                DECLARE cursor1 CURSOR FOR SELECT id, chatbot_id FROM concepts WHERE chatbot_id = w_chatbot_id;
                DECLARE CONTINUE HANDLER FOR NOT FOUND SET i = 0;

                SET @row = 0;
                SET joinClause = ' FROM concept_languages cl ';

                OPEN cursor1;
                bucle: LOOP
                    FETCH cursor1 INTO var_concepto_id, var_chatbot_id;
                    IF i = 0 THEN
                        LEAVE bucle;
                    END IF;

                    IF i = 1 THEN
                        SET joinClause = CONCAT(joinClause, ' JOIN (SELECT c.id as concept_id, lt.term FROM concepts c JOIN lists l on l.chatbot_id = c.chatbot_id JOIN concepts_lists cl2 on c.id = cl2.concept_id AND l.id = cl2.list_id JOIN list_terms lt on lt.list_id = l.id AND c.id = ', var_concepto_id, ' WHERE l.chatbot_id = \"', w_chatbot_id, '\") X', var_concepto_id, ' ON X', var_concepto_id, '.concept_id = cl.concept_id ');
                    ELSE
                        SET joinClause = CONCAT(joinClause, ' CROSS JOIN (SELECT c.id as concept_id, lt.term FROM concepts c JOIN lists l on l.chatbot_id = c.chatbot_id JOIN concepts_lists cl2 on c.id = cl2.concept_id AND l.id = cl2.list_id JOIN list_terms lt on lt.list_id = l.id AND c.id = ', var_concepto_id, ' WHERE l.chatbot_id = \"', w_chatbot_id, '\") X', var_concepto_id);
                    END IF;

                    SET selectClause = CONCAT(selectClause, 'X', var_concepto_id, '.concept_id concepto', var_concepto_id, ', X', var_concepto_id, '.term valor', var_concepto_id);

                    IF i < 4 THEN
                        SET selectClause = CONCAT(selectClause, ', ');
                    END IF;

                    SET i = i + 1;
                END LOOP bucle;
                CLOSE cursor1;

                SET @sqlQuery = CONCAT('SELECT \"', w_chatbot_id, '\", 0 as respuesta, T.* ', ' FROM (SELECT ', selectClause, ' ', joinClause, ') T');
                RETURN @sqlQuery;
            END
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::unprepared('DROP FUNCTION IF EXISTS createCombinations');
    }
};
