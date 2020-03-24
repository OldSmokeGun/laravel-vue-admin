<?php

namespace App\Models\Auth;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Kalnoy\Nestedset\NodeTrait;
use oldSmokeGun\Jwt\Exception;

/**
 * App\Models\Auth\Permission
 *
 * @property int $id
 * @property string $identification 权限唯一标识
 * @property string $title 权限名称
 * @property string $icon 权限图标
 * @property string $component 权限组件
 * @property string $redirect 重定向标识
 * @property string $description 权限描述
 * @property int $type 权限类型（ 0 表示路由，1 表示按钮或其他 ）
 * @property int|null $parent_id 上级权限 id
 * @property int $sort 排序（ 只对路由类型有效 ）
 * @property int $lft 左值
 * @property int $rgt 右值
 * @property int $status 是否可用（ 1 是 0 否 ）
 * @property \Illuminate\Support\Carbon $create_time
 * @property \Illuminate\Support\Carbon $update_time
 * @property-read \Kalnoy\Nestedset\Collection|\App\Models\Auth\Permission[] $children
 * @property-read int|null $children_count
 * @property-read \App\Models\Auth\Permission|null $parent
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Auth\Permission d()
 * @method static \Kalnoy\Nestedset\QueryBuilder|\App\Models\Auth\Permission newModelQuery()
 * @method static \Kalnoy\Nestedset\QueryBuilder|\App\Models\Auth\Permission newQuery()
 * @method static \Kalnoy\Nestedset\QueryBuilder|\App\Models\Auth\Permission query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Auth\Permission whereComponent($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Auth\Permission whereCreateTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Auth\Permission whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Auth\Permission whereIcon($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Auth\Permission whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Auth\Permission whereIdentification($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Auth\Permission whereLft($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Auth\Permission whereParentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Auth\Permission whereRedirect($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Auth\Permission whereRgt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Auth\Permission whereSort($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Auth\Permission whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Auth\Permission whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Auth\Permission whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Auth\Permission whereUpdateTime($value)
 * @mixin \Eloquent
 */
class Permission extends Model
{
    use NodeTrait;

    protected $table = 'permissions';
    protected $dateFormat = 'U';

    const CREATED_AT = 'create_time';
    const UPDATED_AT = 'update_time';

    public function getLftName()
    {
        return 'lft';
    }

    public function getRgtName()
    {
        return 'rgt';
    }

    public function getParentIdName()
    {
        return 'parent_id';
    }

    public function getCreateTimeAttribute($value)
    {
        return date('Y-m-d H:i:s', $value);
    }

    public function getUpdateTimeAttribute($value)
    {
        return date('Y-m-d H:i:s', $value);
    }

    /**
     * @param Builder $query
     * @param string $order
     *
     * @return mixed
     */
    public function scopeSort($query)
    {
        return $query->orderBy('sort')->orderBy('identification');
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

    public function getPermissonList(array $search): ?array
    {
        $type         = $search['type'];
        $status       = $search['status'];
        $searchType   = $search['type'] === '' ? false : true;
        $searchStatus = $search['status'] === '' ? false : true;

        $permissions = $this
            ->when($search['identification'], function ($query, $identification) {
                $query->where('identification', 'like', "%{$identification}%");
            })
            ->when($search['title'], function ($query, $title) {
                $query->where('title', 'like', "%{$title}%");
            })
            ->when($searchType, function ($query) use ($type) {
                $query->where(['type' => $type]);
            })
            ->when($searchStatus, function ($query) use ($status) {
                $query->where(['status' => $status]);
            })
            ->sort()
            ->get()
            ->toTree()
            ->toArray();

        return $permissions;
    }

    public function createPermission(array $permission): bool
    {
        $this->identification = $permission['identification'];
        $this->title          = $permission['title'];
        $this->icon           = $permission['icon'];
        $this->component      = $permission['component'];
        $this->redirect       = $permission['redirect'];
        $this->description    = $permission['description'];
        $this->type           = $permission['type'];
        $this->sort           = $permission['sort'];
        $this->status         = $permission['status'];
        $this->display        = $permission['display'];

        DB::beginTransaction();

        if ( $permission['parent_id'] )
        {
            try {
                $parent = $this->find($permission['parent_id']);

                if ( !$parent->appendNode($this) ) throw new \Exception();

                DB::commit();
                return true;

            } catch (\Exception $exception) {
                DB::rollBack();
                return false;
            }
        }

        try
        {
            if ( !$this->saveAsRoot() ) throw new \Exception();

            DB::commit();
            return true;

        } catch (\Exception $exception) {

            DB::rollBack();
            return false;
        }
    }

    public function updatePermission(array $permission): bool
    {
        DB::beginTransaction();

        try {

            $model = $this->find($permission['id']);

            $model->identification = $permission['identification'];
            $model->title          = $permission['title'];
            $model->icon           = $permission['icon'];
            $model->component      = $permission['component'];
            $model->redirect       = $permission['redirect'];
            $model->description    = $permission['description'];
            $model->type           = $permission['type'];
            $model->parent_id      = $permission['parent_id'];
            $model->sort           = $permission['sort'];
            $model->status         = $permission['status'];
            $model->display         = $permission['display'];

            if ( !$model->save() ) throw new \Exception();

            DB::commit();
            return true;

        } catch (\Exception $exception) {

            DB::rollBack();
            return false;
        }

    }

    public function deletePermission(int $id): bool
    {
        DB::beginTransaction();

        try
        {
            RolePermission::where(['permission_id' => $id])->delete();
            $result = $this->destroy($id);

            if ( !$result ) throw new \Exception();

            DB::commit();
            return true;

        } catch (\Exception $exception) {

            DB::rollBack();
            return false;
        }
    }

    public function editPermission(array $fields): bool
    {
        $model = $this->find($fields['id']);

        unset($fields['id']);

        foreach ( $fields as $fieldKey => $fieldValue )
        {
            $model->$fieldKey = $fieldValue;
        }

        return $model->save();
    }

    public function getPermissionTrees(int $type = null): array
    {
        $searchType = $type === null ? false : true;

        $trees = $this
            ->select([
                'id',
                'identification',
                'title',
                'description',
                'parent_id',
                'lft',
                'rgt'
            ])
            ->when($searchType, function ($query) use ($type) {
                $query->where(['type' => $type]);
            })
            ->sort()
            ->get()
            ->toTree()
            ->toArray();

        $recursive = function ( $children ) use ( &$recursive ) {

            if ( !$children )
            {
                return $children;
            }

            foreach ( $children as &$posterity )
            {
                $posterity['tree_title'] = $posterity['title'] ? $posterity['title'] . ' - ' . $posterity['identification'] : $posterity['identification'];
                $posterity['children']   = $recursive($posterity['children']);
            }

            return $children;
        };

        foreach ( $trees as &$tree )
        {
            $tree['tree_title'] = $tree['title'] ? $tree['title'] . ' - ' . $tree['identification'] : $tree['identification'];
            $tree['children']   = $recursive($tree['children']);
        }

        return $trees;
    }
}
