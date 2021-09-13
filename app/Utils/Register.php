<?php

namespace App\Utils;

use Cache;
use App\Utils\Mac;

/**
 * 注册
 *
 * @author qishaobo
 *
 * @since  2017-03-16 创建
 */
class Register
{
	public function __construct(Mac $mac) {
		$this->mac = $mac;

        $documentRoot = dirname(getenv('DOCUMENT_ROOT'));
        if (empty($documentRoot)) {
            $documentRoot = dirname(dirname(dirname(dirname(dirname(__DIR__)))));
        }

		$this->regDir = $documentRoot.DIRECTORY_SEPARATOR.'bin'.DIRECTORY_SEPARATOR;
		$this->regModuleDir = $this->regDir;

		$this->checkDir();
	}

	function checkDir()
	{
		if (!is_dir($this->regDir)) {
			mkdir($this->regDir, 0777, true);
		}

		if (!is_dir($this->regModuleDir)) {
			mkdir($this->regModuleDir, 0777, true);
		}
	}

	/**
	 * 获取机器码
	 *
	 * @param int $limit 获取机器码数量
	 *
	 * @return array 机器码
	 */
	function getMachineCode($limit = 0)
	{
		$key = substr(md5('eoffice10_mac_code'), 0, -2);

		$macCache = Cache::get($key);

		if (!empty($macCache)){
			$mac = unserialize($macCache);
		} else {
		 	$mac = $this->mac->getMacAddr(PHP_OS);
		 	Cache::add($key, serialize($mac), 1440);
		}

		//记录mac地址，查找mac地址改变问题
		if (empty($mac)) {
			return ['code' => ['mac_empty', 'register']];
		}
		foreach ($mac as $code){
			$macCode[] = strtoupper(md5(str_replace('-', '', $code).getenv('PROCESSOR_REVISION')));
		}

		if (empty($macCode)) {
			return ['code' => ['mac_empty', 'register']];
		}

		return $limit == 1 ? $macCode[0] : $macCode;
	}

	function registerDate($date = '') {
		$dir = base_path('public/iWebOffice/');

		if (!file_exists($dir)) {
			mkdir($dir);
		}

		$file = $dir.'demo.txt';



		if (empty($date)) {
			if (!is_file($file)) {
				return '';
			}

			$date = file_get_contents($file);

			$filectime = filectime($file);
			$filemtime = filemtime($file);

			$filecDate = date('Y-m-d', $filectime);
			$filemDate = date('Y-m-d', $filemtime);


			if ($filecDate != $filemDate) {
				return '';
			}

			$isDate = strtotime($date);

			if($isDate) {
				$dateArray = explode('-', $date);
				if (count($dateArray) != 3) {
					return '';
				} else {
					$filerYear  = $dateArray[0];
					$filerDay   = $dateArray[2];
				}
				$filecMonth = date('m', $filectime);
				$parseFilecMonth = $filecMonth * 125 % 12;
				$parseFilecMonth = $parseFilecMonth == 0 ? '12' : sprintf("%02d", $parseFilecMonth);
				$newFilerDate    = $filerYear.'-'.$parseFilecMonth.'-'.$filerDay;

				if($newFilerDate != $date) {
					return '';
				}
			}else{
				return '';
			}

			return $filecDate;
		}
        // 20201203-chmod修改时调查：
        // app\EofficeApp\Empower\Services\EmpowerService.php 内调用此函数时，没有传参数 $date ，所以下面代码不会运行，chmod不用修改
		chmod($dir, 0777);
		if (is_file($file)) {
            chmod($file, 0777);
        }
		return file_put_contents($file, $date);
	}

	function isPcRegistered()
	{
	  	static $isPcRegister;

	  	if (isset($isPcRegister)) {
	  		return $isPcRegister;
	  	}

		$isPcRegister = 0;

		if (!file_exists($this->regDir."ZendPhpOp.dll")) {
			return $isPcRegister;
		}

		$macs = $this->getMachineCode();
		if (isset($macs['code'])) {
			return $macs;
		}

		$reg = $this->parseRegFileStr('pc');

		if (isset($reg['machineCode']) && in_array($reg['machineCode'], $macs)) {
			$isPcRegister = 1;
		}

		return $isPcRegister;
	}

