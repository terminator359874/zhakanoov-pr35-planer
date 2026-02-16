<?php
class TaskComment {

    private $conn;
    private $table = "task_comments";

    public $id;
    public $task_id;
    public $user_id;
    public $comment;
    public $created_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Получить комментарии по задаче
    public function getByTask() {
        $query = "SELECT * FROM " . $this->table . "
                  WHERE task_id = :task_id
                  ORDER BY created_at ASC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":task_id", $this->task_id);
        $stmt->execute();
        return $stmt;
    }

    // Добавить комментарий
    public function create() {
        $query = "INSERT INTO " . $this->table . "
                  SET task_id = :task_id,
                      user_id = :user_id,
                      comment = :comment";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":task_id", $this->task_id);
        $stmt->bindParam(":user_id", $this->user_id);
        $stmt->bindParam(":comment", $this->comment);

        return $stmt->execute();
    }

    // Удалить комментарий
    public function delete() {
        $query = "DELETE FROM " . $this->table . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->id);
        return $stmt->execute();
    }
}
