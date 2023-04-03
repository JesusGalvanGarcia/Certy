<?php

namespace App\Http\Controllers;

use App\Mail\RecoveryToken;
use App\Models\Client;
use App\Models\RecoveryToken as ModelsRecoveryToken;
use App\Services\UserService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class LoginController extends Controller
{
    private $prefixCode = 'Login';

    public function login(Request $request)
    {
        try {

            $validator = Validator::make($request->all(), [
                'email' => 'Required|Email',
                'password' => 'Required|String'
            ]);

            if ($validator->fails()) {

                return response()->json([
                    'title' => 'Datos Faltantes',
                    'message' => $validator->messages()->first(),
                    'code' => $this->prefixCode . 'X001'
                ], 400);
            }

            // Consulta de usuario en base a authentication
            $user = Client::where([
                ['email', $request->email]
            ])
                ->whereIn('status_id', [1, 3])
                ->first();

            if (!$user) {

                return response()->json([
                    'title' => 'Error de Validación',
                    'message' => 'Usuario o contraseña incorrecto, intente de nuevo.',
                    'code' => $this->prefixCode . 'X002'
                ], 400);
            }

            // Consulta de permisos de la plataforma
            // $permissions = $user->hasAnyPermission([
            //     29,
            //     30,
            //     31,
            //     32
            // ]);

            // if (!$permissions) {
            //     return response()->json([
            //         'message' => 'No estas autorizado para entrar a esta plataforma'
            //     ], 404);
            // }

            //Consulta del internal_user en base a modelo
            if (!$user->roles->first()) {

                UserService::log($user->id, $this->prefixCode . 'Y001', 'Intento fallido de Login. Usuario no autorizado para ingresar.', 2);

                return response()->json([
                    'title' => 'Acceso denegado',
                    'message' => 'No estas autorizado para entrar a esta plataforma',
                    'code' => $this->prefixCode . 'X003'
                ], 404);
            }

            $log_Data = [
                'user_id' => $user->id,
                'user_name' => $user->complete_name,
                'user_email' => $user->email,
                'role' => $user->roles->first()->id
            ];

            $log_on = Request::create(
                '/oauth/token',
                'POST',
                [
                    'grant_type' => 'password',
                    'client_id' => 4,
                    'client_secret' => 'ArRFhdEGQo0kSFADhNDmWYVNFGP9kYJbQTsOu4e3',
                    'username' => $request->email,
                    'password' => $request->password,
                    'scope' => '',
                ]
            );

            $response = app()->handle($log_on);

            $content = json_decode($response->getContent(), true);

            if (array_key_exists('error', $content)) {

                UserService::log($user->id, $this->prefixCode . 'Y002', 'Intento fallido de Login. Usuario o contraseña incorrecto.', 2);

                return response()->json([
                    'title' => 'Error de Validación',
                    'message' => 'Usuario o contraseña incorrecto, intente de nuevo.',
                    'code' => $this->prefixCode . 'X004'
                ], 400);
            }

            UserService::log($user->id, $this->prefixCode . 'Y003', 'Ingreso a la plataforma.', 1);

            return response()->json([
                'title' => 'Login Correcto',
                'message' => 'Bienvenido',
                'data' => $log_Data,
                'token' => $content['access_token'],
                'expiresIn' => $content['expires_in']
            ]);
        } catch (Exception $e) {

            return response()->json([
                'title' => 'Ocurrio un error en el servidor',
                'message' => $e->getMessage() . ' -L:' . $e->getLine(),
                'code' => $this->prefixCode . 'X099'
            ], 500);
        }
    }

    public function register(Request $request)
    {
        try {

            $validator = Validator::make($request->all(), [
                'complete_name' => 'Required|String',
                'email' => 'Required|Email',
                'password' => 'Required|String|Min:5'
            ]);

            if ($validator->fails()) {

                return response()->json([
                    'title' => 'Datos Faltantes',
                    'message' => $validator->messages()->first(),
                    'code' => $this->prefixCode . 'X101'
                ], 400);
            }

            // Consulta que al usuario
            $user = Client::where([['email', $request->email]])->whereIn('status_id', [1, 3])->first();

            DB::beginTransaction();

            // Se evalúa si el usuario existe
            if ($user) {

                // Evalúa si el usuario cuenta con contraseña, si cuenta con contraseña se creo desde la pagina de registro y el usuario ya existe.
                if ($user->password)
                    return response()->json([
                        'title' => 'Correo en Uso',
                        'message' => 'Esté correo ya se encuentra en uso.',
                        'code' => $this->prefixCode . 'X102'
                    ], 400);


                    // Caso contrario se asigna la contraseña al usuario y se crea desde el proceso de cotización.
                $user->update([
                    'password' => Hash::make($request->password)
                ]);
            } else {

                //Si no existe se crea al usuario
                $user = Client::create([
                    'complete_name' => $request->complete_name,
                    'email' => $request->email,
                    'password' => Hash::make($request->password),
                    'status_id' => 1
                ]);

                $user->assignRole(2);
            }

            $log_Data = [
                'user_id' => $user->id,
                'email' => $user->email,
                'user_name' => $user->complete_name,
                'role' => 2
            ];

            $log_on = Request::create(
                '/oauth/token',
                'POST',
                [
                    'grant_type' => 'password',
                    'client_id' => 4,
                    'client_secret' => 'ArRFhdEGQo0kSFADhNDmWYVNFGP9kYJbQTsOu4e3',
                    'username' => $request->email,
                    'password' => $request->password,
                    'scope' => '',
                ]
            );

            $response = app()->handle($log_on);

            $content = json_decode($response->getContent(), true);

            if (array_key_exists('error', $content)) {

                DB::rollBack();

                return response()->json([
                    'title' => 'Error de Validación',
                    'message' => 'Usuario o contraseña incorrecto, intente de nuevo.',
                    'code' => $this->prefixCode . 'X103'
                ], 400);
            }

            DB::commit();

            return response()->json([
                'title' => 'Login Correcto',
                'message' => 'Bienvenido',
                'data' => $log_Data,
                'token' => $content['access_token'],
                'expiresIn' => $content['expires_in']
            ]);
        } catch (Exception $e) {

            DB::rollBack();

            return response()->json([
                'title' => 'Ocurrio un error en el servidor',
                'message' => $e->getMessage() . ' -L:' . $e->getLine(),
                'code' => $this->prefixCode . 'X199'
            ], 500);
        }
    }

    public function sendSecureCode(Request $request)
    {

        try {

            $validator = Validator::make($request->all(), [
                'email' => 'Required|Email'
            ]);

            if ($validator->fails()) {

                return response()->json([
                    'title' => 'Datos Faltantes',
                    'message' => $validator->messages()->first(),
                    'code' => $this->prefixCode . 'X201'
                ], 400);
            }

            $user = Client::where('email', $request->email)->first();

            if (!$user)
                return response()->json([
                    'title' => 'Usuario no encontrado',
                    'message' => 'Verifique el correo electrónico',
                    'code' => $this->prefixCode . 'X202'
                ], 402);

            $actual_token = ModelsRecoveryToken::where([['status_id', 1], ['user_id', $user->id]])->first();

            DB::beginTransaction();

            if ($actual_token) {

                if (Carbon::parse($actual_token->created_at)->diffInMinutes(Carbon::now()) > 5) {

                    $token = random_int(100000, 999999);

                    $actual_token->update([
                        'status_id' => 2
                    ]);

                    $actual_token->delete();

                    ModelsRecoveryToken::create([
                        'user_id' => $user->id,
                        'token' => $token,
                        'email' => $request->email,
                        'status_id' => 1
                    ]);
                } else {

                    $token = $actual_token->token;
                }
            } else {

                $token = random_int(100000, 999999);

                ModelsRecoveryToken::create([
                    'user_id' => $user->id,
                    'token' => $token,
                    'email' => $request->email,
                    'status_id' => 1
                ]);
            }

            $mail = Mail::to($request->email)->send(new RecoveryToken($token, $user));

            if (!$mail)
                return response()->json([
                    'title' => 'Error al enviar correo',
                    'message' => $mail,
                    'code' => $this->prefixCode . 'X203'
                ], 400);

            DB::commit();

            UserService::log($user->id, $this->prefixCode . 'Y201', 'Solicitud de código para cambiar contraseña.', 1);

            return response()->json([
                'title' => 'Proceso terminado',
                'message' => 'Código enviado correctamente'
            ]);
        } catch (Exception $e) {

            DB::rollBack();

            return response()->json([
                'title' => 'Ocurrio un error en el servidor',
                'message' => $e->getMessage() . ' -L:' . $e->getLine(),
                'code' => $this->prefixCode . 'X299'
            ], 500);
        }
    }

    public function actualizePassword(Request $request)
    {

        try {

            $validator = Validator::make($request->all(), [
                'email' => 'Required|Email',
                'secure_code' => 'Required|String',
                'password' => 'Required|String',
            ]);

            if ($validator->fails()) {

                return response()->json([
                    'title' => 'Datos Faltantes',
                    'message' => $validator->messages()->first(),
                    'code' => $this->prefixCode . 'X301'
                ], 400);
            }

            $user = Client::where('email', $request->email)->first();

            if (!$user)
                return response()->json([
                    'title' => 'Usuario no encontrado',
                    'message' => 'Verifique el correo electrónico',
                    'code' => $this->prefixCode . 'X302'
                ], 402);

            $actual_token = ModelsRecoveryToken::where([['user_id', $user->id], ['token', $request->secure_code], ['status_id', 1]])->first();

            if (!$actual_token) {

                UserService::log($user->id, $this->prefixCode . 'Y301', 'Intento fallido de actualización de contraseña. Código de seguridad invalido.', 2);

                return response()->json([
                    'title' => 'Código de Seguridad Invalido',
                    'message' => 'Verifique el código de seguridad por favor. Si el problema persiste, vuelva a enviar el código por correo.',
                    'code' => $this->prefixCode . 'X303'
                ], 402);
            }
            DB::beginTransaction();

            if (Carbon::parse($actual_token->created_at)->diffInMinutes(Carbon::now()) > 5) {

                $actual_token->update([
                    'status_id' => 2
                ]);

                $actual_token->delete();

                UserService::log($user->id, $this->prefixCode . 'Y302', 'Intento fallido de actualización de contraseña. El código de seguridad ya no se encontraba vigente.', 2);

                DB::commit();

                return response()->json([
                    'title' => 'Código de Seguridad Invalido',
                    'message' => 'Esté código de seguridad ya no se encuentra vigente.',
                    'code' => $this->prefixCode . 'X304'
                ], 402);
            }

            $user->update([
                'password' => Hash::make($request->password)
            ]);

            $actual_token->update([
                'status_id' => 2
            ]);

            $actual_token->delete();

            DB::commit();

            UserService::log($user->id, $this->prefixCode . 'Y303', 'Contraseña Actualizada.', 1);

            //Consulta del internal_user en base a modelo
            if (!$user->roles->first()) {

                UserService::log($user->id, $this->prefixCode . 'Y001', 'Intento fallido de Login. Usuario no autorizado para ingresar.', 2);

                return response()->json([
                    'title' => 'Acceso denegado',
                    'message' => 'No estas autorizado para entrar a esta plataforma',
                    'code' => $this->prefixCode . 'X003'
                ], 404);
            }

            $log_Data = [
                'user_id' => $user->id,
                'user_name' => $user->complete_name,
                'user_email' => $user->email,
                'role' => $user->roles->first()->id
            ];

            $log_on = Request::create(
                '/oauth/token',
                'POST',
                [
                    'grant_type' => 'password',
                    'client_id' => 4,
                    'client_secret' => 'ArRFhdEGQo0kSFADhNDmWYVNFGP9kYJbQTsOu4e3',
                    'username' => $request->email,
                    'password' => $request->password,
                    'scope' => '',
                ]
            );

            $response = app()->handle($log_on);

            $content = json_decode($response->getContent(), true);

            if (array_key_exists('error', $content)) {

                UserService::log($user->id, $this->prefixCode . 'Y002', 'Intento fallido de Login. Usuario o contraseña incorrecto.', 2);

                return response()->json([
                    'title' => 'Error de Validación',
                    'message' => 'Usuario o contraseña incorrecto, intente de nuevo.',
                    'code' => $this->prefixCode . 'X004'
                ], 400);
            }

            UserService::log($user->id, $this->prefixCode . 'Y003', 'Ingreso a la plataforma.', 1);

            return response()->json([
                'title' => 'Login Correcto',
                'message' => 'Contraseña actualizada correctamente',
                'data' => $log_Data,
                'token' => $content['access_token'],
                'expiresIn' => $content['expires_in']
            ]);
        } catch (Exception $e) {

            DB::rollBack();

            return response()->json([
                'title' => 'Ocurrio un error en el servidor',
                'message' => $e->getMessage() . ' -L:' . $e->getLine(),
                'code' => $this->prefixCode . 'X399'
            ], 500);
        }
    }
}
