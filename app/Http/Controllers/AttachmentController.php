<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Attachment;
use App\Models\Task;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class AttachmentController extends Controller
{
    public function store(Request $request, $taskId)
    {
        $request->validate([
            'file' => 'required|file|max:10240', // 10MB max
        ]);

        $task = Task::findOrFail($taskId);

        $file = $request->file('file');
        $path = $file->store('attachments', 's3'); // Guarda en S3 bajo la carpeta attachments

        // Guarda el registro en la base de datos
        $attachment = Attachment::create([
            'task_id'   => $task->id,
            'user_id'   => Auth::id(),
            'filename'  => $file->getClientOriginalName(),
            'path'      => $path,
            'mime_type' => $file->getClientMimeType(),
            'size'      => $file->getSize(),
        ]);

        // Opcional: hacer público el archivo
        Storage::disk('s3')->setVisibility($path, 'public');

        return response()->json([
            'message' => 'Archivo subido correctamente',
            'attachment' => $attachment,
            'url' => Storage::disk('s3')->url($path),
        ]);
    }
} 