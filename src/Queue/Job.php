<?php
// src/Queue/Job.php

namespace App\Queue;

class Job {
    /**
     * Añade un nuevo trabajo a la cola.
     * @param string $queue El nombre de la cola (ej. 'emails').
     * @param array $payload Los datos necesarios para ejecutar el trabajo.
     */
    public static function dispatch(string $queue, array $payload): void {
        $pdo = get_pdo_connection();
        $stmt = $pdo->prepare(
            "INSERT INTO jobs (queue, payload, available_at, created_at) VALUES (?, ?, ?, ?)"
        );
        $now = time();
        $stmt->execute([$queue, json_encode($payload), $now, $now]);
    }
}