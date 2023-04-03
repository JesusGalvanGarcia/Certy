<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\UserService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class ToolsController extends Controller
{

    private $prefixCode = 'Tools';

    public function storePermissions(Request $request)
    {
        app()->make(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        try {

            // Se validan los parámetros de entrada
            $validator = Validator::make(request()->all(), [
                'user_id' => 'Required|Integer|Min:1',
                'permissions' => 'Required|Array',
                'permissions.*' => 'Required|String|Distinct',
            ]);

            // Si la validación detecta un error regresa la descripción del error
            if ($validator->fails())
                return response()->json([
                    'title' => 'Datos Faltantes',
                    'message' => $validator->messages()->first(),
                    'code' => $this->prefixCode . 'X201'
                ], 400);

            // Se valida que el usuario este vigente
            $user = UserService::checkUser(request('user_id'));

            if (!$user)
                return response()->json([
                    'title' => 'Fallo en la consulta',
                    'message' => 'Usuario no encontrado.',
                    'code' => $this->prefixCode . 'X202'
                ], 400);

            // Se verifican que el usuario tenga permiso para crear nuevos permisos
            if (!$user->hasPermissionTo('create_permissions'))
                return response()->json([
                    'title' => 'Fallo en el proceso',
                    'message' => 'Usuario no tiene permiso.',
                    'code' => $this->prefixCode . 'X203'
                ], 400);

            DB::beginTransaction();

            // Por cada permiso enviado se almacena en BD, si el permiso ya existe regresara un error
            foreach ($request->permissions as $permission)
                Permission::create(['name' => $permission]);

            DB::commit();

            return response()->json([
                'title' => 'Proceso concluido',
                'message' => (count($request->permissions) > 1 ? 'Permisos generados ' : 'Permiso generado ') . 'correctamente'
            ]);
        } catch (Exception $e) {

            DB::rollBack();

            return response()->json([
                'title' => 'Ocurrio un error en el servidor',
                'message' => $e->getMessage() . ' -L:' . $e->getLine(),
                'code' => $this->prefixCode . '299'
            ], 500);
        }
    }

    public function storeRoles(Request $request)
    {
        app()->make(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        try {
            // Se validan los parámetros de entrada
            $validator = Validator::make(request()->all(), [
                'user_id' => 'Required|Integer|Min:1',
                'roles' => 'Required|Array',
                'roles.*' => 'Required|String|Distinct',
            ]);

            // Si la validación detecta un error regresa la descripción del error
            if ($validator->fails())
                return response()->json([
                    'title' => 'Datos Faltantes',
                    'message' => $validator->messages()->first(),
                    'code' => $this->prefixCode . 'X301'
                ], 400);

            // Se valida que el usuario este vigente
            $user = UserService::checkUser(request('user_id'));

            if (!$user)
                return response()->json([
                    'title' => 'Fallo en la consulta',
                    'message' => 'Usuario no encontrado.',
                    'code' => $this->prefixCode . 'X302'
                ], 400);

            // Se verifican que el usuario tenga permiso para crear nuevos permisos
            if (!$user->hasPermissionTo('create_roles'))
                return response()->json([
                    'title' => 'Fallo en el proceso',
                    'message' => 'Usuario no tiene permiso.',
                    'code' => $this->prefixCode . 'X303'
                ], 400);

            DB::beginTransaction();

            // Por cada rol enviado se almacena en BD, si el rol ya existe regresara un error
            foreach ($request->roles as $role)
                Role::create(['name' => $role]);

            DB::commit();

            return response()->json([
                'title' => 'Proceso concluido',
                'message' => (count($request->roles) > 1 ? 'Roles generados ' : 'Rol generado ') . 'correctamente'
            ]);
        } catch (Exception $e) {

            DB::rollBack();

            return response()->json([
                'title' => 'Ocurrio un error en el servidor',
                'message' => $e->getMessage() . ' -L:' . $e->getLine(),
                'code' => $this->prefixCode . '399'
            ], 500);
        }
    }

    public function assignPermissions(Request $request)
    {
        app()->make(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        try {

            // Se validan los parámetros de entrada
            $validator = Validator::make(request()->all(), [
                'user_id' => 'Required|Integer|Min:1',
                'permissions' => 'Required|Array',
                'permissions.*' => 'Required|Distinct',
            ]);

            // Si la validación detecta un error regresa la descripción del error
            if ($validator->fails())
                return response()->json([
                    'title' => 'Datos Faltantes',
                    'message' => $validator->messages()->first(),
                    'code' => $this->prefixCode . 'X401'
                ], 400);

            // Se valida que el usuario este vigente
            $user = UserService::checkUser(request('user_id'));

            if (!$user)
                return response()->json([
                    'title' => 'Fallo en la consulta',
                    'message' => 'Usuario no encontrado.',
                    'code' => $this->prefixCode . 'X402'
                ], 400);

            // Se verifican que el usuario tenga permiso para crear nuevos permisos
            if (!$user->hasPermissionTo('assign_permissions'))
                return response()->json([
                    'title' => 'Fallo en el proceso',
                    'message' => 'Usuario no tiene permiso.',
                    'code' => $this->prefixCode . 'X403'
                ], 400);

            DB::beginTransaction();

            // Se consulta el rol, si lo encuentra se asignan los permisos, si algún permiso no es encontrado devolverá un error.
            $role = Role::find($request->role_id);

            if ($role)
                $role->givePermissionTo($request->permissions);

            // Se consulta el usuario objetivo, si lo encuentra se asignan los permisos, si algún permiso no es encontrado devolverá un error.
            $user_target = User::find($request->user);

            if ($user_target)
                $user_target->givePermissionTo($request->permissions);

            DB::commit();

            return response()->json([
                'title' => 'Proceso concluido',
                'message' => (count($request->permissions) > 1 ? 'Permisos asignados ' : 'Permiso asignado ') . 'correctamente'
            ]);
        } catch (Exception $e) {

            DB::rollBack();

            return response()->json([
                'title' => 'Ocurrio un error en el servidor',
                'message' => $e->getMessage() . ' -L:' . $e->getLine(),
                'code' => $this->prefixCode . '499'
            ], 500);
        }
    }

    public function assignRoles(Request $request)
    {
        app()->make(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        try {

            // Se validan los parámetros de entrada
            $validator = Validator::make(request()->all(), [
                'user_id' => 'Required|Integer|Min:1',
                'roles' => 'Required|Array',
                'roles.*' => 'Required|Distinct',
            ]);

            // Si la validación detecta un error regresa la descripción del error
            if ($validator->fails())
                return response()->json([
                    'title' => 'Datos Faltantes',
                    'message' => $validator->messages()->first(),
                    'code' => $this->prefixCode . 'X501'
                ], 400);

            // Se valida que el usuario este vigente
            $user = UserService::checkUser(request('user_id'));

            if (!$user)
                return response()->json([
                    'title' => 'Fallo en la consulta',
                    'message' => 'Usuario no encontrado.',
                    'code' => $this->prefixCode . 'X502'
                ], 400);

            // Se verifican que el usuario tenga permiso para crear nuevos permisos
            if (!$user->hasPermissionTo('assign_roles'))
                return response()->json([
                    'title' => 'Fallo en el proceso',
                    'message' => 'Usuario no tiene permiso.',
                    'code' => $this->prefixCode . 'X503'
                ], 400);

            DB::beginTransaction();

            // Se valida que el usuario objetivo exista.
            $user_target = User::find($request->user);

            if (!$user_target)
                return response()->json([
                    'title' => 'Fallo en la consulta',
                    'message' => 'Usuario seleccionado no encontrado.',
                    'code' => $this->prefixCode . 'X504'
                ], 400);

            $user_target->assignRole($request->roles);

            DB::commit();

            return response()->json([
                'title' => 'Proceso concluido',
                'message' => (count($request->roles) > 1 ? 'Roles asignados ' : 'Rol asignado ') . 'correctamente'
            ]);
        } catch (Exception $e) {

            DB::rollBack();

            return response()->json([
                'title' => 'Ocurrio un error en el servidor',
                'message' => $e->getMessage() . ' -L:' . $e->getLine(),
                'code' => $this->prefixCode . '599'
            ], 500);
        }
    }
}
