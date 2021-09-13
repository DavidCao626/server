<?php

namespace App\EofficeApp\Project\NewServices\ServiceTraits\setting;

use App\EofficeApp\Project\NewRepositories\ProjectConfigRepository;

Trait ProjectSettingTrait
{
    public static function otherSettingList() {
        return [
            'task_progress_show_model' => ProjectConfigRepository::getTaskProgressShowModel()
        ];
    }

    public static function otherSettingEdit($data) {
        if (array_key_exists('task_progress_show_model', $data)) {
            ProjectConfigRepository::delTaskProgressShowModelCache();
            ProjectConfigRepository::buildQuery(['key' => 'task_progress_show_model'])->update([
                'value' => $data['task_progress_show_model'] ? 1 : 0
            ]);
        }
    }
}
