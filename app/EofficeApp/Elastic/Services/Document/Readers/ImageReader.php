<?php

namespace App\EofficeApp\Elastic\Services\Document\Readers;

use App\EofficeApp\Elastic\Services\Document\Contract\DocumentReaderInterface;

class ImageReader implements DocumentReaderInterface
{
    private $imageInfo = [];//识别出来的文字位置数据

    /**
     * 图片文字识别
     * @param $realPath
     * @return mixed|string
     */
    public function readContent($realPath)
    {
        // 图片暂时无法识别 后续考虑接入ocr
        return '';
        if (file_exists($realPath)) {
            $this->imageInfo = [];
            try {
                $eofficeRoot = dirname(getAttachmentDir());
                $elasticRoot = $eofficeRoot.'/elastic';
                $ocrExeRoot = $elasticRoot . '/ocr';

                $order = 'cd ' . $ocrExeRoot . ' && ';
                //可用选项备注：--oem 1 引擎，chi_sim+eng+chi_tra
//                $name = is_win() ? 'tesseract.exe' : 'tesseract';
                $name = 'tesseract.exe';
                $order .= $ocrExeRoot . "/{$name}" . ' -l chi_sim ';
                $order .= $realPath . ' -';//makebox代表可获取到坐标位置

                $content = shell_exec($order);

                $order .= ' makebox';
                $locationInfo = shell_exec($order);
                $location = $this->handleBoxContent($locationInfo);
                $imageInfo = $this->readImageInfo($realPath);
                $this->imageInfo = [
                    'location' => $location,
                    'weight' => $imageInfo['weight'],
                    'height' => $imageInfo['height'],
                ];
                return $content;
            } catch (\Exception $e) {
                return '';
            }
        }

        return '';
    }

    /**
     * 获取上次图片识别的文字位置，并清空信息
     * @return array
     */
    public function getLastImageInfoOnce()
    {
        $data = $this->imageInfo;
        $this->imageInfo = [];

        return $data;
    }

    /**
     * 获取图片宽高
     * @param string $imagePath
     * @return array
     */
    public function readImageInfo(string $imagePath)
    {
        $imageInfo = getimagesize($imagePath);

        return [
            'weight' => $imageInfo ? $imageInfo[0] : 0,
            'height' => $imageInfo ? $imageInfo[1] : 0,
        ];
    }

    /**
     * 处理识别的图片位置信息
     * @param $location
     * @return array
     */
    private function handleBoxContent($location)
    {
        if (!$location) {
            return [
                'sentence' => '',
                'location' => []
            ];
        }
        $location = explode("\n", $location);
        foreach ($location as $key => $line) {
            $line = explode(' ', $line);
            //切割后至少有6项
            if (count($line) < 6) {
                unset($location[$key]);
            } else {
                $location[$key] = $line;
            }
        }
        $location = array_values($location);

        return $location;
    }
}