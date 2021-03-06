<?php

namespace app\api\controller;

use think\exception\ValidateException;
use think\facade\Filesystem;
use think\response\Json;

class File
{
    public function updateImage(): Json
    {
        // 获取表单上传文件
        $files = request()->file('image');
        if (empty($files)) {
            return returnData(['code' => 404, 'msg' => "未检测到文件"]);
        }
        try {
            validate(['file' => [
                'fileExt' => 'jpg,png,gif,jpeg',
                'fileMime' => 'image/jpeg,image/png,image/gif',
            ]])->check(['file' => $files]);
            $savePath = $this->savePath($this->saveFile($files), 1);
            return returnData($savePath);
        } catch (ValidateException $e) {
            return returnData(['code' => 404, 'msg' => $e->getMessage()]);
        }
    }

    private function savePath($saveName, $type): array
    {
        $savePath = [];
        foreach ($saveName as $filepath) {
            $filepath1 = str_replace("\\", "/", $filepath);
            $file = \app\common\model\File::create([
                'type' => $type,
                'create_time' => time(),
                'file_path' => "/storage/" . $filepath1
            ]);
            $savePath[] = ['fileId' => $file->id, "file_path" => http()."/storage/" . $filepath1];
        }
        return $savePath;
    }

    private function saveFile($files): array
    {
        $saveName = [];
        foreach ($files as $file1) {
            $saveName[] = Filesystem::disk('public')->putFile('topic', $file1);
        }
        return $saveName;
    }

    public function updateVideo(): Json
    {
        // 获取表单上传文件
        $files = request()->file('video');
        if (empty($files)) {
            return returnData(['code' => 404, 'msg' => "未检测到文件"]);
        }
        try {
            validate(['file' => 'fileMime:video/mp4'])->check(['file' => $files]);
            $savePath = $this->savePath($this->saveFile($files), 2);
            return returnData($savePath);
        } catch (ValidateException $e) {
            return returnData(['code' => 404, 'msg' => $e->getMessage()]);
        }
    }

    public function getFiles(): Json
    {
        $type = input('post.type', '', 'strip_tags');
        $fileId = input('post.fileId', '', 'strip_tags');
        if (empty($type) || empty($fileId)) {
            return returnData(['code' => 404, 'msg' => '参数不完整，请检查参数']);
        }
        $filePath = (new \app\common\model\File)->where(['type' => $type, 'id' => $fileId])->find();
        if (empty($filePath)) {
            return returnData(['code' => 404, 'msg' => '为查询到参数']);
        }
        if (PATH_SEPARATOR == ':') {
            return returnData(['code' => 200, 'date' => 'http://' . $_SERVER['HTTP_HOST'] . '/storage/' . $filePath->toArray()['file_path']]);
        } else {
            return returnData(['code' => 200, 'date' => str_replace('\\', '/', 'http://' . $_SERVER['HTTP_HOST'] . '/storage/' . $filePath->toArray()['file_path'])]);
        }
    }
}