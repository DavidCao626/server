<?php
namespace App\EofficeApp\Attachment\Repositories;
use DB;
use Schema;
class AttachmentRepository
{
    private $fullTable = ['rel_id','attachment_name','affect_attachment_name','thumb_attachment_name','attachment_desc','attachment_size','attachment_type','attachment_create_user','attachment_base_path','attachment_path','attachment_mark','relation_table','created_at','updated_at'];

    public function __construct()
    {

    }
    public function makeAttachmentTable($tableName)
    {
        if (Schema::hasTable($tableName)) {
            return $tableName;
        }

        Schema::create($tableName, function($table) {
            $table->integer('rel_id')->comment('附件关联id');
            $table->string('attachment_name')->comment('附件原名称');
            $table->string('affect_attachment_name', 150)->comment('加密后的附件名称');
            $table->string('thumb_attachment_name', 150)->default('')->comment('附件缩略图名称');
            $table->string('attachment_desc', 255)->comment('附件描述')->default('');
            $table->integer('attachment_size')->comment('附件大小')->default(0);
            $table->string('attachment_type', 20)->comment('附件类别')->default('');
            $table->char('attachment_create_user', 12)->comment('附件创建人')->default('');
            $table->string('attachment_base_path', 100)->comment('附件根路径')->default('');
            $table->string('attachment_path', 100)->comment('附件路径')->default('');
            $table->tinyInteger('attachment_mark')->comment('附件标志')->default(9);
            $table->string('relation_table', 50)->comment('附件关系表')->default('');
            $table->timestamps();
            $table->unique('rel_id');
        });

        return $tableName;
    }
    public function makeRelationTable($tableName, $extraColumns)
    {
        if (Schema::hasTable($tableName)) {
            if (is_array($extraColumns)) {
                foreach ($extraColumns as $v) {
                    if (isset($v['field_name']) && $v['field_name'] == 'entity_column') {
                        if (!Schema::hasColumn($tableName, 'entity_column')) {
                            Schema::table($tableName, function($table) {
                                $table->string('entity_column', 50)->comment("管理表对应记录表的字段");
                            });
                        }
                        break;
                    }
                }
            }
            return true;
        } else {
            Schema::create($tableName, function ($table) use($extraColumns) {
                $table->charset = 'utf8';
                $table->increments("relation_id")->comment('主键');
                $dataTypes = ['integer', 'string', 'text', 'dateTime'];
                if (!empty($extraColumns)) {
                    foreach ($extraColumns as $column) {
                        $fieldType = $column['field_type'];
                        $fieldComment = $column['field_comment'] ?? '';
                        if (in_array($fieldType, $dataTypes)) {
                            if ($fieldType == 'string') {
                                $length = isset($column['field_length']) ? $column['field_length'] : "255";
                                $table->string($column['field_name'], $length)->comment($fieldComment);
                            } else {
                                $table->{$fieldType}($column['field_name'])->comment($fieldComment);
                            }
                        }
                    }
                }
                $table->string("attachment_id", 100)->comment('表主键');
            });
            return Schema::hasTable($tableName);
        }
    }

    public function deleteAttachment($tableName, $relIds, $column = 'rel_id')
    {
        if (is_array($relIds)) {
            return DB::table($tableName)->whereIn($column, $relIds)->delete();
        }
        return DB::table($tableName)->where($column, $relIds)->delete();
    }

    public function tableExists($tableName)
    {
        if (Schema::hasTable($tableName)) {
            return true;
        }

        return false;
    }
    public function getAttachmentIds($tableName, $wheres)
    {
        return $this->andWheres(DB::table($tableName)->select(['attachment_id']), $wheres)->get();
    }
    public function getAttachmentRelations($relationTableName, $fields = ['*'], $wheres = [])
    {
        if (!Schema::hasTable($relationTableName)) {
            return [];
        }
        return $this->andWheres(DB::table($relationTableName)->select($fields),$wheres)->distinct()->get();
    }
    public function getEntityIdsByAttachmentIds($attachmentIds, $tableName)
    {
        return DB::table($tableName)->select(['entity_id'])->whereIn('attachment_id', $attachmentIds)->get()->toArray();
    }
    public function getEntityIdsByEntityIds($entityIds, $tableName)
    {
        return DB::table($tableName)->select(['entity_id', 'attachment_id'])->whereIn('entity_id', $entityIds)->get()->toArray();
    }
    public function getOneAttachment($tableName, $wheres)
    {
        return $this->andWheres(DB::table($tableName), $wheres)->first();
    }
    public function getAttachments($tableName, $wheres)
    {
        return $this->andWheres(DB::table($tableName), $wheres)->get();
    }

    public function updateAttachmentData($tableName, $data, $wheres)
    {
       return $this->andWheres(DB::table($tableName), $wheres)->update($data);
    }
    public function saveRelationData($tableName, $data)
    {
        return DB::table($tableName)->insert($data);
    }
    public function saveAttachmentData($tableName, $data)
    {
        return $this->saveDataThen($data, $tableName, $this->fullTable, function($data){
            $data['created_at'] = date('Y-m-d H:i:s');
            $data['updated_at'] = date('Y-m-d H:i:s');
            return $data;
        });
    }
    public function saveDataThen($data, $tableName, $fullTable,$handle)
    {
        if(empty($data) || !is_array($data)){
            return false;
        }

        $saveData = [];
        foreach($data as $key => $value){
            if(in_array($key, $fullTable)) {
                $saveData[$key] = $value;
            }
        }

        $saveData = $handle($saveData);

        return DB::table($tableName)->insert($saveData);
    }
    private function andWheres($query, $wheres)
    {
        $operators = [
            'between'       => 'whereBetween',
            'not_between'   => 'whereNotBetween',
            'in'            => 'whereIn',
            'not_in'        => 'whereNotIn'
        ];

        if (empty($wheres)) {
            return $query;
        }

        foreach ($wheres as $field=>$where) {
            $operator = isset($where[1]) ? $where[1] : '=';
            $operator = strtolower($operator);
            if (isset($operators[$operator])) {
                $whereOp = $operators[$operator]; //兼容PHP7写法
                $query = $query->$whereOp($field, $where[0]);
            } else {
                $value = $operator != 'like' ? $where[0] : '%'.$where[0].'%';
                $query = $query->where($field, $operator, $value);
            }
        }

        return $query;
    }

    public function migrateAttachmentPath($table, $sourcePath, $descPath)
    {
        if (Schema::hasTable($table)) {
            return DB::table($table)->where('attachment_base_path', $sourcePath)->update(['attachment_base_path' => $descPath]);
        }
        return false;
    }
}
