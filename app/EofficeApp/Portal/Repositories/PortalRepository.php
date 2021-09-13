<?php
namespace App\EofficeApp\Portal\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Portal\Entities\PortalEntity;
use App\EofficeApp\Portal\Entities\SystemParamsEntity;
use App\EofficeApp\Portal\Entities\PortalUserViewPurviewEntity;
use App\EofficeApp\Portal\Entities\PortalUserEditPurviewEntity;
use App\EofficeApp\Portal\Entities\PortalDeptViewPurviewEntity;
use App\EofficeApp\Portal\Entities\PortalRoleViewPurviewEntity;
use App\EofficeApp\Portal\Entities\PortalDeptEditPurviewEntity;
use App\EofficeApp\Portal\Entities\PortalRoleEditPurviewEntity;
use DB;
/**
 * 门户资源库类
 *
 * @author 李志军
 *
 * @since 2015-10-27
 */
class PortalRepository extends BaseRepository
{
	/**
	 * 注册门户实体对象
	 *
	 * @param \App\EofficeApp\Portal\Entities\PortalEntity $entity
	 *
	 * @author 李志军
	 *
	 * @since 2015-10-27
	 */
	public function __construct(PortalEntity $entity)
	{
		parent::__construct($entity);
	}
	/**
	 * 获取门户列表
	 *
	 * @param array $fields
	 *
	 * @return array 门户列表
	 *
	 * @author 李志军
	 *
	 * @since 2015-10-27
	 */
	public function listPortal($fields,$type = 'show', ...$conditions)
	{
		$fields = empty($fields) ? ['*'] : $fields;

		$query = $this->entity->select($fields);
		
		if(in_array($type,['show','menu'])) {
			$userId = $conditions[0];
		    $deptId = $conditions[1]; 
		    $roleId = $conditions[2];
			$query->where(function($query) use ($userId, $deptId, $roleId){
				$query->where('edit_priv_scope', 1)
				->orWhere(function($query) use ($userId, $deptId, $roleId){
					$query->whereHas('userEditPurview', function ($query) use($userId){
						$query->where('user_id', $userId);
					})->orWhereHas('deptEditPurview', function ($query) use($deptId){
						$query->where('dept_id', $deptId);
					})->orWhereHas('roleEditPurview', function ($query) use($roleId){
						$query->whereIn('role_id', $roleId);
					});
				})
				->orWhere('view_priv_scope',1)
				->orWhere(function($query) use ($userId, $deptId, $roleId){
					$query->whereHas('userViewPurview', function ($query) use($userId){
						$query->where('user_id', $userId);
					})->orWhereHas('deptViewPurview', function ($query) use($deptId){
						$query->where('dept_id', $deptId);
					})->orWhereHas('roleViewPurview', function ($query) use($roleId){
						$query->whereIn('role_id', $roleId);
					});
				});
			});
		}
		
		return $query->orderBy('portal_sort','asc')->get();
	}
	
	/**
	 * 获取有编辑权限的门户列表
	 *
	 * @return array 有编辑权限的门户列表
	 *
	 * @author 李志军
	 *
	 * @since 2015-10-27
	 */
	public function getEditPrivPortal($userId, $deptId, $roleId)
	{
            return $this->entity->select(['portal_id'])->where(function($query) use($userId, $deptId, $roleId) {
                    $query->where('edit_priv_scope', 1)
                            ->orWhere(function($query) use ($userId, $deptId, $roleId) {
                                $query->whereHas('userEditPurview', function ($query) use($userId) {
                                    $query->where('user_id', $userId);
                                })->orWhereHas('deptEditPurview', function ($query) use($deptId) {
                                    $query->where('dept_id', $deptId);
                                })->orWhereHas('roleEditPurview', function ($query) use($roleId) {
                                    $query->whereIn('role_id', $roleId);
                                });
                            });
                })->get();
    }
	/**
	 * 获取最大排序号
	 *
	 * @return int 最大排序号
	 *
	 * @author 李志军
	 *
	 * @since 2015-10-27
	 */
	public function getMaxSort()
	{
		return $this->entity->max('portal_sort');
	}
	/**
	 * 判断该门户是否有编辑权限
	 *
	 * @param int $portalId
	 *
	 * @return int
	 *
	 * @author 李志军
	 *
	 * @since 2015-10-27
	 */
	public function hasEditPrivOfPortal($portalId, $userId, $deptId, $roleId)
	{
		return $this->entity->where(function($query) use ($userId, $deptId, $roleId){
					$query->where('edit_priv_scope', 1)
						->orWhere(function($query) use ($userId, $deptId, $roleId){
							$query->whereHas('userEditPurview', function ($query) use($userId){
								$query->where('user_id', $userId);
							})->orWhereHas('deptEditPurview', function ($query) use($deptId){
								$query->where('dept_id', $deptId);
							})->orWhereHas('roleEditPurview', function ($query) use($roleId){
								$query->whereIn('role_id', $roleId);
							});
						});
				})->where('portal_id', $portalId)->count();
	}

