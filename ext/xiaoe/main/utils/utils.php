<?php

namespace EassistantClient\Utils;

use Illuminate\Support\Facades\Cache;

class Utils
{

    /**
     * 获取当前用户的信息
     * @param $apiToken
     * @return array|bool
     */
    public static function getUser($apiToken)
    {
        if ($apiToken) {
            $user = Cache::get($apiToken);
            if (!$user) {
                return false;
            }
            $own = [
                'user_id' => $user->user_id,
                'user_name' => $user->user_name,
                'user_accounts' => $user->user_accounts,
                'dept_id' => isset($user->userHasOneSystemInfo->dept_id) ? $user->userHasOneSystemInfo->dept_id : ($user->dept_id ? $user->dept_id : null),
                'dept_name' => isset($user->userHasOneSystemInfo->userSystemInfoBelongsToDepartment->dept_name) ? $user->userHasOneSystemInfo->userSystemInfoBelongsToDepartment->dept_name : ($user->dept_name ? $user->dept_name : null),
                'roles' => $user->roles,
                'role_id' => isset($user->roles) ? array_column($user->roles, 'role_id') : self::parseRoles($user['userHasManyRole'], 'role_id'),
                'role_name' => isset($user->roles) ? array_column($user->roles, 'role_name') : self::parseRoles($user['userHasManyRole'], 'role_name'),
                'menus' => isset($user->menus) ? $user->menus : [],
                'post_priv' => isset($user->userHasOneSystemInfo->post_priv) ? $user->userHasOneSystemInfo->post_priv : ($user->post_priv ? $user->post_priv : 0),
                'max_role_no' => isset($user->userHasOneSystemInfo->max_role_no) ? $user->userHasOneSystemInfo->max_role_no : ($user->max_role_no ? $user->max_role_no : 0),
            ];
            return $own;
        }
        return false;
    }

    public static function parseRoles($roles, $type)
    {
        if (empty($roles)) {
            return [];
        }
        static $parseRole = array();

        if (!empty($parseRole)) {
            return $parseRole[$type];
        }
        $roleId = $roleName = $roleArray = [];

        foreach ($roles as $role) {
            $roleId[] = $role['role_id'];
            $roleName[] = $roleArray[$role['role_id']] = $role['hasOneRole']['role_name'];
        }

        $parseRole = ['role_id' => $roleId, 'role_name' => $roleName, 'role_array' => $roleArray];

        return $parseRole[$type];
    }

    /**
     * rgb转hexcolor
     * @param $rgb
     * @return string
     */
    public static function rgbToHex($rgb)
    {
        $rgb = 'rgb(' . $rgb . ')';
        $regexp = "/^rgb\(([0-9]{0,3})\,\s*([0-9]{0,3})\,\s*([0-9]{0,3})\)/";
        $re = preg_match($regexp, $rgb, $match);
        $re = array_shift($match);
        $hexColor = "#";
        $hex = array('0', '1', '2', '3', '4', '5', '6', '7', '8', '9', 'A', 'B', 'C', 'D', 'E', 'F');
        for ($i = 0; $i < 3; $i++) {
            $r = null;
            $c = $match[$i];
            $hexAr = array();
            while ($c > 16) {
                $r = $c % 16;
                $c = ($c / 16) >> 0;
                array_push($hexAr, $hex[$r]);
            }
            array_push($hexAr, $hex[$c]);
            $ret = array_reverse($hexAr);
            $item = implode('', $ret);
            $item = str_pad($item, 2, '0', STR_PAD_LEFT);
            $hexColor .= $item;
        }
        return $hexColor;
    }

    public static function errorResponse($url)
    {
        if ($url) {
            echo "<script>location.href='$url'</script>";
        } else {
            echo "<script>history.back()</script>";
        }
    }
}