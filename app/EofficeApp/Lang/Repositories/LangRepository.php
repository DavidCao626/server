<?php
namespace App\EofficeApp\Lang\Repositories;

use Schema;
use Illuminate\Database\Schema\Blueprint;
use DB;
class LangRepository
{
    public function __construct()
    {
    }
    public function clearLangs($tableName)
    {
        return DB::table($tableName)->truncate();
    }
    public function tableExists($tableName)
    {
        return Schema::hasTable($tableName);
    }
    public function dropTable($tableName)
    {
        return Schema::dropIfExists($tableName);
    }
    public function insertMultipleData($data, $tableName)
    {
        return DB::table($tableName)->insert($data);
    }
    public function getLangValueByTable($tableName, $langTable)
    {
        return DB::table($langTable)->where('table', $tableName)->get();
    }
    public function getLangValue($tableName, $keys)
    {
        return DB::table($tableName)
                ->where('table', $keys[0])
                ->where('column', $keys[1])
                ->where('option',$keys[2])
                ->where('lang_key', $keys[3])->first();
    }
    public function getLangCount($tableName)
    {
        return DB::table($tableName)->count();
    }
    public function updateData($data, $wheres, $tableName)
    {
        $query = DB::table($tableName);

        if (!empty($wheres)) {
            foreach ($wheres as $key => $where) {
                $condition = $where[1] ?? '=';
                $query->where($key, $condition, $where[0]);
            }
        }

        return $query->update($data);
    }
    public function getAllLangs($tableName)
    {
        return DB::table($tableName)->orderBy('id', 'asc')->get();
    }
    public function getLangKeysLikeValue($table,$column, $option, $langValue, $tableName)
    {
        return DB::table($tableName)->select(['lang_key'])->where('table', $table)->where('column', $column)->where('option', $option)->where('lang_value','like', "%$langValue%")->get();
    }
    public function getOnePageLangs($tableName, $offset, $limit, $search = [])
    {
        $query = DB::table($tableName);
        if(!empty($search)) {
            foreach ($search as $key => $where) {
                $condition = $where[1] ?? '=';
                $value = $where[0];
                if($condition == 'like') {
                    $value = "%$value%";
                }
                $query->where($key, $condition, $value);
            }
        }
        return $query->offset($offset)->limit($limit)->orderBy('id', 'asc')->get();
    }
    public function getLangs($tableName, $table, $column, $option, $langKeys)
    {
        return DB::table($tableName)->where('table', $table)->where('column', $column)->where('option', $option)->whereIn('lang_key', $langKeys)->get();
    }
    public function createLangTable($tableName)
    {
        if(!Schema::hasTable($tableName)){
            Schema::create($tableName, function(Blueprint $table)
            {
                $table->increments('id')->comment("主键id");
                $table->string('lang_key',100)->comment('语言键名');
                $table->string('lang_value',255)->comment('语言');
                $table->string('table',100)->comment('表名');
                $table->string('column',100)->comment('列名');
                $table->string('option',100)->comment('选项(如果列只有一个选项，则值和列名称一样)');
            });

            return $this->tableExists($tableName);
        }
    }

    public function addDynamicLang($data, $table)
    {
        if(DB::table($table)->where('table', $data['table'])
            ->where('column', $data['column'])
            ->where('option', $data['option'])
            ->where('lang_key', $data['lang_key'])->first()) {
            return DB::table($table)->where('table', $data['table'])
            ->where('column', $data['column'])
            ->where('option', $data['option'])
            ->where('lang_key', $data['lang_key'])->update(['lang_value' => $data['lang_value']]);
        }

        return DB::table($table)->insert($data);
    }
    public function deleteDynamicLang($table, $conditionTable, $column, $option, $langKeys)
    {
        $query = DB::table($table)
                ->where('table', $conditionTable)
                ->where('column', $column)
                ->where('option', $option);
        
        $query = is_array($langKeys) ? $query->whereIn('lang_key', $langKeys) : $query->where('lang_key', $langKeys);
        
        return $query->delete();
    }
    /**
     * 根据多语言value查找key
     */
    public function getLangKey($tableName,$langValue,$table)
    {
        return DB::table($tableName)
                ->where('table', $table)
                ->where('lang_value', $langValue)->first();
    }
}
