<?php

namespace App\Http\Controllers\Disk;

use App\Http\Controllers\Controller;
use App\Models\DiskFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class Files extends Controller
{
    /**
     * Список файлов пользователя
     * 
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $request->dir = Disk::getFolderId($request->dir);

        $dir = DiskFile::find($request->dir);

        if (!($dir->is_dir ?? null))
            return response()->json(['message' => "Каталг с файлами не найден или был удален"], 404);

        $files = $dir->files()
            ->orderBy('is_dir', 'DESC')
            ->orderBy('name')
            ->get()
            ->map(function ($row) {
                return $this->serialize($row);
            });

        return response()->json([
            'dir' => $request->dir,
            'files' => $files,
            'page' => $request->page ?: 1,
        ]);
    }

    /**
     * Формирование строки файла
     * 
     * @param  \App\Models\DiskFile $row
     * @return array
     */
    public function serialize(DiskFile $row)
    {
        $dir = $row->dir;

        if ($row->dir)
            $row->dir = env("DRIVE_DIR", "drive") . "/" . $row->dir;

        if ($row->thumb_litle) {

            $row->thumb_litle_url = env("APP_URL") . "/thumbs/{$dir}/{$row->thumb_litle}";

            // $img = Storage::path("{$row->dir}/thumbs/{$row->thumb_litle}");

            // $imageSize = getimagesize($img);
            // $imageData = base64_encode(file_get_contents($img));

            // $row->thumb_litle_url = "data:{$imageSize['mime']};base64,{$imageData}";
        }

        $row->icon = $row->is_dir ? "folder" : IconsNames::get($row->ext);

        $row->link = $this->decToLink($row->id);

        return $row->toArray();
    }

    /**
     * Создание каталога
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     * 
     * @todo Добавить проверка общего доступа
     */
    public function createFolder(Request $request)
    {
        $request->dir = Disk::getFolderId($request->dir);

        if (!$dir = DiskFile::find($request->dir))
            return response()->json(['message' => "Каталог не найден или уже удален"], 400);

        if ($dir->user_id != $request->user()->id)
            return response()->json(['message' => "Доступ к каталогу ограничен"], 403);

        $file = DiskFile::create([
            'user_id' => $request->user()->id,
            'name' => $request->name ?: "Новая папка",
            'is_dir' => true,
        ]);

        $dir->files()->attach($file->id);

        return response()->json([
            'file' => $this->serialize($file),
        ]);
    }
}
