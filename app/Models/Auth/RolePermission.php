<?php

namespace App\Models\Auth;

use Illuminate\Database\Eloquent\Model;

class RolePermission extends Model
{
    protected $table = 'roles_permissions';
    protected $dateFormat = 'U';

    const CREATED_AT = 'create_time';
    const UPDATED_AT = 'update_time';

    protected $fillable = ['role_id', 'permission_id', 'create_time', 'update_time'];

    public function getCreateTimeAttribute($value)
    {
        return date('Y-m-d H:i:s', $value);
    }

    public function getUpdateTimeAttribute($value)
    {
        return date('Y-m-d H:i:s', $value);
    }
}
