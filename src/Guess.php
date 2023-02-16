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

        if( ! $current_guess ) {
            return 0;
        }

        $questions = $current_guess->get_thing()->question_ids();
        $question_count = count( $questions );

        if( $question_count > 20 ) {
            $question_count = 20;
        }

        return intval( $points_difference / $question_count * 100 );
    }

    /**
     * Get the top X guesses
     *
     * @return Thing[]
     */
    public function top_X( int $x ): array {
        arsort( $this->points );
        $values = array_keys( $this->points );
        $values = array_slice( $values, 0, $x );

        $topX = [];

        foreach( $values as $value ) {
            $thing = Thing::load( $value, $this->db );
            $topX[] = $thing;
        }

        return $topX;
    }

    /**
     * Gets all the guesses that have the most points
     * and that have unanswered questions left
     *
     * @return Thing[]
     */
    public function best_guesses(): array {
        arsort( $this->points );

        $top = [];
        $prev_points = null;

        foreach( $this->points as $thing_id => $points ) {
            if( $prev_points !== null && $points !== $prev_points ) {
                break;
            }

            $thing = Thing::load( $thing_id, $this->db );
            $diff = array_diff( $thing->question_ids(), $this->questions );

            if ( count( $diff ) ) {
                $top[] = $thing;
                $prev_points = $points;
            }
        }

        return $top;
    }

    /**
     * Gets the category that includes most of the
     * best guesses
     */
    public function best_category(): int|null {
        $best_categories = [];
        foreach( $this->best_guesses() as $thing ) {
            $cats = $thing->category_ids();
            foreach( $cats as $cat_id ) {
                if( in_array( $cat_id, $this->guessed_categories ) ) {
                    continue;
                }
                if( ! isset( $best_categories[$cat_id] ) ) {
                    $best_categories[$cat_id] = 0;
                }
                $best_categories[$cat_id]++;
            }
        }

        arsort( $best_categories );

        return key( $best_categories );
    }

    public function save() {
        $_SESSION['guess'] = [
            'questions' => $this->questions,
            'points' => $this->points,
            'guessed_categories' => $this->guessed_categories,
            'categories' => $this->categories,
        ];
    }

    public function clear() {
        $_SESSION['guess'] = "";
    }

    /**
     * Create a Guess object from session
     */
    public static function from_session( PDO $db ): static {
        $data = $_SESSION['guess'] ?? [];

        $questions = $data['questions'] ?? [];
        $points = $data['points'] ?? [];
        $guessed_categories = $data['guessed_categories'] ?? [];
        $categories = $data['categories'] ?? [];

        return new Guess(
            db: $db,
            questions: $questions,
            points: $points,
            guessed_categories: $guessed_categories,
            categories: $categories
        );
    }
}
