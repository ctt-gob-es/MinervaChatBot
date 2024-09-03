<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class CreatePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $role1 = Role::updateOrCreate(
            ['name' => 'SuperAdmin']
        );
        $role6 = Role::updateOrCreate(
            ['name' => 'Api']
        );


        $permissions = [
            //manage_dashboard
            ["permission" => 'manage_dashboard', "name_es" => 'Gestión Métricas', "guard_name" => 'web', "module" => 'manage_dashboard'],

            //manage_conversations
            ["permission" => 'manage_conversations', "name_es" => 'Gestión Conversaciones', "guard_name" => 'web', "module" => 'manage_conversations'],
            ["permission" => 'detail_conversations', "name_es" => 'Detalle Conversación', "guard_name" => 'web', "module" => 'manage_conversations'],

            //manage_chatbots
            ["permission" => 'manage_chatbots', "name_es" => 'Gestión ChatBot', "guard_name" => 'web', "module" => 'manage_chatbots'],
            ["permission" => 'chatbots_add', "name_es" => 'Agregar ChatBot', "guard_name" => 'web', "module" => 'manage_chatbots'],
            ["permission" => 'chatbots_edit', "name_es" => 'Editar ChatBot', "guard_name" => 'web', "module" => 'manage_chatbots'],
            ["permission" => 'chatbots_build', "name_es" => 'Construir ChatBot', "guard_name" => 'web', "module" => 'manage_chatbots'],
            ["permission" => 'chatbots_build_save', "name_es" => 'Guardar construcción ChatBot', "guard_name" => 'web', "module" => 'manage_chatbots'],
            ["permission" => 'manage_knowledge', "name_es" => 'Gestión conocimiento ChatBot', "guard_name" => 'web', "module" => 'manage_chatbots'],
            ["permission" => 'chatbots_settings', "name_es" => 'Ajustes ChatBot', "guard_name" => 'web', "module" => 'manage_chatbots'],
            ["permission" => 'chatbots_history', "name_es" => 'Historial ChatBot', "guard_name" => 'web', "module" => 'manage_chatbots'],
            ["permission" => 'chatbots_delete', "name_es" => 'Eliminar ChatBot', "guard_name" => 'web', "module" => 'manage_chatbots'],
            ["permission" => 'training', "name_es" => 'Entrenamiento ChatBot', "guard_name" => 'web', "module" => 'manage_chatbots'],
            ["permission" => 'test', "name_es" => 'Probar ChatBot', "guard_name" => 'web', "module" => 'manage_chatbots'],
            ["permission" => 'recover_chatbot', "name_es" => 'Recuperar ChatBot', "guard_name" => 'web', "module" => 'manage_chatbots'],

            //ajust_chatbot
            ["permission" => 'manage_settings_hours', "name_es" => 'Configuración Horas', "guard_name" => 'web', "module" => 'ajust_chatbots'],
            ["permission" => 'manage_settings_holidays', "name_es" => 'Configuración Festivos', "guard_name" => 'web', "module" => 'ajust_chatbots'],
            ["permission" => 'chatbots_settings_info', "name_es" => 'Ver información General', "guard_name" => 'web', "module" => 'ajust_chatbots'],
            ["permission" => 'chatbots_settings_edit', "name_es" => 'Editar configuración ChatBot', "guard_name" => 'web', "module" => 'ajust_chatbots'],
            ["permission" => 'add_holiday', "name_es" => 'Agregar festivo', "guard_name" => 'web', "module" => 'ajust_chatbots'],
            ["permission" => 'see_holiday_information', "name_es" => 'Ver festivo', "guard_name" => 'web', "module" => 'ajust_chatbots'],
            ["permission" => 'edit_holiday', "name_es" => 'Editar festivo', "guard_name" => 'web', "module" => 'ajust_chatbots'],
            ["permission" => 'delete_holiday', "name_es" => 'Eliminar festivo', "guard_name" => 'web', "module" => 'ajust_chatbots'],

            //manage_knowledge
            ["permission" => 'manage_intention', "name_es" => 'Gestión Intenciones', "guard_name" => 'web', "module" => 'manage_knowledge'],
            ["permission" => 'manage_thematic', "name_es" => 'Gestión Temáticas', "guard_name" => 'web', "module" => 'manage_knowledge'],
            ["permission" => 'manage_concepts', "name_es" => 'Gestión Contextos', "guard_name" => 'web', "module" => 'manage_knowledge'],
            ["permission" => 'manage_lists', "name_es" => 'Gestión Listas', "guard_name" => 'web', "module" => 'manage_knowledge'],

            //manage_intentions
            ["permission" => 'knowledge_information', "name_es" => 'Información Intenciones', "guard_name" => 'web', "module" => 'manage_intention'],
            ["permission" => 'knowledge_add', "name_es" => 'Agregar Intenciones', "guard_name" => 'web', "module" => 'manage_intention'],
            ["permission" => 'knowledge_edit', "name_es" => 'Editar Intenciones', "guard_name" => 'web', "module" => 'manage_intention'],
            ["permission" => 'knowledge_delete', "name_es" => 'Eliminar Intenciones', "guard_name" => 'web', "module" => 'manage_intention'],
            ["permission" => 'manage_responses', "name_es" => 'Gestionar respuestas', "guard_name" => 'web', "module" => 'manage_intention'],
            ["permission" => 'intentions_history', "name_es" => 'Historial Intenciones', "guard_name" => 'web', "module" => 'manage_intention'],
            ["permission" => 'generate_combinations', "name_es" => 'Generar combinaciones', "guard_name" => 'web', "module" => 'manage_intention'],
            ["permission" => 'download_template_intentions', "name_es" => 'Plantilla intenciones', "guard_name" => 'web', "module" => 'manage_intention'],
            ["permission" => 'import_template_intentions', "name_es" => 'Importar plantilla Intenciones', "guard_name" => 'web', "module" => 'manage_intention'],

            //manage_thematic
            ["permission" => 'thematic_add', "name_es" => 'Agregar Temática', "guard_name" => 'web', "module" => 'manage_thematic'],
            ["permission" => 'thematic_edit', "name_es" => 'Editar Temática', "guard_name" => 'web', "module" => 'manage_thematic'],
            ["permission" => 'thematic_delete', "name_es" => 'Eliminar Temática', "guard_name" => 'web', "module" => 'manage_thematic'],
            ["permission" => 'thematic_export', "name_es" => 'Exportar Intenciones', "guard_name" => 'web', "module" => 'manage_thematic'],
            ["permission" => 'thematic_import', "name_es" => 'Importar Intenciones', "guard_name" => 'web', "module" => 'manage_thematic'],

            //manage_concepts
            ["permission" => 'concepts_add', "name_es" => 'Agregar Contexto', "guard_name" => 'web', "module" => 'manage_concepts'],
            ["permission" => 'concepts_view', "name_es" => 'Ver Contexto', "guard_name" => 'web', "module" => 'manage_concepts'],
            ["permission" => 'concepts_edit', "name_es" => 'Editar Contexto', "guard_name" => 'web', "module" => 'manage_concepts'],
            ["permission" => 'concepts_delete', "name_es" => 'Eliminar Contexto', "guard_name" => 'web', "module" => 'manage_concepts'],

            //manage_lists
            ["permission" => 'lists_add', "name_es" => 'Agregar Lista', "guard_name" => 'web', "module" => 'manage_lists'],
            ["permission" => 'lists_information', "name_es" => 'Información Lista', "guard_name" => 'web', "module" => 'manage_lists'],
            ["permission" => 'lists_edit', "name_es" => 'Editar Lista', "guard_name" => 'web', "module" => 'manage_lists'],
            ["permission" => 'lists_delete', "name_es" => 'Eliminar Lista', "guard_name" => 'web', "module" => 'manage_lists'],

            //manage_clients
            ["permission" => 'manage_clients', "name_es" => 'Gestión Clientes', "guard_name" => 'web', "module" => 'manage_clients'],
            ["permission" => 'clients_add', "name_es" => 'Agregar Cliente', "guard_name" => 'web', "module" => 'manage_clients'],
            ["permission" => 'clients_edit', "name_es" => 'Editar Cliente', "guard_name" => 'web', "module" => 'manage_clients'],
            ["permission" => 'clients_delete', "name_es" => 'Eliminar Cliente', "guard_name" => 'web', "module" => 'manage_clients'],
            ["permission" => 'clients_ajusts', "name_es" => 'Ajustes Cliente', "guard_name" => 'web', "module" => 'manage_clients'],
            ["permission" => 'clients_chatbot', "name_es" => 'Chatbots', "guard_name" => 'web', "module" => 'manage_clients'],

            //manage_users
            ["permission" => 'manage_users', "name_es" => 'Gestión Usuarios', "guard_name" => 'web', "module" => 'manage_users'],
            ["permission" => 'users_add', "name_es" => 'Agregar Usuarios', "guard_name" => 'web', "module" => 'manage_users'],
            ["permission" => 'users_edit', "name_es" => 'Editar Usuarios', "guard_name" => 'web', "module" => 'manage_users'],
            ["permission" => 'users_delete', "name_es" => 'Eliminar Usuarios', "guard_name" => 'web', "module" => 'manage_users'],
            ["permission" => 'users_history', "name_es" => 'Historial Usuarios', "guard_name" => 'web', "module" => 'manage_users'],

            //manage_settings
            ["permission" => 'manage_settings', "name_es" => 'Gestión configuración general', "guard_name" => 'web', "module" => 'manage_settings'],
            ["permission" => 'system_settings', "name_es" => 'Ajustes predeterminados del sistema', "guard_name" => 'web', "module" => 'manage_settings'],
            ["permission" => 'edit_default_settings', "name_es" => 'Editar ajuste predeterminado del sistema', "guard_name" => 'web', "module" => 'manage_settings'],
            ["permission" => 'default_chatbot_settings', "name_es" => 'Ajustes predeterminados chatbot', "guard_name" => 'web', "module" => 'manage_settings'],
            ["permission" => 'default_chatbot_edit', "name_es" => 'Editar ajuste predeterminado chatbot', "guard_name" => 'web', "module" => 'manage_settings'],
            ["permission" => 'add_settings', "name_es" => 'Agregar ajuste personalizado de cliente', "guard_name" => 'web', "module" => 'manage_settings'],
            ["permission" => 'edit_settings', "name_es" => 'Editar ajuste personalizado de cliente', "guard_name" => 'web', "module" => 'manage_settings'],
            ["permission" => 'delete_settings', "name_es" => 'Eliminar ajuste personalizado de cliente', "guard_name" => 'web', "module" => 'manage_settings'],
            ["permission" => 'edit_profile', "name_es" => 'Editar perfil', "guard_name" => 'web', "module" => 'manage_settings'],
            ["permission" => 'change_password', "name_es" => 'Cambiar contraseña', "guard_name" => 'web', "module" => 'manage_settings'],

            //manage_roles
            ["permission" => 'manage_roles', "name_es" => 'Gestión Roles', "guard_name" => 'web', "module" => 'manage_roles'],
            ["permission" => 'add_roles', "name_es" => 'Agregar rol', "guard_name" => 'web', "module" => 'manage_roles'],
            ["permission" => 'edit_roles', "name_es" => 'Editar rol', "guard_name" => 'web', "module" => 'manage_roles'],
            ["permission" => 'delete_roles', "name_es" => 'Eliminar rol', "guard_name" => 'web', "module" => 'manage_roles'],

            //manage_training
            ["permission" => 'manage_supervised_training', "name_es" => 'Gestión Aprendizaje Supervisado', "guard_name" => 'web', "module" => 'manage_training'],
            ["permission" => 'answer_negative', "name_es" => 'Gestión respuesta negativa', "guard_name" => 'web', "module" => 'manage_training'],
            ["permission" => 'answer_no_valuation', "name_es" => 'Gestión respuesta sin valoración', "guard_name" => 'web', "module" => 'manage_training'],
            ["permission" => 'answer_positive', "name_es" => 'Gestión respuesta positiva', "guard_name" => 'web', "module" => 'manage_training'],
            ["permission" => 'answer_uncategorized', "name_es" => 'Gestión respuesta sin categoría', "guard_name" => 'web', "module" => 'manage_training'],
            ["permission" => 'validate_positive_response', "name_es" => 'Validar positiva la respuesta', "guard_name" => 'web', "module" => 'manage_training'],
            ["permission" => 'validate_negative_response', "name_es" => 'Validar negativa la respuesta', "guard_name" => 'web', "module" => 'manage_training'],
            ["permission" => 'discard_response', "name_es" => 'Descartar la respuesta', "guard_name" => 'web', "module" => 'manage_training'],
            //manage_training_manual
            ["permission" => 'manage_manual_training', "name_es" => 'Gestión Entrenamiento manual', "guard_name" => 'web', "module" => 'manage_training_manual'],
            ["permission" => 'question_intentions', "name_es" => 'Gestión pregunta con intención', "guard_name" => 'web', "module" => 'manage_training_manual'],
            ["permission" => 'question_no_intentions', "name_es" => 'Gestión pregunta sin intención', "guard_name" => 'web', "module" => 'manage_training_manual'],
            ["permission" => 'validate_positive_response_manual', "name_es" => 'Validar positiva la respuesta', "guard_name" => 'web', "module" => 'manage_training_manual'],
            ["permission" => 'validate_negative_response_manual', "name_es" => 'Validar negativa la respuesta', "guard_name" => 'web', "module" => 'manage_training_manual'],
            ["permission" => 'validate_negative_response_manual', "name_es" => 'Validar negativa la respuesta', "guard_name" => 'web', "module" => 'manage_training_manual'],
            ["permission" => 'discard_response_manual', "name_es" => 'Descartar la respuesta', "guard_name" => 'web', "module" => 'manage_training_manual'],
            ["permission" => 'download_template', "name_es" => 'Descargar plantilla', "guard_name" => 'web', "module" => 'manage_training_manual'],
            ["permission" => 'import_template', "name_es" => 'Importar plantilla', "guard_name" => 'web', "module" => 'manage_training_manual'],

        ];

        foreach ($permissions as $permissionData) {
            $existingPermission = Permission::where([
                'name' => $permissionData['permission'],
                'guard_name' => $permissionData['guard_name'],
            ])->first();

            if ($existingPermission) {
                $existingPermission->update([
                    'module' => $permissionData['module'],
                    'name_es' => $permissionData['name_es']
                ]);
            } else {
                Permission::create([
                    'name' => $permissionData['permission'],
                    'name_es' => $permissionData['name_es'],
                    'guard_name' => $permissionData['guard_name'],
                    'module' => $permissionData['module'],
                ]);
            }
        }

        $existingPermissions = Permission::all();

        foreach ($existingPermissions as $existingPermission) {
            $permissionExistsInSeeder = collect($permissions)->contains(function ($permissionData) use ($existingPermission) {
                return $permissionData['permission'] === $existingPermission->name &&
                    $permissionData['guard_name'] === $existingPermission->guard_name;
            });

            if (!$permissionExistsInSeeder) {
                $existingPermission->delete();
            }
        }

        // Sync de permisos para roles
        $roles = Role::all();
        foreach ($roles as $role) {
            if ($role->name == 'SuperAdmin') {
                $role->syncPermissions(Permission::pluck('name'));
            }
        }
    }
}