	public function getPortalInfo($portalId)
	{
            $portal = $this->entity
                    ->with('userViewPurview')
                    ->with('deptViewPurview')
                    ->with('roleViewPurview')
                    ->with('userEditPurview')
                    ->with('deptEditPurview')
                    ->with('roleEditPurview')
                    ->where('portal_id',$portalId)
                    ->first();
            if(!$portal){
                return [];
            }
            
            return $portal->toArray();
	}
	/**
	 * 设置默认门户
	 *
	 * @param int $portalId
	 *
	 * @return boolean 设置默认门户结果
	 *
	 * @author 李志军
	 *
	 * @since 2015-10-27
	 */
	public function setDefaultPortal($portalId)
	{	
		if ($this->entity->where('portal_id','>',0)->update(['portal_default' => 0])) {
			if ($this->entity->where('portal_id', $portalId)->update(['portal_default' => 1])) {
				return true;
			}
		}

		return false;
	}
	public function setUserViewPurview($portalId, $userId)
	{
		PortalUserViewPurviewEntity::where('portal_id',$portalId)->delete();

		if (empty($userId)) {
			return true;
		}

		$data = array_map(function($value){
			return new PortalUserViewPurviewEntity(['user_id' => $value]);
		}, $userId);

		$portal = $this->entity->find($portalId);

		return $portal->userViewPurview()->saveMany($data);
	}

	public function setUserEditPurview($portalId, $userId)
	{
		PortalUserEditPurviewEntity::where('portal_id',$portalId)->delete();

		if (empty($userId)) {
			return true;
		}

		$data = array_map(function($value){
			return new PortalUserEditPurviewEntity(['user_id' => $value]);
		},$userId);

		$portal = $this->entity->find($portalId);

		return $portal->userEditPurview()->saveMany($data);
	}

	public function setDeptViewPurview($portalId, $deptId)
	{
		PortalDeptViewPurviewEntity::where('portal_id',$portalId)->delete();

		if (empty($deptId)) {
			return true;
		}

		$data = array_map(function($value){
			return new PortalDeptViewPurviewEntity(['dept_id' => $value]);
		},$deptId);

		$portal = $this->entity->find($portalId);
		return $portal->userViewPurview()->saveMany($data);
	}

	public function setRoleViewPurview($portalId, $roleId)
	{
		PortalRoleViewPurviewEntity::where('portal_id',$portalId)->delete();

		if (empty($roleId)) {
			return true;
		}

		$data = array_map(function($value){
			return new PortalRoleViewPurviewEntity(['role_id' => $value]);
		}, $roleId);

		$portal = $this->entity->find($portalId);

		return $portal->roleViewPurview()->saveMany($data);
	}

	public function setDeptEditPurview($portalId, $deptId)
	{
		PortalDeptEditPurviewEntity::where('portal_id',$portalId)->delete();

		if (empty($deptId)) {
			return true;
		}

		$data = array_map(function($value){
			return new PortalDeptEditPurviewEntity(['dept_id' => $value]);
		}, $deptId);

		$portal = $this->entity->find($portalId);

		return $portal->userViewPurview()->saveMany($data);
	}

	public function setRoleEditPurview($portalId, $roleId)
	{
		PortalRoleEditPurviewEntity::where('portal_id',$portalId)->delete();

		if (empty($roleId)) {
			return true;
		}

		$data = array_map(function($value){
			return new PortalRoleEditPurviewEntity(['role_id' => $value]);
		}, $roleId);

		$portal = $this->entity->find($portalId);

		return $portal->roleViewPurview()->saveMany($data);
	}

    public function updateSystemParams($params)
    {
        if(empty($params)){
            return false;
        }
        $flag = true;

        foreach ($params as $key => $value){
            $result = SystemParamsEntity::where('param_key',$key)->update(['param_value' => $value]);
            if($result!=0 && !$result){
                $flag = false;
            }
        }

        return $flag;
    }

    public function setNavbar($data){
        if (empty($data)) {
            return false;
        }
        foreach ($data as $param_key => $param_value) {
            if ($param_key == "navigate_menus") {
                if (empty($param_value)) {
                    $param_value = [];
                }
                $param_value = json_encode($param_value);
            }
            $system_params = DB::table('system_params')->where('param_key', $param_key)->first();
            if (empty($system_params)) {
                DB::table("system_params")->insert(['param_key' => $param_key, 'param_value' => $param_value]);
            } else {
                SystemParamsEntity::where('param_key', $param_key)->update(['param_value' => $param_value]);
            }
        }
    }

    public function getNavbar(){
        $paramKeys = ['web_home', 'navbar_type', 'home_text', 'navigate_menus', 'web_home_module_type', 'home_module'];
        $params = DB::table('system_params')->whereIn("param_key", $paramKeys)->get();
        $paramsMap = [];
        if(!$params->isEmpty()) {
            $paramsMap = $params->mapWithKeys(function($item){
                return [$item->param_key => $item->param_value];
            });
        }
        $data = [];
        $data['web_home'] = isset($paramsMap['web_home']) ? $paramsMap['web_home'] : 'icon';
        $data['navbar_type'] = isset($paramsMap['navbar_type']) ? $paramsMap['navbar_type'] : 'click';
        $data['web_home_module_type'] = isset($paramsMap['web_home_module_type']) && $paramsMap['web_home_module_type'] ? $paramsMap['web_home_module_type'] : 'portal';
        if ($data['web_home'] == "text") {
            $data['home_text'] = isset($paramsMap['home_text']) ? mulit_trans_dynamic("system_params.param_value.home_text") : '';
        }
        if ($data['navbar_type'] == "custom") {
            $data['navigate_menus'] = isset($paramsMap['navigate_menus']) ? json_decode($paramsMap['navigate_menus']) : [];
        }
        if ($data['web_home_module_type'] == "custom") {
            $data['home_module'] = $paramsMap['home_module'] ?? null;
        }
        return $data;
    }
}