	function isMobileRegistered()
	{
	  	static $isMobileRegister;

	  	if (isset($isMobileRegister)) {
	  		return $isMobileRegister;
	  	}

		$isMobileRegister = 0;

		if (!file_exists($this->regDir."ZendPhpOp.dll")) {
			return $isMobileRegister;
		}

		$macs = $this->getMachineCode();
		if (isset($macs['code'])) {
			return $macs;
		}

		$reg = $this->parseRegFileStr('mobile');

		if (isset($reg['machineCode']) && in_array($reg['machineCode'], $macs)) {
			$isMobileRegister = 1;
		}

		return $isMobileRegister;
	}

	function isPermanentUser()
	{
	  	static $isPermanent;

	  	if (isset($register)) {
	  		return $isPermanent;
	  	}

	  	$isPermanent = 0;

		if (!$this->isPcRegistered()) {
			return $isPermanent;
		}

		$macs = $this->getMachineCode();
		if (isset($macs['code'])) {
			return $macs;
		}

		$regs = $this->parseRegFileStr('pc');

		if (!empty($regs)) {
			if (!isset($regs['machineCode']) || !in_array($regs['machineCode'], $macs)) {
				return $isPermanent;
			}

			if ($regs['expireDdate'] === 1 || date("Y", strtotime($regs['expireDdate'])) >= "2030") {
				$isPermanent = 1;
			}
		}

		return $isPermanent;
	}

	function parseRegFileStr($type=""){
		if (isset($data)) {
			return $data;
		}

		$data = [];

		$keyNameFile = $this->regDir."ZendPhpOp.dll";

		if (!file_exists($keyNameFile)) {
			return $data;
		}

		$regName = file_get_contents($keyNameFile);
		$regNames = array_filter(explode(",", $regName));

		if (empty($regNames)) {
			return $data;
		}

        if(!empty($type) && $type == 'pc') {
			foreach ($regNames as $name) {
				$key = ['machineCode', 'empowerName', 'expireDdate', 'pcUserNumber', 'version'];
				$pcRegFile = $this->regDir.$name.".dll";
				if (!file_exists($pcRegFile)) {
					continue;
				}
				$filectime = filectime($pcRegFile);
				$filecDate = date('Y-m-d', $filectime);
				$contents = file_get_contents($pcRegFile);
				$contentsDecode = xxtea_decrypt(base64_decode($contents), "WV10ZJ1106");
				$value = explode("|", $contentsDecode);
				$pcTrialDays = '';
				$startDate = strtotime($filecDate);
				if (isset($value['2'])) {
					$endDate = strtotime($value['2']);
					$pcTrialDays = round(($endDate-$startDate)/3600/24);
				}
				if(isset($value['1'])) {
					$value['1'] = iconv('GBK', 'UTF-8', $value['1']);
				}
				$tempData = array_combine($key, $value);
				$tempData['pcTrialDays'] = $pcTrialDays;
				$data[] = $tempData;
			}
        }
        if(!empty($type) && $type == 'mobile') {
			foreach ($regNames as $name) {
				$key = ['empowerName', 'machineCode', 'mobileUserNumber', 'expireDdate', 'version'];
				$mobileRegFile = $this->regDir.$name."_mobile.dll";
				if (!file_exists($mobileRegFile)) {
					continue;
				}

				$contents = file_get_contents($mobileRegFile);
				$contentsDecode = xxtea_decrypt(base64_decode($contents), "WV10ZJ1106");
				$value = explode("|", $contentsDecode);
				if(isset($value['0'])) {
					$value['0'] = iconv('GBK', 'UTF-8', $value['0']);
				}
				$data[] = array_combine($key, $value);
			}
        }
        if(!empty($data)) {
            $data = array_pop($data);
            if (!empty($data['expireDdate'])) {
                $data['dateInterval'] = $this->getDateInterval($data['expireDdate']);
            }
        	// 记录是否是永久授权
	        if (!empty($data['expireDdate']) && ($data['expireDdate'] >= '2030-01-01')) {
	        	$data['expireDdate'] = 1;
	        }
        }

		return $data;
    }

