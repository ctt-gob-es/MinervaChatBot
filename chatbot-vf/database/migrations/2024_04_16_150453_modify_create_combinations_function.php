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
        // Eliminar la función existente si es que existe
        DB::unprepared('DROP FUNCTION IF EXISTS createCombinations');

        // Crear la función con las modificaciones
        DB::unprepared("
            CREATE FUNCTION createCombinations(w_intention_id VARCHAR(500))
            RETURNS varchar(2000) CHARSET latin1 COLLATE latin1_swedish_ci DETERMINISTIC
            BEGIN
                DECLARE i INTEGER DEFAULT 1;
                DECLARE joinClause VARCHAR(2000);
                DECLARE selectClause VARCHAR(2000) DEFAULT '';
                DECLARE var_diagrama_id VARCHAR(100) DEFAULT '';
                DECLARE sqlQuery VARCHAR(4000) DEFAULT '';
                DECLARE num_conceptos  INTEGER DEFAULT 4;
                DECLARE var_final INTEGER DEFAULT 0;
                DECLARE var_concepto_id INTEGER DEFAULT 0;
                DECLARE var_chatbot_id VARCHAR(100) DEFAULT '';

                DECLARE cursor1 CURSOR FOR SELECT concept_id, chatbot_id FROM intentions i JOIN intentions_concepts ic ON i.id = ic.intention_id WHERE i.id = w_intention_id;
                DECLARE CONTINUE HANDLER FOR NOT FOUND SET var_final = 1;

                SET num_conceptos = (SELECT COUNT(*) FROM intentions_concepts WHERE intention_id = w_intention_id);

                SET @row := 0;
                SET joinClause := ' FROM concepts cl ';

                OPEN cursor1;
                bucle: LOOP
                    FETCH cursor1 INTO var_concepto_id, var_chatbot_id;
                    IF var_final = 1 THEN
                    LEAVE bucle;
                    END IF;

                    IF i = 1 THEN
                        SET joinClause := CONCAT(joinClause, ' JOIN (SELECT c.id as concept_id, lt.term FROM concepts c JOIN lists l on l.chatbot_id = c.chatbot_id JOIN concepts_lists cl2 on c.id = cl2.concept_id AND l.id = cl2.list_id JOIN list_terms lt on lt.list_id = l.id AND c.id = ', var_concepto_id, ' WHERE l.chatbot_id = \'', var_chatbot_id, '\') X', var_concepto_id, ' ON X', var_concepto_id, '.concept_id = cl.id ');
                    ELSE
                        SET joinClause := CONCAT(joinClause, ' CROSS JOIN (SELECT c.id as concept_id, lt.term FROM concepts c JOIN lists l on l.chatbot_id = c.chatbot_id JOIN concepts_lists cl2 on c.id = cl2.concept_id AND l.id = cl2.list_id JOIN list_terms lt on lt.list_id = l.id AND c.id = ', var_concepto_id, ' WHERE l.chatbot_id = \'', var_chatbot_id, '\') X', var_concepto_id);
                    END IF;
                    SET selectClause := CONCAT(selectClause, 'X', var_concepto_id, '.concept_id concepto', i, ', X', var_concepto_id, '.term valor', i);

                    IF i < num_conceptos THEN
                        SET selectClause := CONCAT(selectClause, ', ');
                    END IF;

                    SET i := i + 1;
                END LOOP bucle;
                CLOSE cursor1;

                SET sqlQuery := CONCAT('SELECT /*', w_intention_id , ' as intention_id, 0 as respuesta,*/ T.* ', ' FROM (SELECT ', selectClause, ' ', joinClause, ') T');

                RETURN sqlQuery;
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
