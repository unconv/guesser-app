<?php
class Question {
    public function __construct(
        protected PDO $db,
        public ?int $id,
        public string $text,
    ) {}

    public static function by_text( string $text, PDO $db ): static|null {
        $stmt = $db->prepare(
            "SELECT question_id,
                    question_text
            FROM    questions
            WHERE   question_text = :text
            LIMIT   1"
        );

        $stmt->execute( [
            ":text" => $text,
        ] );

        $row = $stmt->fetch( PDO::FETCH_ASSOC );

        if( ! $row ) {
            return null;
        }

        $question = new Question(
            db: $db,
            id: $row['question_id'],
            text: $row['question_text'],
        );

        return $question;
    }

    public static function load( int $id, PDO $db ): static|null {
        $stmt = $db->prepare(
            "SELECT question_id,
                    question_text
            FROM    questions
            WHERE   question_id = :id
            LIMIT   1"
        );

        $stmt->execute( [
            ":id" => $id,
        ] );

        $row = $stmt->fetch( PDO::FETCH_ASSOC );

        if( ! $row ) {
            return null;
        }

        $question = new Question(
            db: $db,
            id: $row['question_id'],
            text: $row['question_text'],
        );

        return $question;
    }

    public static function fetchRandom( Guess $guess, PDO $db ): static {
        $exclude = $guess->questions;

        $exclude_query = "";
        $exec = [];

        if( ! empty( $exclude ) ) {
            $exclude_query = " WHERE questions.question_id NOT IN (".implode( ", ", $exclude ).")";
        }

        $order_query = "";

        $thing_id_query = "";
        $next_guess = $guess->next_guess();
        if( $next_guess ) {
            $exec[":thing_id"] = $next_guess->id;
            $thing_id_query = ", questions.question_id IN (SELECT question_id FROM answers where thing_id = :thing_id) AS is_guess";
            $order_query = "is_guess DESC, ";
            file_put_contents( "log.txt", "Next guess: " . $next_guess->name.PHP_EOL, FILE_APPEND );
        }

        $stmt = $db->prepare(
            "SELECT answers.question_id,
                    questions.question_text,
                    COUNT(*)
                    ".$thing_id_query."
            FROM    answers
            LEFT JOIN questions ON questions.question_id = answers.question_id
                    ".$exclude_query."
            GROUP BY answers.question_id
            ORDER BY ".$order_query."`COUNT(*)` DESC, RAND();"
        );

        $stmt->execute( $exec );

        $row = $stmt->fetch( PDO::FETCH_ASSOC );

        if( $next_guess && ! $row['is_guess'] ) {
            $guess->points[$next_guess->id] = -20;
        }

        $question = new Question(
            db: $db,
            id: $row['question_id'],
            text: $row['question_text'],
        );

        return $question;
    }

    public function save() {
        $stmt = $this->db->prepare(
            "INSERT INTO questions (
                question_text
            ) VALUES (
                :text
            )"
        );

        $stmt->execute( [
            ":text" => $this->text,
        ] );

        $this->id = $this->db->lastInsertId();
    }

    public function fetchThings() {
        $stmt = $this->db->prepare(
            "SELECT DISTINCT thing_id
            FROM    answers
            WHERE   question_id = :id"
        );

        $stmt->execute( [
            ":id" => $this->id
        ] );

        $rows = $stmt->fetchAll( PDO::FETCH_ASSOC );

        $things = [];

        foreach( $rows as $row ) {
            $things[] = $row['thing_id'];
        }

        return $things;
    }

    public function addAnswer( $thing_id ) {
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
            ":question_id" => $this->id,
            ":thing_id" => $thing_id,
        ] );

        $answer_id = $this->db->lastInsertId();

        return $answer_id;
    }
}