    function getDateInterval($expireDdate) {
        if ($expireDdate == 1) {
            return -1;
        } else {
            if (date('Y-m-d') >= $expireDdate) {
                return 0;
            } else {
                $diff = date_diff(date_create(date('Y-m-d')), date_create($expireDdate));
                return $diff->days;
            }
        }
    }

	function isExpired(){
		$regs = $this->parseRegFileStr();

		if (empt($regs)) {
			return false;
		}

		$reg = array_pop($regs);

		return time() < strtotime($reg['expireDdate']) ? true : false;
	}

	function parseEmpowerFile($content, $type)
	{
        $value = explode("|", xxtea_decrypt(base64_decode($content), "WV10ZJ1106"));
        if($type == 'pc') {
        	$key = ['machineCode', 'empowerName', 'expireDdate', 'pcUserNumber', 'version'];
        	if(!isset($value['2']) || (isset($value['2']) && !strtotime($value['2']))) {
        		return ['code' => ['empower_file_error', 'register']];
        	}
        }
        if($type == 'mobile') {
        	$key = ['empowerName', 'machineCode', 'mobileUserNumber', 'expireDdate', 'version'];
        	if(!isset($value['3']) || (isset($value['2']) && !strtotime($value['3']))) {
        		return ['code' => ['empower_file_error', 'register']];
        	}
        }

        if (empty($value) || count($value) != 5) {
        	return ['code' => ['empower_file_error', 'register']];
        }
        $data = array_combine($key, $value);
        // 记录是否是永久授权
        if (!empty($data['expireDdate']) && ($data['expireDdate'] >= '2030-01-01')) {
        	$data['expireDdate'] = 1;
        }
        return $data;
	}

	function saveEmpowerInfo($data, $content, $type)
	{
        $keyContentName = md5($data['machineCode']);

        $keyNameFile = $this->regDir."ZendPhpOp.dll";
        // chmod($this->regDir, 0777);
        // 替换为会报错的dir验证
        $dirPermission = verify_dir_permission($this->regDir);
        if(is_array($dirPermission) && isset($dirPermission['code'])) {
            return $dirPermission;
        }
        if (is_file($keyNameFile)) {
            // chmod($keyNameFile, 0777);
            // 替换为会报错的file验证
            $filePermission = verify_file_permission($keyNameFile);
            if(is_array($filePermission) && isset($filePermission['code'])) {
                return $filePermission;
            }
        }
        file_put_contents($keyNameFile, ",".$keyContentName);

        if($type == 'mobile') {
        	$regFile = $this->regDir.$keyContentName."_mobile.dll";
        }else{
        	$regFile = $this->regDir.$keyContentName.".dll";
        }
        if (is_file($regFile)) {
            // chmod($regFile, 0777);
            // 替换为会报错的file验证
            $filePermission = verify_file_permission($regFile);
            if(is_array($filePermission) && isset($filePermission['code'])) {
                return $filePermission;
            }
        }
        file_put_contents($regFile, $content);
	}

    /**
     * 获取模块授权信息
     *
     * @return array 查询结果
     *
     * @author qishaobo
     *
     * @since  2017-03-17
     */
    public function getModuleEmpower()
    {
        $mac = $this->getMachineCode();
        if (isset($mac['code'])) {
			return $mac;
		}

        $verifyFileArray = array();
        if (!empty($mac)) {
            foreach ($mac as $key => $value) {
                $verifyFileArray[] = $this->regModuleDir.md5($value."eoffice9731").".evf";
            }
        }

        $verifyFile = '';
        $fileExistsFlag = 0;
        if (!empty($verifyFileArray)) {
        	foreach ($verifyFileArray as $key => $value) {
		        if (file_exists($value)) {
		        	$verifyFile = $value;
		        	$fileExistsFlag++;
		        	break;
		        }
        	}
        }

        if ($fileExistsFlag == 0) {
        	return ['code' => ['no_module_file', 'register']];
        } else {
	        if (!empty($verifyFile)) {
	        	return json_decode(authcode(file_get_contents($verifyFile), "DECODE"), true);
	        } else {
	        	return ['code' => ['no_module_file', 'register']];
	        }
        }
    }

