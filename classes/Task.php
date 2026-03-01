<?php
class Task {

    private $conn;
    private $table = "tasks";

    public $id;
    public $title;
    public $description;
    public $priority;
    public $status;
    public $due_date;
    public $project_id;
    public $assigned_to;
    public $is_recurring;
    public $created_at;
    public $completed_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Получить все задачи
    public function getAll() {
        $query = "SELECT * FROM " . $this->table . "
                  ORDER BY created_at DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    // Получить задачи проекта
    public function getByProject() {
        $query = "SELECT * FROM " . $this->table . "
                  WHERE project_id = :project_id
                  ORDER BY created_at DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":project_id", $this->project_id);
        $stmt->execute();
        return $stmt;
    }

    // Создать задачу
    public function create() {
        $query = "INSERT INTO " . $this->table . "
                  SET title = :title,
                      description = :description,
                      priority = :priority,
                      status = :status,
                      due_date = :due_date,
                      project_id = :project_id,
                      assigned_to = :assigned_to,
                      is_recurring = :is_recurring";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":title", $this->title);
        $stmt->bindParam(":description", $this->description);
        $stmt->bindParam(":priority", $this->priority);
        $stmt->bindParam(":status", $this->status);
        $stmt->bindParam(":due_date", $this->due_date);
        $stmt->bindParam(":project_id", $this->project_id);
        $stmt->bindParam(":assigned_to", $this->assigned_to);
        $stmt->bindParam(":is_recurring", $this->is_recurring);

        return $stmt->execute();
    }

    // Обновить статус
    public function updateStatus() {
        $query = "UPDATE " . $this->table . "
                  SET status = :status,
                      completed_at = :completed_at
                  WHERE id = :id";

        $stmt = $this->conn->prepare($query);

        if ($this->status === "completed") {
            $this->completed_at = date("Y-m-d H:i:s");
        } else {
            $this->completed_at = null;
        }

        $stmt->bindParam(":status", $this->status);
        $stmt->bindParam(":completed_at", $this->completed_at);
        $stmt->bindParam(":id", $this->id);

        return $stmt->execute();
    }

    // Удалить задачу
    public function delete() {
        $query = "DELETE FROM " . $this->table . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->id);
        return $stmt->execute();
    }
}
