<?php
class Guess {
    /**
     * @param PDO $db                   Database connection
     * @param int[] $questions          Array of question ID's
     * @param array $points             Associative array with the key being the Answer ID
     *                                  and the value being the sum of yes (+1) and no (-1)
     *                                  answers
     * @param int[] $guessed_categories Categories that have been guessed already
     * @param int[] $categories         Categories that have been verified already
     */
    public function __construct(
        protected PDO $db,
        public array $questions = [],
        public array $points = [],
        public array $guessed_categories = [],
        public array $categories = [],
    ) {}

    /**
     * @param Question|Category $question
     * @param bool|null $yes Yes = true, no = false, null = I don't know
     * @param bool $add_to_guessed
     */
    public function answer(
        Question|Category $question,
        bool|null $yes,
        bool $add_to_guessed = true,
    ) {
        if( $yes && $question instanceof Category ) {
            $other_categories = $question->get_other_categories( $this );
            foreach( $other_categories as $other_category ) {
                $this->answer( $other_category, false, false );
            }
        }

        $things = $question->fetchThings();

        $points_to_add = $question instanceof Question ? 1 : 3;

        file_put_contents( "log.txt", $question->name ?? $question->text.PHP_EOL, FILE_APPEND );

        foreach( $things as $thing_id ) {
            if ( ! array_key_exists( $thing_id, $this->points ) ) {
                $this->points[$thing_id] = 0;
            }

            $thing = Thing::load( $thing_id, $this->db );

            if ( $yes === true ) {
                file_put_contents( "log.txt", "Adding points for " . $thing->name.PHP_EOL, FILE_APPEND );
                $this->points[$thing_id] += $points_to_add;
            } elseif( $yes === false ) {
                file_put_contents( "log.txt", "Substracting points from " . $thing->name.PHP_EOL, FILE_APPEND );
                $this->points[$thing_id] -= $points_to_add*2;
            }
        }

        if( $question instanceof Question ) {
            $this->questions[] = $question->id;
        } else {
            if( $add_to_guessed ) {
                $this->guessed_categories[] = $question->id;
            }

            if( $yes ) {
                $this->categories[] = $question->id;
            }
        }

        $this->save();
    }

    /**
     * @return Answer|null the current best guess
     */
    public function current_guess(): Answer|null {
        $best_answer = null;
        $best_score = -9999;

        foreach ( $this->points as $thing_id => $score ) {
            if ( $score > $best_score ) {
                $best_score = $score;
                $best_answer = Answer::load( $thing_id, $this->db );
            }
        }

        if( $best_score < 1 ) {
            return null;
        }

        return $best_answer;
    }

    /**
     * @return Thing|null the next guess that has unasked questions
     */
    public function next_guess(): Thing|null {
        arsort( $this->points );

        foreach ( array_keys( $this->points ) as $thing_id ) {
            $thing = Thing::load( $thing_id, $this->db );
            $diff = array_diff( $thing->question_ids(), $this->questions );

            if ( count( $diff ) ) {
                return $thing;
            }
        }

        return null;
    }

    /**
     * Get confidence as a percentage 0 - 100
     */
    public function get_confidence(): int {
        arsort( $this->points );
        $values = array_values( $this->points );
        $max = $values[0] ?? 0;
        $second_max = $values[1] ?? 0;

        $points_difference = $max - $second_max;

        if( ! $points_difference ) {
            return 0;
        }

        $current_guess = $this->current_guess();
        $questions = $current_guess->get_thing()->question_ids();
        $question_count = count( $questions );

        if( $question_count > 20 ) {
            $question_count = 20;
        }

        return intval( $points_difference / $question_count * 100 );
    }

    /**
     * Get the top X guesses
     */
    public function top_X( int $x ): string {
        arsort( $this->points );
        $values = array_keys( $this->points );
        $values = array_slice( $values, 0, $x );

        $top5 = [];

        foreach( $values as $value ) {
            $thing = Thing::load( $value, $this->db );
            $top5[] = $thing->name;
        }

        return implode( ", ", $top5 );
    }

    public function save() {
        file_put_contents( "guess.txt", $this->create_json() );
    }

    public function clear() {
        file_put_contents( "guess.txt", "" );
    }

    /**
     * Creates a JSON representation of the points and the questions
     * so that they can be loaded with from_json later
     */
    public function create_json(): string {
        return json_encode([
            'questions' => $this->questions,
            'points' => $this->points,
            'guessed_categories' => $this->guessed_categories,
            'categories' => $this->categories,
        ]);
    }

    /**
     * Create a Guess object from json
     */
    public static function from_json( PDO $db, string $json ) {
        $data = json_decode( $json, true );
        $questions = $data['questions'] ?? [];
        $points = $data['points'] ?? [];
        $guessed_categories = $data['guessed_categories'] ?? [];
        $categories = $data['categories'] ?? [];

        return new Guess( $db, $questions, $points, $guessed_categories, $categories );
    }
}
