<?php
class Answer {
    public function __construct(
        public PDO $db,
        public ?int $id,
        public int $question_id,
        public int $thing_id,
    ) {}
  
    public function save() {
        $stmt = $this->db->prepare(
            "INSERT INTO answers (
                question_id,
                thing_id
            ) VALUES (
                :question_id,
                :thing_id
            )"
        );

        $stmt->execute( [
            ":question_id" => $this->question_id,
            ":thing_id" => $this->thing_id,
        ] );
      
        $this->id = $this->db->lastInsertId();
    }

    public static function load( int $id, PDO $db ) {
        $stmt = $db->prepare(
            "SELECT question_id,
                    thing_id
            FROM    answers
            WHERE   thing_id = :id"
        );

        $stmt->execute( [
            ":id" => $id
        ] );

        $row = $stmt->fetch( PDO::FETCH_ASSOC );
      
        $answer = new Answer(
            id: $id,
            db: $db,
            question_id: $row['question_id'],
            thing_id: $row['thing_id'],
        );

        return $answer;
    }

    public function get_thing(): Thing|null {
        $thing = Thing::load( $this->thing_id, $this->db );

        return $thing;
    }
}
