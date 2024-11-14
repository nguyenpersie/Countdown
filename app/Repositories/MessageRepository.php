<?php

namespace App\Repositories;

use App\Models\Saoke;

class MessageRepository
{
    protected $model;

    public function __construct()
    {
        $this->model = $this->getModel();
    }

    protected function getModel(): string
    {
        return Saoke::class;
    }

    public function getDataChunkById($chunkById, string $message)
    {
        return $this->model::where('detail', 'LIKE', "%{$message}%")
            ->chunkById(20, $chunkById);
    }
}
