<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AttachmentUploadTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic feature test example.
     */
    public function test_example(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    public function test_authenticated_user_can_upload_attachment_to_task()
    {
        // Simula el disco S3
        Storage::fake('s3');

        // Crea un usuario y una tarea
        $user = User::factory()->create();
        $task = Task::factory()->create(['user_id' => $user->id]);

        // Simula autenticación
        $this->actingAs($user);

        // Archivo falso
        $file = UploadedFile::fake()->create('documento.pdf', 100, 'application/pdf');

        // Realiza la petición
        $response = $this->post("/tasks/{$task->id}/attachments", [
            'file' => $file,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'attachment' => [
                    'id', 'task_id', 'user_id', 'filename', 'path', 'mime_type', 'size', 'created_at', 'updated_at'
                ],
                'url'
            ]);

        // Verifica que el archivo se subió a S3
        Storage::disk('s3')->assertExists('attachments/' . $file->hashName());

        // Verifica que el registro existe en la base de datos
        $this->assertDatabaseHas('attachments', [
            'task_id' => $task->id,
            'user_id' => $user->id,
            'filename' => 'documento.pdf',
        ]);
    }
}
