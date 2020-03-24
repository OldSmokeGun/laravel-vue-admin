<?php

namespace App\Models\Auth;

use App\Models\Auth\Permission as PermissionModel;
use App\Models\Auth\Role as RoleModel;
use App\Models\Auth\RolePermission as RolePermissionModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * App\Models\Auth\Admin
 *
 * @property int $id
 * @property string $username 用户名
 * @property string $password 用户密码
 * @property string $avatar 用户头像
 * @property string $email email
 * @property string $token 用户 token
 * @property int $status 是否可用（ 1 是 0 否 ）
 * @property int $last_login_time 最后登陆时间
 * @property string $last_login_ip 最后登陆 IP
 * @property \Illuminate\Support\Carbon $create_time
 * @property \Illuminate\Support\Carbon $update_time
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Auth\Admin newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Auth\Admin newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Auth\Admin query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Auth\Admin whereAvatar($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Auth\Admin whereCreateTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Auth\Admin whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Auth\Admin whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Auth\Admin whereLastLoginIp($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Auth\Admin whereLastLoginTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Auth\Admin wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Auth\Admin whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Auth\Admin whereToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Auth\Admin whereUpdateTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Auth\Admin whereUsername($value)
 * @mixin \Eloquent
 * @property-read mixed $last_login_date
 */
class Admin extends Model
{
    protected $table = 'admins';
    protected $dateFormat = 'U';

    const CREATED_AT = 'create_time';
    const UPDATED_AT = 'update_time';

    public function getLastLoginTimeAttribute($value)
    {
        return date('Y-m-d H:i:s', $value);
    }

    public function getCreateTimeAttribute($value)
    {
        return date('Y-m-d H:i:s', $value);
    }

    public function getUpdateTimeAttribute($value)
    {
        return date('Y-m-d H:i:s', $value);
    }

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'admins_roles', 'admin_id', 'role_id');
    }

    /**
     * @param Builder $query
     * @param string $order
     *
     * @return mixed
     */
    public function scopeIdSort($query, $order = 'asc')
    {
        return $query->orderBy('id', $order);
    }

    /**
     * @param Builder $query
     *
     * @return mixed
     */
    public function scopeEnable($query)
    {
        return $query->where(['status' => 1]);
    }

    public function getAdminList(array $search): ?array
    {
        $status       = $search['status'];
        $searchStatus = $search['status'] === '' ? false : true;

        $admins = $this
            ->select([
                'id',
                'username',
                'nickname',
                'avatar',
                'email',
                'status',
                'last_login_time',
                'last_login_ip',
                'create_time',
                'update_time'
            ])
            ->when($search['username'], function ($query, $username) {
                $query->where('username', 'like', "%{$username}%");
            })
            ->when($search['nickname'], function ($query, $username) {
                $query->where('nickname', 'like', "%{$username}%");
            })
            ->when($search['email'], function ($query, $email) {
                $query->where('email', 'like', "%{$email}%");
            })
            ->when($searchStatus, function ($query) use ($status) {
                $query->where(['status' => $status]);
            })
            ->idSort()
            ->paginate($search['limit']);

        foreach ( $admins as $admin )
        {
            $admin->roles = $admin->roles()->select(['roles.id','name'])->get()->toArray();
        }

        return $admins->toArray();
    }

    public function getAdminPermissionsByToken(string $token, array $fields): ?array
    {
        $admin = $this->select($fields)->where(['token' => $token])->first();

        if ( !$admin ) return [];

        $permissionModel = new PermissionModel();
        $permissionTable = $permissionModel->getTable();

        if ( (int) $admin->id === 1 )
        {
            $permissionQuery = $permissionModel
                ->select([
                    "{$permissionTable}.id",
                    "{$permissionTable}.identification",
                    "{$permissionTable}.title",
                    "{$permissionTable}.icon",
                    "{$permissionTable}.component",
                    "{$permissionTable}.redirect",
                    "{$permissionTable}.parent_id",
                    "{$permissionTable}.lft",
                    "{$permissionTable}.rgt",
                    "{$permissionTable}.display",
                ])
                ->sort();

            $otherPermissionQuery = clone $permissionQuery;

            $routePermissions = $permissionQuery->where("{$permissionTable}.type", '=', 0)->get()->toTree()->toArray();
            $otherPermissions = $otherPermissionQuery->get()->toArray();

            $admin->permission_maps = [
                'routes' => $routePermissions,
                'maps' => $otherPermissions
            ];

            return $admin->toArray();
        }

        // 查询权限表
        $roleModel = new RoleModel();
        $roleTable = $roleModel->getTable();

        $roles = $admin->roles()->select(["{$roleTable}.id"])->where("{$roleTable}.status", "=", 1)->get()->toArray();

        $rolePermissionModel      = new RolePermissionModel();
        $rolePermissionModelTable = $rolePermissionModel->getTable();

        $permissionQuery = $permissionModel
            ->select([
                "{$permissionTable}.id",
                "{$permissionTable}.identification",
                "{$permissionTable}.title",
                "{$permissionTable}.icon",
                "{$permissionTable}.component",
                "{$permissionTable}.redirect",
                "{$permissionTable}.parent_id",
                "{$permissionTable}.lft",
                "{$permissionTable}.rgt",
                "{$permissionTable}.display",
            ])
            ->join(
                $rolePermissionModelTable,
                "{$permissionTable}.id",
                '=',
                "{$rolePermissionModelTable}.permission_id"
            )
            ->enable()
            ->sort()
            ->whereIn("{$rolePermissionModelTable}.role_id", array_column($roles, 'id'))
            ->groupBy("{$permissionTable}.identification");

        $otherPermissionQuery = clone $permissionQuery;

        $routePermissions = $permissionQuery->where("{$permissionTable}.type", '=', 0)->get()->toTree()->toArray();
        $otherPermissions = $otherPermissionQuery->get()->toArray();

        $admin->permission_maps = [
            'routes' => $routePermissions,
            'maps' => $otherPermissions
        ];

        return $admin->toArray();
    }

    public function hasAdminPermissionByToken(string $token, string $permission): bool
    {
        $admin = $this->where(['token' => $token])->first();

        $roleModel                = new RoleModel();
        $permissionModel          = new PermissionModel();
        $rolePermissionModel      = new RolePermissionModel();
        $roleTable                = $roleModel->getTable();
        $permissionTable          = $permissionModel->getTable();
        $rolePermissionModelTable = $rolePermissionModel->getTable();

        $roles = $admin->roles()->select(["{$roleTable}.id"])->where("{$roleTable}.status", "=", 1)->get()->toArray();

        $result = $permissionModel
            ->join(
                $rolePermissionModelTable,
                "{$permissionTable}.id",
                '=',
                "{$rolePermissionModelTable}.permission_id"
            )
            ->enable()
            ->sort()
            ->whereIn("{$rolePermissionModelTable}.role_id", array_column($roles, 'id'))
            ->where("{$permissionTable}.identification", '=', $permission)
            ->first();

        return $result ? true : false;
    }

    public function createAdmin(array $admin): bool
    {
        DB::beginTransaction();

        try
        {
            $this->username = $admin['username'];
            $this->nickname = $admin['nickname'];
            $this->password = Hash::make($admin['password']);
            $this->avatar   = $admin['avatar'];
            $this->email    = $admin['email'];
            $this->token    = '';
            $this->status   = $admin['status'];

            if ( !$this->save() ) throw new \Exception();

            if ( $admin['roles'] )
            {
                $adminRoles = [];

                foreach ( $admin['roles'] as $role )
                {
                    $adminRoles[] = ['admin_id' => $this->id, 'role_id' => $role, 'create_time' => time()];
                }

                if ( !AdminRole::insert($adminRoles) ) throw new \Exception();
            }

            DB::commit();
            return true;

        } catch ( \Exception $exception ) {

            DB::rollBack();
            return false;
        }
    }

    public function updateAdmin(array $admin): bool
    {
        $model = $this->find($admin['id']);

        DB::beginTransaction();

        try
        {
            $model->username = $admin['username'];
            $model->nickname = $admin['nickname'];
            $model->avatar   = $admin['avatar'];
            $model->email    = $admin['email'];
            $model->status   = $admin['status'];

            $admin['password'] && $model->password = Hash::make($admin['password']);

            if ( !$model->save() ) throw new \Exception();

            AdminRole::where(['admin_id' => $admin['id']])->delete();

            if ( $admin['roles'] )
            {
                $adminRoles = [];

                foreach ( $admin['roles'] as $role )
                {
                    $adminRoles[] = ['admin_id' => $admin['id'], 'role_id' => $role, 'update_time' => time()];
                }

                if ( !AdminRole::insert($adminRoles)) throw new \Exception();
            }

            DB::commit();
            return true;

        } catch ( \Exception $exception ) {

            DB::rollBack();
            return false;
        }
    }

    public function deleteAdmin(int $id): bool
    {
        DB::beginTransaction();

        try
        {
            AdminRole::where(['admin_id' => $id])->delete();
            $adminResult = $this->destroy($id);

            if ( !$adminResult ) throw new \Exception();

            DB::commit();
            return true;

        } catch ( \Exception $exception ) {

            DB::rollBack();
            return false;
        }

    }

    public function editAdmin(array $fields): bool
    {
        $model = $this->find($fields['id']);

        unset($fields['id']);

        foreach ( $fields as $fieldKey => $fieldValue )
        {
            $model->$fieldKey = $fieldValue;
        }

        return  $model->save();
    }

    public function getRoles():? array
    {
        return Role::idSort()->get()->toArray();
    }

    public function findByName(string $username): ?Model
    {
        return $this->where(['username' => $username])->first();
    }

    public function findByToken(string $token): ?Model
    {
        return $this->where(['token' => $token])->first();
    }

    public function setLastLogin(Admin $admin, array $lostLoginInfo): bool
    {
        $admin->last_login_time = $lostLoginInfo['last_login_time'];
        $admin->last_login_ip   = $lostLoginInfo['last_login_ip'];
        return $admin->save();
    }

    public function setToken(Admin $admin, string $token): bool
    {
        $admin->token = $token;
        return $admin->save();
    }

    public function removeToken(Admin $admin): bool
    {
        $admin->token = '';
        return $admin->save();
    }

}