	function saveEmpowerModule($moduleInfor, $verifyFileName, $verifyMode)
	{
        if (!file_exists($this->regModuleDir)) {
            mkdir($this->regModuleDir, 0777, true);
        }

        $regFile = $this->regModuleDir.$verifyFileName;

        //读取原始配置文件 根据授权方式 确定是追加 还是 覆盖
        if ($verifyMode == "append" && file_exists($regFile)) {
            $originalInfor = json_decode(authCode(file_get_contents($regFile)) , true);

            //合并授权文件
            if (is_array($moduleInfor) && is_array($originalInfor)) {
                foreach ($moduleInfor as $key => $value) {
                    $originalInfor[$key] = $value;
                }
            }

            $moduleInfor = $originalInfor;
        }

        $moduleVerifyString = json_encode($moduleInfor);
        // chmod($this->regModuleDir, 0777);
        // 替换为会报错的dir验证
        $dirPermission = verify_dir_permission($this->regModuleDir);
        if(is_array($dirPermission) && isset($dirPermission['code'])) {
            return $dirPermission;
        }
		if (is_file($regFile)) {
            // chmod($regFile, 0777);
            // 替换为会报错的file验证
            $filePermission = verify_file_permission($regFile);
            if(is_array($filePermission) && isset($filePermission['code'])) {
                return $filePermission;
            }
        }
        return file_put_contents($regFile, authCode($moduleVerifyString, "ENCODE"));
	}

    /**
     * 20191016-由common脚本调用此函数，用于“集成中心”模块升级，把几个迁移了父级的菜单，默认授权到授权文件中
     * @param  [type] $appendModule [需要添加的菜单的数组]
     * @return [type]               [description]
     */
    public function updateEmpowerModule($appendModule)
    {
        $mac = $this->getMachineCode();
        if (isset($mac['code'])) {
            return $mac;
        }

        $verifyFileArray = array();
        if (!empty($mac)) {
            foreach ($mac as $key => $value) {
                $verifyFileArray[] = $this->regModuleDir.md5($value."eoffice9731").".evf";
            }
        }

        $verifyFile = '';
        $fileExistsFlag = 0;
        if (!empty($verifyFileArray)) {
            foreach ($verifyFileArray as $key => $value) {
                if (file_exists($value)) {
                    $verifyFile = $value;
                    $fileExistsFlag++;
                    break;
                }
            }
        }

        if ($fileExistsFlag == 0) {
            return ['code' => ['no_module_file', 'register']];
        } else {
            if (!empty($verifyFile)) {
                $empowerModule = json_decode(authcode(file_get_contents($verifyFile), "DECODE"), true);
                // 解析出已授权模块
                if(isset($empowerModule['verifyMode'])) {
                    $moduleInfo = isset($empowerModule['moduleInfor']) ? $empowerModule['moduleInfor'] : [];
                } else {
                    $moduleInfo = $empowerModule;
                }

                // 需要append的模块数组 $appendModule
                if(!empty($appendModule) && !empty($moduleInfo)) {
                    foreach ($appendModule as $key => $info) {
                        $menuId = isset($info['menu_id']) ? $info['menu_id'] : '';
                        $permission = isset($info['permission']) ? $info['permission'] : '';
                        if($permission == 'forever') {
                            $moduleInfo[$menuId] = '';
                        } else if($permission != '') {
                            $moduleInfo[$menuId] = date('Y-m-d', strtotime('+' . $permission . ' month'));
                        }
                    }
                    if(isset($empowerModule['verifyMode'])) {
                        $empowerModule['moduleInfor'] = $moduleInfo;
                        $moduleVerifyString = json_encode($empowerModule);
                    } else {
                        $empowerModule = $moduleInfo;
                        $moduleVerifyString = json_encode($empowerModule);
                    }
                    chmod($this->regModuleDir, 0777);
                    if (is_file($verifyFile)) {
		                chmod($verifyFile, 0777);
		            }
                    return file_put_contents($verifyFile, authCode($moduleVerifyString, "ENCODE"));
                }
            } else {
                return ['code' => ['no_module_file', 'register']];
            }
        }
    }

}