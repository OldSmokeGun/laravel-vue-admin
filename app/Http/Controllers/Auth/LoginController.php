<?php

namespace App\Http\Controllers\Auth;

use App\Facades\HttpResponse;
use App\Facades\Jwt;
use App\Http\Controllers\Controller;
use App\Models\Auth\Admin as AdminModel;
use App\Models\Auth\Permission as PermissionModel;
use App\Models\Auth\Role as RoleModel;
use App\Models\Auth\RolePermission as RolePermissionModel;
use App\Utils\HttpResponse\HttpResponseCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class LoginController extends Controller
{
    protected function loginValidator(Request $request): \Illuminate\Contracts\Validation\Validator
    {
        return Validator::make($request->all(), [
            'username' => 'required|max:8',
            'password' => 'required|max:16',
        ], [
            'username.required' => '用户名必须',
            'username.max'      => '用户名不能超过 8 个字符',
            'password.required' => '密码必须',
            'password.max'      => '密码不能超过 16 个字符',
        ]);
    }

    /**
     * 管理员登陆
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        $validator = $this->loginValidator($request);

        if ( $validator->fails() )
        {
            return HttpResponse::failedResponse($validator->errors()->first());
        }

        ['username' => $username, 'password' => $password] = request()->post();

        $adminModel = new AdminModel();

        $admin = $adminModel->findByName($username);

        if ( !$admin )
        {
            return HttpResponse::failedResponse('用户名或密码错误');
        }

        if ( !Hash::check($password, $admin->password) )
        {
            return HttpResponse::failedResponse('用户名或密码错误');
        }

        if ( !$admin->status )
        {
            return HttpResponse::failedResponse('账户已被禁用');
        }

        $lastLoginInfo = ['last_login_time' => time(), 'last_login_ip' => $request->ip()];

        if ( !$adminModel->setLastLogin($admin, $lastLoginInfo) )
        {
            return HttpResponse::failedResponse('登陆出错');
        }

        $token = Jwt::encode([
            'id'       => $admin->id,
            'username' => $admin->username,
            'email'    => $admin->email,
        ]);

        if ( !$adminModel->setToken($admin, $token) )
        {
            return HttpResponse::failedResponse('登陆出错');
        }

        $data = [
            'token' => $token
        ];

        return HttpResponse::successResponse($data);

    }

    /**
     * 管理员注销
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(Request $request)
    {
        $token = strval($request->input('token', ''));

        if ( !$token )
        {
            return HttpResponse::failedResponse(HttpResponseCode::ILLEGAL_REQUEST_CODE_MESSAGE, HttpResponseCode::ILLEGAL_REQUEST_CODE);
        }

        $adminModel = new AdminModel();
        $admin      = $adminModel->findByToken($token);

        if ( !$admin )
        {
            return HttpResponse::failedResponse(HttpResponseCode::LOGIN_INVALID_CODE_MESSAGE, HttpResponseCode::LOGIN_INVALID_CODE);
        }

        if ( !$adminModel->removeToken($admin) )
        {
            return HttpResponse::failedResponse('退出登陆失败');
        }

        return HttpResponse::successResponse();
    }
}
